import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';

import { ImportJob } from '../../models';
import { ImportService, MAX_IMPORT_FILE_SIZE } from './import.service';

function job(overrides: Partial<ImportJob>): ImportJob {
  return {
    id: 'job-1',
    status: 'pending',
    original_filename: 'import.xlsx',
    total_rows: 0,
    processed_rows: 0,
    progress: 0,
    created_count: 0,
    updated_count: 0,
    failed_count: 0,
    errors: [],
    warnings: [],
    failure_reason: null,
    started_at: null,
    finished_at: null,
    created_at: '2026-10-11T10:00:00Z',
    ...overrides,
  };
}

describe('ImportService', () => {
  let service: ImportService;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(ImportService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    http.verify();
    vi.useRealTimers();
  });

  it('validates extension and size on the client', () => {
    expect(service.validateFile(new File(['x'], 'data.csv'))).toContain('.xlsx');
    expect(service.validateFile(new File([], 'empty.xlsx'))).toBe('Файл пустой');

    const big = new File(['x'], 'big.xlsx');
    Object.defineProperty(big, 'size', { value: MAX_IMPORT_FILE_SIZE + 1 });
    expect(service.validateFile(big)).toContain('10 МБ');

    expect(service.validateFile(new File(['x'], 'IMPORT.XLSX'))).toBeNull();
  });

  it('uploads the file as multipart form data', () => {
    const file = new File(['x'], 'import.xlsx');
    service.upload(file).subscribe((result) => expect(result.id).toBe('job-1'));

    const req = http.expectOne('/api/imports');
    expect(req.request.method).toBe('POST');
    expect((req.request.body as FormData).get('file')).toBeInstanceOf(File);
    req.flush(job({}));
  });

  it('polls the status until the import is finished', () => {
    vi.useFakeTimers();
    const statuses: string[] = [];
    let completed = false;

    service.poll('job-1', 1000).subscribe({
      next: (j) => statuses.push(j.status),
      complete: () => (completed = true),
    });

    vi.advanceTimersByTime(0);
    http.expectOne('/api/imports/job-1').flush(job({ status: 'processing', progress: 40 }));

    vi.advanceTimersByTime(1000);
    http.expectOne('/api/imports/job-1').flush(job({ status: 'completed', progress: 100 }));

    vi.advanceTimersByTime(5000);
    http.expectNone('/api/imports/job-1');

    expect(statuses).toEqual(['processing', 'completed']);
    expect(completed).toBe(true);
  });
});
