import { HttpErrorResponse } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { Action } from '@ngrx/store';
import { provideMockActions } from '@ngrx/effects/testing';
import { Observable, firstValueFrom, of, throwError } from 'rxjs';

import { ProductService } from '../../core/services/product.service';
import { loadProducts, loadProductsFailure, loadProductsSuccess } from './products.actions';
import { ProductsEffects } from './products.effects';

describe('ProductsEffects', () => {
  let actions$: Observable<Action>;
  let productService: { getProducts: ReturnType<typeof vi.fn> };
  let effects: ProductsEffects;

  beforeEach(() => {
    productService = { getProducts: vi.fn() };

    TestBed.configureTestingModule({
      providers: [
        ProductsEffects,
        provideMockActions(() => actions$),
        { provide: ProductService, useValue: productService },
      ],
    });

    effects = TestBed.inject(ProductsEffects);
  });

  it('loads products through ProductService', async () => {
    const query = { page: 2, limit: 10 };
    const meta = { page: 2, limit: 10, total: 15, total_pages: 2 };
    productService.getProducts.mockReturnValue(of({ data: [], meta }));
    actions$ = of(loadProducts({ query }));

    const action = await firstValueFrom(effects.loadProducts$);

    expect(productService.getProducts).toHaveBeenCalledWith(query);
    expect(action).toEqual(loadProductsSuccess({ products: [], meta }));
  });

  it('maps HTTP errors to loadProductsFailure', async () => {
    productService.getProducts.mockReturnValue(
      throwError(
        () => new HttpErrorResponse({ status: 422, error: { message: 'Неверные параметры' } }),
      ),
    );
    actions$ = of(loadProducts({ query: { page: 1, limit: 20 } }));

    const action = await firstValueFrom(effects.loadProducts$);

    expect(action).toEqual(loadProductsFailure({ error: 'Неверные параметры' }));
  });
});
