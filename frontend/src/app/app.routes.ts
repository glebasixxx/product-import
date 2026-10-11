import { Routes } from '@angular/router';
import { provideEffects } from '@ngrx/effects';
import { provideState } from '@ngrx/store';

import { authGuard, guestGuard } from './core/guards/auth.guard';
import { ProductsEffects, productsFeature } from './store/products';

export const routes: Routes = [
  {
    path: 'login',
    canActivate: [guestGuard],
    title: 'Вход',
    loadComponent: () => import('./features/auth/login.page').then((m) => m.LoginPage),
  },
  {
    path: '',
    canActivate: [authGuard],
    loadComponent: () => import('./layout/shell.component').then((m) => m.ShellComponent),
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'products' },
      {
        path: 'products',
        providers: [provideState(productsFeature), provideEffects(ProductsEffects)],
        children: [
          {
            path: '',
            title: 'Товары',
            loadComponent: () =>
              import('./features/products/product-list.page').then((m) => m.ProductListPage),
          },
          {
            path: ':id',
            title: 'Карточка товара',
            loadComponent: () =>
              import('./features/products/product-detail.page').then((m) => m.ProductDetailPage),
          },
        ],
      },
      {
        path: 'import',
        title: 'Импорт товаров',
        loadComponent: () => import('./features/import/import.page').then((m) => m.ImportPage),
      },
    ],
  },
  { path: '**', redirectTo: '' },
];
