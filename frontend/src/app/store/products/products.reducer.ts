import { createFeature, createReducer, on } from '@ngrx/store';

import { PaginationMeta, ProductQuery, ProductSummary } from '../../models';
import { loadProducts, loadProductsFailure, loadProductsSuccess } from './products.actions';

export const DEFAULT_PRODUCT_QUERY: ProductQuery = { page: 1, limit: 20 };

export interface ProductsState {
  products: ProductSummary[];
  query: ProductQuery;
  meta: PaginationMeta | null;
  loading: boolean;
  error: string | null;
}

export const initialProductsState: ProductsState = {
  products: [],
  query: DEFAULT_PRODUCT_QUERY,
  meta: null,
  loading: false,
  error: null,
};

export const productsFeature = createFeature({
  name: 'products',
  reducer: createReducer(
    initialProductsState,
    on(loadProducts, (state, { query }): ProductsState => ({
      ...state,
      query,
      loading: true,
      error: null,
    })),
    on(loadProductsSuccess, (state, { products, meta }): ProductsState => ({
      ...state,
      products,
      meta,
      loading: false,
    })),
    on(loadProductsFailure, (state, { error }): ProductsState => ({
      ...state,
      products: [],
      loading: false,
      error,
    })),
  ),
});
