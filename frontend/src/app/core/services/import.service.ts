import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, switchMap, takeWhile, timer } from 'rxjs';

import { ImportJob, isImportFinished } from '../../models';
import { API_BASE_URL } from '../api';

export const MAX_IMPORT_FILE_SIZE = 10 * 1024 * 1024;
export const POLL_INTERVAL_MS = 1000;

const LAST_IMPORT_KEY = 'lastImportId';

@Injectable({ providedIn: 'root' })
export class ImportService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${inject(API_BASE_URL)}/imports`;

  validateFile(file: File): string | null {
    if (!file.name.toLowerCase().endsWith('.xlsx')) {
      return 'Допускаются только файлы .xlsx';
    }
    if (file.size > MAX_IMPORT_FILE_SIZE) {
      return 'Размер файла не должен превышать 10 МБ';
    }
    if (file.size === 0) {
      return 'Файл пустой';
    }
    return null;
  }

  upload(file: File): Observable<ImportJob> {
    const body = new FormData();
    body.append('file', file, file.name);

    return this.http.post<ImportJob>(this.baseUrl, body);
  }

  get(id: string): Observable<ImportJob> {
    return this.http.get<ImportJob>(`${this.baseUrl}/${id}`);
  }

  list(): Observable<ImportJob[]> {
    return this.http.get<ImportJob[]>(this.baseUrl);
  }

  poll(id: string, intervalMs = POLL_INTERVAL_MS): Observable<ImportJob> {
    return timer(0, intervalMs).pipe(
      switchMap(() => this.get(id)),
      takeWhile((job) => !isImportFinished(job), true),
    );
  }

  rememberLastImport(id: string): void {
    try {
      sessionStorage.setItem(LAST_IMPORT_KEY, id);
    } catch {}
  }

  lastImportId(): string | null {
    try {
      return sessionStorage.getItem(LAST_IMPORT_KEY);
    } catch {
      return null;
    }
  }
}
