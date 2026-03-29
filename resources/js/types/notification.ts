export type NotificationType = 'database_created' | 'database_failed' | 'schema_changed' | 'backup_completed';

export interface Notification {
  id: number;
  type: NotificationType;
  title: string;
  message: string;
  data: Record<string, unknown> | null;
  read: boolean;
  read_at: string | null;
  created_at: string;
}

export interface NotificationCollection {
  data: Notification[];
}
