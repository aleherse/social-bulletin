export interface CurrentUser {
  email: string;
}

export interface SessionResponse {
  user: CurrentUser;
}

export interface RegistrationRequest {
  email: string;
}
