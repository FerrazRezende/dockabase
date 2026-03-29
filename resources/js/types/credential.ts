import type { User } from './user';
import type { Database } from './database';

export type CredentialPermission = 'read' | 'write' | 'read-write';

export interface Credential {
  id: string;
  name: string;
  permission: CredentialPermission;
  permission_label: string;
  description: string | null;
  users_count?: number;
  databases_count?: number;
  users?: User[];
  databases?: Database[];
  created_at: string;
  updated_at: string;
}

export interface CredentialCollection {
  data: Credential[];
}
