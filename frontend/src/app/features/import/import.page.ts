import { AsyncPipe, DatePipe, DecimalPipe } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { Component, inject, signal } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatTableModule } from '@angular/material/table';
import { MatTooltipModule } from '@angular/material/tooltip';
import { RouterLink } from '@angular/router';
import {
  BehaviorSubject,
  EMPTY,
  Subject,
  catchError,
  exhaustMap,
  finalize,
  map,
  merge,
  of,
  shareReplay,
  startWith,
  switchMap,
  tap,
} from 'rxjs';

import { messageOf } from '../../core/interceptors/error.interceptor';
import { ImportService } from '../../core/services/import.service';
import { ImportJob, ImportStatus, isImportFinished } from '../../models';

const STATUS_LABELS: Record<ImportStatus, string> = {
  pending: 'В очереди',
  processing: 'Обрабатывается',
  completed: 'Завершён',
  failed: 'Ошибка',
};

@Component({
  selector: 'app-import-page',
  imports: [
    AsyncPipe,
    DatePipe,
    DecimalPipe,
    RouterLink,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatProgressBarModule,
    MatTableModule,
    MatExpansionModule,
    MatTooltipModule,
  ],
  templateUrl: './import.page.html',
  styleUrl: './import.page.scss',
})
export class ImportPage {
  private readonly importService = inject(ImportService);

  protected readonly selectedFile = signal<File | null>(null);
  protected readonly fileError = signal<string | null>(null);
  protected readonly uploading = signal(false);
  protected readonly dragOver = signal(false);

  protected readonly errorColumns = ['row', 'external_code', 'messages'];

  private readonly upload$ = new Subject<File>();
  private readonly watch$ = new Subject<string>();
  private readonly refreshHistory$ = new BehaviorSubject<void>(undefined);

  private readonly uploadedJobId$ = this.upload$.pipe(
    exhaustMap((file) => {
      this.uploading.set(true);
      this.fileError.set(null);

      return this.importService.upload(file).pipe(
        map((job) => job.id),
        tap((id) => {
          this.importService.rememberLastImport(id);
          this.selectedFile.set(null);
          this.refreshHistory$.next();
        }),
        catchError((error: unknown) => {
          this.fileError.set(
            (error instanceof HttpErrorResponse ? messageOf(error) : null) ??
              'Не удалось загрузить файл',
          );
          return EMPTY;
        }),
        finalize(() => this.uploading.set(false)),
      );
    }),
  );

  protected readonly job$ = merge(this.uploadedJobId$, this.watch$).pipe(
    startWith(this.importService.lastImportId()),
    switchMap((id) =>
      id
        ? this.importService.poll(id).pipe(
            tap((job) => isImportFinished(job) && this.refreshHistory$.next()),
            catchError(() => of(null)),
          )
        : of(null),
    ),
    shareReplay({ bufferSize: 1, refCount: true }),
  );

  protected readonly history$ = this.refreshHistory$.pipe(
    switchMap(() => this.importService.list().pipe(catchError(() => of([] as ImportJob[])))),
  );

  onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.pickFile(input.files?.item(0) ?? null);
    input.value = '';
  }

  onDragOver(event: DragEvent): void {
    event.preventDefault();
    this.dragOver.set(true);
  }

  onDragLeave(): void {
    this.dragOver.set(false);
  }

  onDrop(event: DragEvent): void {
    event.preventDefault();
    this.dragOver.set(false);
    this.pickFile(event.dataTransfer?.files.item(0) ?? null);
  }

  upload(): void {
    const file = this.selectedFile();
    if (file && !this.fileError()) {
      this.upload$.next(file);
    }
  }

  showReport(job: ImportJob): void {
    this.importService.rememberLastImport(job.id);
    this.watch$.next(job.id);
  }

  statusLabel(status: ImportStatus): string {
    return STATUS_LABELS[status];
  }

  isFinished(job: ImportJob): boolean {
    return isImportFinished(job);
  }

  private pickFile(file: File | null): void {
    this.selectedFile.set(file);
    this.fileError.set(file ? this.importService.validateFile(file) : null);
  }
}
