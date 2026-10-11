import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

import { Paginated, Product, ProductQuery, ProductSummary } from '../../models';
import { API_BASE_URL } from '../api';

@Injectable({ providedIn: 'root' })
export class ProductService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${inject(API_BASE_URL)}/products`;

  getProducts(query: ProductQuery): Observable<Paginated<ProductSummary>> {
    let params = new HttpParams().set('page', query.page).set('limit', query.limit);

    if (query.name) {
      params = params.set('name', query.name);
    }
    if (query.price_min != null) {
      params = params.set('price_min', query.price_min);
    }
    if (query.price_max != null) {
      params = params.set('price_max', query.price_max);
    }

    return this.http.get<Paginated<ProductSummary>>(this.baseUrl, { params });
  }

  getProduct(id: number): Observable<Product> {
    return this.http.get<Product>(`${this.baseUrl}/${id}`);
  }
}
