import { TestBed } from '@angular/core/testing';
import {
  ActivatedRouteSnapshot,
  Router,
  RouterStateSnapshot,
  UrlTree,
  provideRouter,
} from '@angular/router';

import { AuthService } from '../services/auth.service';
import { authGuard, guestGuard } from './auth.guard';

describe('auth guards', () => {
  let authenticated = false;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        provideRouter([]),
        { provide: AuthService, useValue: { isAuthenticated: () => authenticated } },
      ],
    });
  });

  const run = (guard: typeof authGuard, url = '/products?page=2') =>
    TestBed.runInInjectionContext(() =>
      guard({} as ActivatedRouteSnapshot, { url } as RouterStateSnapshot),
    );

  it('authGuard lets authenticated users through', () => {
    authenticated = true;
    expect(run(authGuard)).toBe(true);
  });

  it('authGuard redirects guests to login with returnUrl', () => {
    authenticated = false;
    const result = run(authGuard) as UrlTree;

    expect(result).toBeInstanceOf(UrlTree);
    expect(TestBed.inject(Router).serializeUrl(result)).toBe(
      '/login?returnUrl=%2Fproducts%3Fpage%3D2',
    );
  });

  it('guestGuard sends authenticated users to the product list', () => {
    authenticated = true;
    const result = run(guestGuard) as UrlTree;

    expect(TestBed.inject(Router).serializeUrl(result)).toBe('/products');
  });
});
