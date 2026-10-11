import { HttpErrorResponse } from '@angular/common/http';
import { Component, inject, input, signal } from '@angular/core';
import { NonNullableFormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { Router } from '@angular/router';

import { messageOf } from '../../core/interceptors/error.interceptor';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-login-page',
  imports: [
    ReactiveFormsModule,
    MatCardModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule,
    MatProgressBarModule,
  ],
  template: `
    <div class="wrapper">
      <mat-card class="card" appearance="outlined">
        @if (loading()) {
          <mat-progress-bar mode="indeterminate" />
        }
        <mat-card-header>
          <mat-icon mat-card-avatar class="avatar">inventory_2</mat-icon>
          <mat-card-title>Каталог товаров</mat-card-title>
          <mat-card-subtitle>Войдите, чтобы продолжить</mat-card-subtitle>
        </mat-card-header>

        <form [formGroup]="form" (ngSubmit)="submit()">
          <mat-card-content class="fields">
            <mat-form-field>
              <mat-label>Email</mat-label>
              <input
                matInput
                type="email"
                formControlName="email"
                autocomplete="username"
                data-testid="email"
              />
              @if (form.controls.email.hasError('email')) {
                <mat-error>Некорректный email</mat-error>
              }
            </mat-form-field>

            <mat-form-field>
              <mat-label>Пароль</mat-label>
              <input
                matInput
                type="password"
                formControlName="password"
                autocomplete="current-password"
                data-testid="password"
              />
            </mat-form-field>

            @if (error()) {
              <p class="error" role="alert">{{ error() }}</p>
            }
          </mat-card-content>

          <mat-card-actions>
            <button
              mat-flat-button
              type="submit"
              class="submit"
              [disabled]="form.invalid || loading()"
              data-testid="login-submit"
            >
              Войти
            </button>
          </mat-card-actions>
        </form>
      </mat-card>
    </div>
  `,
  styles: `
    .wrapper {
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 16px;
      background: var(--mat-sys-surface-container-low);
    }
    .card {
      width: 100%;
      max-width: 400px;
      overflow: hidden;
    }
    .avatar {
      display: grid;
      place-items: center;
      color: var(--mat-sys-primary);
    }
    .fields {
      display: flex;
      flex-direction: column;
      gap: 12px;
      padding-top: 16px;
    }
    .error {
      margin: 0;
      color: var(--mat-sys-error);
    }
    .submit {
      width: 100%;
    }
    mat-card-actions {
      padding: 0 16px 16px;
    }
  `,
})
export class LoginPage {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  readonly returnUrl = input<string | undefined>();

  protected readonly loading = signal(false);
  protected readonly error = signal<string | null>(null);

  protected readonly form = inject(NonNullableFormBuilder).group({
    email: ['admin@example.com', [Validators.required, Validators.email]],
    password: ['', Validators.required],
  });

  submit(): void {
    if (this.form.invalid) {
      return;
    }

    this.loading.set(true);
    this.error.set(null);
    const { email, password } = this.form.getRawValue();

    this.auth.login(email, password).subscribe({
      next: () => {
        void this.router.navigateByUrl(this.returnUrl() || '/products');
      },
      error: (error: unknown) => {
        this.loading.set(false);
        this.error.set(
          error instanceof HttpErrorResponse && error.status === 401
            ? 'Неверный email или пароль'
            : ((error instanceof HttpErrorResponse ? messageOf(error) : null) ??
                'Не удалось войти'),
        );
      },
    });
  }
}
