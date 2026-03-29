export interface Database {
  id: string;
  name: string;
  display_name: string | null;
  description: string | null;
  host: string;
  port: number;
  database_name: string;
  is_active: boolean;
  settings: Record<string, unknown> | null;
  credentials_count?: number;
  created_at: string;
  updated_at: string;
}

export interface DatabaseCollection {
  data: Database[];
}
