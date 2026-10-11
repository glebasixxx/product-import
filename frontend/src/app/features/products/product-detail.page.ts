import { AsyncPipe, DecimalPipe } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { Component, inject, input, signal } from '@angular/core';
import { toObservable } from '@angular/core/rxjs-interop';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatDividerModule } from '@angular/material/divider';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { RouterLink } from '@angular/router';
import { Store } from '@ngrx/store';
import { Observable, catchError, map, of, startWith, switchMap, tap } from 'rxjs';

import { ProductService } from '../../core/services/product.service';
import { Product } from '../../models';
import { selectProductsQuery } from '../../store/products';

type ViewState =
  | { status: 'loading' }
  | { status: 'loaded'; product: Product }
  | { status: 'error'; message: string };

@Component({
  selector: 'app-product-detail-page',
  imports: [
    AsyncPipe,
    DecimalPipe,
    RouterLink,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatDividerModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './product-detail.page.html',
  styleUrl: './product-detail.page.scss',
})
export class ProductDetailPage {
  private readonly productService = inject(ProductService);

  readonly id = input.required<string>();

  protected readonly selectedImage = signal(0);

  protected readonly listQuery$ = inject(Store).select(selectProductsQuery);

  protected readonly state$: Observable<ViewState> = toObservable(this.id).pipe(
    tap(() => this.selectedImage.set(0)),
    switchMap((id) =>
      this.productService.getProduct(Number(id)).pipe(
        map((product): ViewState => ({ status: 'loaded', product })),
        catchError((error: unknown) =>
          of<ViewState>({
            status: 'error',
            message:
              error instanceof HttpErrorResponse && error.status === 404
                ? 'Товар не найден'
                : 'Не удалось загрузить товар',
          }),
        ),
        startWith<ViewState>({ status: 'loading' }),
      ),
    ),
  );

  selectImage(index: number): void {
    this.selectedImage.set(index);
  }
}
