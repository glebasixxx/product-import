export interface ProductAttribute {
  id: number;
  key: string;
  value: string;
}

export interface ProductImage {
  id: number;
  url: string;
  path: string | null;
  public_url: string;
  position: number;
}

export interface ProductSummary {
  id: number;
  external_code: string;
  name: string;
  price: number;
  purchase_price: number | null;
  discount: number | null;
  thumbnail_url: string | null;
}

export interface Product extends Omit<ProductSummary, 'thumbnail_url'> {
  description: string | null;
  attributes: ProductAttribute[];
  images: ProductImage[];
  created_at: string;
  updated_at: string;
}

export interface PaginationMeta {
  page: number;
  limit: number;
  total: number;
  total_pages: number;
}

export interface Paginated<T> {
  data: T[];
  meta: PaginationMeta;
}

export interface ProductQuery {
  page: number;
  limit: number;
  name?: string | null;
  price_min?: number | null;
  price_max?: number | null;
}
