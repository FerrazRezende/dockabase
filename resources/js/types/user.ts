export interface User {
  id: number;
  name: string;
  email: string;
  is_admin: boolean;
}

export interface UserCollection {
  data: User[];
}
