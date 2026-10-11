import { ProductsState, initialProductsState } from './products.reducer';
import {
  selectAllProducts,
  selectProductsLoading,
  selectTotalPages,
  selectTotalProducts,
} from './products.selectors';

describe('products selectors', () => {
  const productsState: ProductsState = {
    ...initialProductsState,
    loading: true,
    products: [
      {
        id: 7,
        external_code: 'X',
        name: 'Товар',
        price: 100,
        purchase_price: null,
        discount: null,
        thumbnail_url: null,
      },
    ],
    meta: { page: 1, limit: 20, total: 41, total_pages: 3 },
  };
  const rootState = { products: productsState };

  it('selectAllProducts returns the current page', () => {
    expect(selectAllProducts(rootState)).toHaveLength(1);
    expect(selectAllProducts(rootState)[0].id).toBe(7);
  });

  it('selectProductsLoading returns the loading flag', () => {
    expect(selectProductsLoading(rootState)).toBe(true);
  });

  it('selectTotalPages and selectTotalProducts read pagination meta', () => {
    expect(selectTotalPages(rootState)).toBe(3);
    expect(selectTotalProducts(rootState)).toBe(41);
  });

  it('selectTotalPages is 0 before the first load', () => {
    expect(selectTotalPages({ products: initialProductsState })).toBe(0);
  });
});
