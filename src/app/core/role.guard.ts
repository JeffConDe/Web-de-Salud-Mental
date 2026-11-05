import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { catchError, map, of } from 'rxjs';
import { AuthRole, AuthService } from './auth.service';

export function roleGuard(allowedRoles: readonly AuthRole[]): CanActivateFn {
  return () => {
    const auth = inject(AuthService);
    const router = inject(Router);

    const hasAllowedRole = (role: AuthRole): boolean => allowedRoles.includes(role);
    if (auth.currentUser) {
      return hasAllowedRole(auth.currentUser.role) || router.createUrlTree(['/']);
    }

    return auth.restoreSession().pipe(
      map((user) => user && hasAllowedRole(user.role) ? true : router.createUrlTree(['/'])),
      catchError(() => of(router.createUrlTree(['/login']))),
    );
  };
}
