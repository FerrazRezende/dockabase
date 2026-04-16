export interface User {
  id: number;
  name: string;
  email: string;
  is_admin: boolean;
  avatar?: string | null;
}

export interface UserCollection {
  data: User[];
}

export interface SystemUser {
    id: string;
    name: string;
    email: string;
    is_admin: boolean;
    created_at: string;
    updated_at: string;
}

export interface SystemUserCollection {
    data: SystemUser[];
}
