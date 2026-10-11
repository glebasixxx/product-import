export type ImportStatus = 'pending' | 'processing' | 'completed' | 'failed';

export interface ImportRowError {
  row: number;
  external_code: string | null;
  messages: string[];
}

export interface ImportWarning {
  row: number;
  external_code: string | null;
  message: string;
}

export interface ImportJob {
  id: string;
  status: ImportStatus;
  original_filename: string;
  total_rows: number;
  processed_rows: number;
  progress: number;
  created_count: number;
  updated_count: number;
  failed_count: number;
  errors: ImportRowError[];
  warnings: ImportWarning[];
  failure_reason: string | null;
  started_at: string | null;
  finished_at: string | null;
  created_at: string;
}

export function isImportFinished(job: Pick<ImportJob, 'status'>): boolean {
  return job.status === 'completed' || job.status === 'failed';
}
