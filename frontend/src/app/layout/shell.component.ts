import { Component, inject } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatTooltipModule } from '@angular/material/tooltip';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';

import { AuthService } from '../core/services/auth.service';

@Component({
  selector: 'app-shell',
  imports: [
    RouterOutlet,
    RouterLink,
    RouterLinkActive,
    MatToolbarModule,
    MatButtonModule,
    MatIconModule,
    MatTooltipModule,
  ],
  template: `
    <mat-toolbar class="toolbar">
      <a class="brand" routerLink="/products">
        <mat-icon>inventory_2</mat-icon>
        <span>Каталог товаров</span>
      </a>

      <nav class="nav">
        <a mat-button routerLink="/products" routerLinkActive="active">
          <mat-icon>list</mat-icon> Товары
        </a>
        <a mat-button routerLink="/import" routerLinkActive="active">
          <mat-icon>upload_file</mat-icon> Импорт
        </a>
      </nav>

      <span class="spacer"></span>

      @if (auth.user(); as user) {
        <span class="user">{{ user.email }}</span>
      }
      <button mat-icon-button matTooltip="Выйти" aria-label="Выйти" (click)="auth.logout()">
        <mat-icon>logout</mat-icon>
      </button>
    </mat-toolbar>

    <main class="content">
      <router-outlet />
    </main>
  `,
  styles: `
    .toolbar {
      position: sticky;
      top: 0;
      z-index: 10;
      gap: 8px;
      background: var(--mat-sys-surface-container);
      border-bottom: 1px solid var(--mat-sys-outline-variant);
    }
    .brand {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-right: 16px;
      color: inherit;
      text-decoration: none;
      font-weight: 500;
    }
    .nav a.active {
      background: var(--mat-sys-secondary-container);
    }
    .spacer {
      flex: 1;
    }
    .user {
      font-size: 14px;
      color: var(--mat-sys-on-surface-variant);
    }
    .content {
      max-width: 1200px;
      margin: 0 auto;
      padding: 24px 16px 48px;
    }
    @media (max-width: 640px) {
      .brand span,
      .user {
        display: none;
      }
    }
  `,
})
export class ShellComponent {
  protected readonly auth = inject(AuthService);
}
