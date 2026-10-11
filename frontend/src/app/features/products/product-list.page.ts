import { AsyncPipe, DecimalPipe } from '@angular/common';
import { Component, computed, effect, inject, untracked } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { NonNullableFormBuilder, ReactiveFormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatChipsModule } from '@angular/material/chips';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatPaginatorModule, PageEvent } from '@angular/material/paginator';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatTableModule } from '@angular/material/table';
import { ActivatedRoute, ParamMap, Router, RouterLink } from '@angular/router';
import { Store } from '@ngrx/store';

import { ProductQuery } from '../../models';
import {
  DEFAULT_PRODUCT_QUERY,
  loadProducts,
  selectAllProducts,
  selectProductsError,
  selectProductsLoading,
  selectTotalPages,
  selectTotalProducts,
} from '../../store/products';

@Component({
  selector: 'app-product-list-page',
  imports: [
    AsyncPipe,
    DecimalPipe,
    ReactiveFormsModule,
    RouterLink,
    MatTableModule,
    MatPaginatorModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule,
    MatCardModule,
    MatChipsModule,
    MatProgressBarModule,
  ],
  templateUrl: './product-list.page.html',
  styleUrl: './product-list.page.scss',
})
export class ProductListPage {
  private readonly store = inject(Store);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  protected readonly products$ = this.store.select(selectAllProducts);
  protected readonly loading$ = this.store.select(selectProductsLoading);
  protected readonly error$ = this.store.select(selectProductsError);
  protected readonly totalPages$ = this.store.select(selectTotalPages);
  protected readonly total$ = this.store.select(selectTotalProducts);

  protected readonly columns = ['image', 'name', 'external_code', 'price', 'discount'];
  protected readonly pageSizes = [10, 20, 50, 100];

  protected readonly filters = inject(NonNullableFormBuilder).group({
    name: [''],
    price_min: [null as number | null],
    price_max: [null as number | null],
  });

  private readonly params = toSignal(this.route.queryParamMap, { requireSync: true });
  protected readonly query = computed(() => parseQuery(this.params()));

  constructor() {
    effect(() => {
      const query = this.query();

      untracked(() => {
        this.filters.setValue(
          {
            name: query.name ?? '',
            price_min: query.price_min ?? null,
            price_max: query.price_max ?? null,
          },
          { emitEvent: false },
        );
        this.store.dispatch(loadProducts({ query }));
      });
    });
  }

  search(): void {
    const { name, price_min, price_max } = this.filters.getRawValue();

    this.navigate({
      page: 1,
      name: name.trim() || null,
      price_min: isNumber(price_min) ? price_min : null,
      price_max: isNumber(price_max) ? price_max : null,
    });
  }

  reset(): void {
    this.filters.reset({ name: '', price_min: null, price_max: null });
    this.navigate({ page: 1, name: null, price_min: null, price_max: null });
  }

  changePage(event: PageEvent): void {
    this.navigate({ page: event.pageIndex + 1, limit: event.pageSize });
  }

  private navigate(changes: Partial<ProductQuery>): void {
    const query = { ...this.query(), ...changes };

    void this.router.navigate([], {
      relativeTo: this.route,
      queryParams: {
        page: query.page,
        limit: query.limit,
        name: query.name || null,
        price_min: query.price_min ?? null,
        price_max: query.price_max ?? null,
      },
    });
  }
}

export function parseQuery(params: ParamMap): ProductQuery {
  const page = toPositiveInt(params.get('page')) ?? DEFAULT_PRODUCT_QUERY.page;
  const limit = Math.min(toPositiveInt(params.get('limit')) ?? DEFAULT_PRODUCT_QUERY.limit, 100);

  return {
    page,
    limit,
    name: params.get('name') || null,
    price_min: toNumber(params.get('price_min')),
    price_max: toNumber(params.get('price_max')),
  };
}

function toPositiveInt(value: string | null): number | null {
  const parsed = Number(value);
  return value !== null && Number.isInteger(parsed) && parsed > 0 ? parsed : null;
}

function toNumber(value: string | null): number | null {
  const parsed = Number(value);
  return value !== null && value !== '' && Number.isFinite(parsed) && parsed >= 0 ? parsed : null;
}

function isNumber(value: unknown): value is number {
  return typeof value === 'number' && Number.isFinite(value);
}
