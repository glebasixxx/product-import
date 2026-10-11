import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { catchError, throwError } from 'rxjs';

import { AuthService } from '../services/auth.service';

export const errorInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(AuthService);
  const snackBar = inject(MatSnackBar);

  return next(req).pipe(
    catchError((error: unknown) => {
      if (error instanceof HttpErrorResponse) {
        const isLoginRequest = req.url.endsWith('/auth/login');

        if (error.status === 401 && !isLoginRequest) {
          auth.clearSession();
          snackBar.open('Сессия истекла, войдите снова', 'OK', { duration: 4000 });
        } else if (error.status === 429) {
          snackBar.open(messageOf(error) ?? 'Слишком много запросов, попробуйте позже', 'OK', {
            duration: 5000,
          });
        } else if (error.status === 0 || error.status >= 500) {
          snackBar.open('Ошибка сервера. Попробуйте позже.', 'OK', { duration: 5000 });
        }
      }

      return throwError(() => error);
    }),
  );
};

export function messageOf(error: HttpErrorResponse): string | null {
  const body = error.error as { message?: unknown; errors?: Record<string, string[]> } | null;

  const firstFieldError = body?.errors ? Object.values(body.errors)[0]?.[0] : undefined;
  if (firstFieldError) {
    return firstFieldError;
  }

  return typeof body?.message === 'string' ? body.message : null;
}
