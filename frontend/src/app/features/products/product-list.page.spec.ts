import { TestBed } from '@angular/core/testing';
import { convertToParamMap, provideRouter } from '@angular/router';
import { RouterTestingHarness } from '@angular/router/testing';
import { MockStore, provideMockStore } from '@ngrx/store/testing';

import { initialProductsState, loadProducts } from '../../store/products';
import { ProductListPage, parseQuery } from './product-list.page';

describe('parseQuery', () => {
  it('reads page, limit and filters from the URL', () => {
    expect(
      parseQuery(
        convertToParamMap({
          page: '3',
          limit: '50',
          name: 'шорты',
          price_min: '100',
          price_max: '900',
        }),
      ),
    ).toEqual({ page: 3, limit: 50, name: 'шорты', price_min: 100, price_max: 900 });
  });

  it('falls back to defaults for missing or invalid values', () => {
    expect(parseQuery(convertToParamMap({ page: '-1', limit: '5000', price_min: 'abc' }))).toEqual({
      page: 1,
      limit: 100,
      name: null,
      price_min: null,
      price_max: null,
    });
  });
});

describe('ProductListPage', () => {
  let store: MockStore;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        provideRouter([{ path: 'products', component: ProductListPage }]),
        provideMockStore({
          initialState: {
            products: {
              ...initialProductsState,
              products: [
                {
                  id: 1,
                  external_code: 'A-1',
                  name: 'Бермуды мужские',
                  price: 1320,
                  purchase_price: 880,
                  discount: 33.33,
                  thumbnail_url: null,
                },
              ],
              meta: { page: 2, limit: 10, total: 11, total_pages: 2 },
            },
          },
        }),
      ],
    });
    store = TestBed.inject(MockStore);
  });

  it('dispatches loadProducts with the query from the URL', async () => {
    const dispatch = vi.spyOn(store, 'dispatch');

    await RouterTestingHarness.create('/products?page=2&limit=10&name=бермуды');

    expect(dispatch).toHaveBeenCalledWith(
      loadProducts({
        query: { page: 2, limit: 10, name: 'бермуды', price_min: null, price_max: null },
      }),
    );
  });

  it('renders products from the store as links to the card', async () => {
    const harness = await RouterTestingHarness.create('/products?page=2&limit=10');
    const element = harness.routeNativeElement as HTMLElement;

    const link = element.querySelector('a.product-link') as HTMLAnchorElement;
    expect(link.textContent?.trim()).toBe('Бермуды мужские');
    expect(link.getAttribute('href')).toBe('/products/1');
    expect(element.textContent).toContain('страница 2 из 2');
  });
});
