import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { Observable, finalize, tap } from 'rxjs';

import { LoginResponse, User } from '../../models';
import { API_BASE_URL } from '../api';

const TOKEN_KEY = 'auth.token';
const USER_KEY = 'auth.user';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly router = inject(Router);
  private readonly baseUrl = `${inject(API_BASE_URL)}/auth`;

  private readonly tokenState = signal<string | null>(read(TOKEN_KEY));
  private readonly userState = signal<User | null>(parseUser(read(USER_KEY)));

  readonly user = this.userState.asReadonly();
  readonly isAuthenticated = computed(() => this.tokenState() !== null);

  token(): string | null {
    return this.tokenState();
  }

  login(email: string, password: string): Observable<LoginResponse> {
    return this.http.post<LoginResponse>(`${this.baseUrl}/login`, { email, password }).pipe(
      tap((response) => {
        write(TOKEN_KEY, response.token);
        write(USER_KEY, JSON.stringify(response.user));
        this.tokenState.set(response.token);
        this.userState.set(response.user);
      }),
    );
  }

  logout(): void {
    this.http
      .post(`${this.baseUrl}/logout`, {})
      .pipe(finalize(() => this.clearSession()))
      .subscribe({ error: () => undefined });
  }

  clearSession(redirect = true): void {
    remove(TOKEN_KEY);
    remove(USER_KEY);
    this.tokenState.set(null);
    this.userState.set(null);

    if (redirect) {
      void this.router.navigate(['/login'], {
        queryParams: { returnUrl: this.router.url !== '/login' ? this.router.url : null },
      });
    }
  }
}

function read(key: string): string | null {
  try {
    return localStorage.getItem(key);
  } catch {
    return null;
  }
}

function write(key: string, value: string): void {
  try {
    localStorage.setItem(key, value);
  } catch {}
}

function remove(key: string): void {
  try {
    localStorage.removeItem(key);
  } catch {}
}

function parseUser(raw: string | null): User | null {
  if (!raw) {
    return null;
  }
  try {
    return JSON.parse(raw) as User;
  } catch {
    return null;
  }
}
