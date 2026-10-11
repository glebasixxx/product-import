import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';

import { ProductService } from './product.service';

describe('ProductService', () => {
  let service: ProductService;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(ProductService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('requests a page with filters as query params', () => {
    service
      .getProducts({ page: 3, limit: 50, name: 'бермуды', price_min: 100, price_max: 0 })
      .subscribe();

    const req = http.expectOne((r) => r.url === '/api/products');
    expect(req.request.method).toBe('GET');
    expect(req.request.params.get('page')).toBe('3');
    expect(req.request.params.get('limit')).toBe('50');
    expect(req.request.params.get('name')).toBe('бермуды');
    expect(req.request.params.get('price_min')).toBe('100');
    expect(req.request.params.get('price_max')).toBe('0');
    req.flush({ data: [], meta: { page: 3, limit: 50, total: 0, total_pages: 0 } });
  });

  it('omits empty filters', () => {
    service.getProducts({ page: 1, limit: 20, name: '', price_min: null }).subscribe();

    const req = http.expectOne((r) => r.url === '/api/products');
    expect(req.request.params.keys()).toEqual(['page', 'limit']);
    req.flush({ data: [], meta: { page: 1, limit: 20, total: 0, total_pages: 0 } });
  });

  it('loads a single product', () => {
    service.getProduct(42).subscribe((product) => expect(product.id).toBe(42));

    http.expectOne('/api/products/42').flush({ id: 42 });
  });
});
