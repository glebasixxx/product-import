export interface User {
  id: number;
  name: string;
  email: string;
}

export interface LoginResponse {
  token: string;
  token_type: 'Bearer';
  user: User;
}
