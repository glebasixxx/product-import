import { HttpClient, provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';

import { AuthService } from '../services/auth.service';
import { authInterceptor } from './auth.interceptor';

describe('authInterceptor', () => {
  let token: string | null;
  let http: HttpClient;
  let controller: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        provideHttpClient(withInterceptors([authInterceptor])),
        provideHttpClientTesting(),
        { provide: AuthService, useValue: { token: () => token } },
      ],
    });
    http = TestBed.inject(HttpClient);
    controller = TestBed.inject(HttpTestingController);
  });

  afterEach(() => controller.verify());

  it('adds the Bearer token to API calls', () => {
    token = 'abc';
    http.get('/api/products').subscribe();

    const req = controller.expectOne('/api/products');
    expect(req.request.headers.get('Authorization')).toBe('Bearer abc');
    req.flush({});
  });

  it('does not touch requests without a token or to other hosts', () => {
    token = null;
    http.get('/api/products').subscribe();
    expect(controller.expectOne('/api/products').request.headers.has('Authorization')).toBe(false);

    token = 'abc';
    http.get('https://example.com/data').subscribe();
    expect(
      controller.expectOne('https://example.com/data').request.headers.has('Authorization'),
    ).toBe(false);
  });
});
