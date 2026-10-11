import { ProductSummary } from '../../models';
import { loadProducts, loadProductsFailure, loadProductsSuccess } from './products.actions';
import { initialProductsState, productsFeature } from './products.reducer';

const reducer = productsFeature.reducer;

const product: ProductSummary = {
  id: 1,
  external_code: 'A-1',
  name: 'Бермуды',
  price: 1320,
  purchase_price: 880,
  discount: 33.33,
  thumbnail_url: null,
};

describe('products reducer', () => {
  it('starts loading and stores the query', () => {
    const query = { page: 2, limit: 10, name: 'шорты' };
    const state = reducer(initialProductsState, loadProducts({ query }));

    expect(state.loading).toBe(true);
    expect(state.error).toBeNull();
    expect(state.query).toEqual(query);
  });

  it('stores products and pagination meta on success', () => {
    const meta = { page: 1, limit: 20, total: 1, total_pages: 1 };
    const loading = reducer(initialProductsState, loadProducts({ query: { page: 1, limit: 20 } }));
    const state = reducer(loading, loadProductsSuccess({ products: [product], meta }));

    expect(state.loading).toBe(false);
    expect(state.products).toEqual([product]);
    expect(state.meta).toEqual(meta);
  });

  it('stores the error on failure', () => {
    const state = reducer(
      { ...initialProductsState, products: [product], loading: true },
      loadProductsFailure({ error: 'boom' }),
    );

    expect(state.loading).toBe(false);
    expect(state.error).toBe('boom');
    expect(state.products).toEqual([]);
  });
});
