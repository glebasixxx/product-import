import { createSelector } from '@ngrx/store';

import { productsFeature } from './products.reducer';

export const selectProductsState = productsFeature.selectProductsState;

export const selectAllProducts = productsFeature.selectProducts;

export const selectProductsLoading = productsFeature.selectLoading;

export const selectProductsError = productsFeature.selectError;

export const selectProductsQuery = productsFeature.selectQuery;

export const selectProductsMeta = productsFeature.selectMeta;

export const selectTotalPages = createSelector(
  selectProductsMeta,
  (meta) => meta?.total_pages ?? 0,
);

export const selectTotalProducts = createSelector(selectProductsMeta, (meta) => meta?.total ?? 0);
