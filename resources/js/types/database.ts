export type DatabaseStatus = 'pending' | 'processing' | 'ready' | 'failed';

export type CreationStep = 'validating' | 'creating' | 'configuring' | 'migrating' | 'permissions' | 'testing' | 'ready';

export interface Database {
  id: string;
  name: string;
  display_name: string | null;
  description: string | null;
  host: string;
  port: number;
  database_name: string;
  is_active: boolean;
  status: DatabaseStatus;
  current_step: CreationStep | null;
  progress: number;
  error_message: string | null;
  settings: Record<string, unknown> | null;
  credentials_count?: number;
  created_at: string;
  updated_at: string;
}

export interface DatabaseCollection {
  data: Database[];
}

export interface StepUpdatePayload {
  step: CreationStep;
  progress: number;
  database: {
    id: string;
    name: string;
    status: DatabaseStatus;
  };
}

export interface DatabaseCreatedPayload {
  database: {
    id: string;
    name: string;
    status: DatabaseStatus;
  };
}

export interface DatabaseFailedPayload {
  status: 'failed';
  error: string;
  database: {
    id: string;
    name: string;
  };
}
