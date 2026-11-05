import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, catchError, map, of, tap, throwError } from 'rxjs';
import { API_URL } from './api-url';

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: AuthRole;
}

export type AuthRole = 'administrator' | 'receptionist' | 'doctor' | 'patient';

interface AuthResponse {
  user: AuthUser;
  token: string;
}

const TOKEN_KEY = 'salud-mental-token';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly currentUserSubject = new BehaviorSubject<AuthUser | null>(null);
  readonly currentUser$ = this.currentUserSubject.asObservable();

  constructor(private readonly http: HttpClient) {}

  get token(): string | null {
    return sessionStorage.getItem(TOKEN_KEY);
  }

  get currentUser(): AuthUser | null {
    return this.currentUserSubject.value;
  }

  register(name: string, email: string, password: string): Observable<AuthUser> {
    return this.http.post<AuthResponse>(`${API_URL}/register`, {
      name,
      email,
      password,
      password_confirmation: password,
    }).pipe(
      map((response) => this.saveSession(response)),
    );
  }

  login(email: string, password: string): Observable<AuthUser> {
    return this.http.post<AuthResponse>(`${API_URL}/login`, { email, password }).pipe(
      map((response) => this.saveSession(response)),
    );
  }

  restoreSession(): Observable<AuthUser | null> {
    if (!this.token) {
      return of(null);
    }

    return this.loadCurrentUser().pipe(
      catchError((error: unknown) => {
        if (this.isUnauthorized(error)) {
          this.clearSession();
          return of(null);
        }

        return throwError(() => error);
      }),
    );
  }

  loadCurrentUser(): Observable<AuthUser> {
    return this.http.get<{ user: AuthUser }>(`${API_URL}/user`).pipe(
      map(({ user }) => this.setCurrentUser(user)),
    );
  }

  updateProfile(name: string, email: string): Observable<AuthUser> {
    return this.http.put<{ user: AuthUser }>(`${API_URL}/profile`, { name, email }).pipe(
      map(({ user }) => this.setCurrentUser(user)),
    );
  }

  logout(): Observable<void> {
    return this.http.post<void>(`${API_URL}/logout`, {}).pipe(
      tap(() => this.clearSession()),
      catchError((error: unknown) => {
        this.clearSession();
        return throwError(() => error);
      }),
    );
  }

  clearSession(): void {
    sessionStorage.removeItem(TOKEN_KEY);
    this.currentUserSubject.next(null);
  }

  private saveSession(response: AuthResponse): AuthUser {
    sessionStorage.setItem(TOKEN_KEY, response.token);
    return this.setCurrentUser(response.user);
  }

  private setCurrentUser(user: AuthUser): AuthUser {
    this.currentUserSubject.next(user);
    return user;
  }

  private isUnauthorized(error: unknown): boolean {
    return typeof error === 'object' && error !== null && 'status' in error && error.status === 401;
  }
}
