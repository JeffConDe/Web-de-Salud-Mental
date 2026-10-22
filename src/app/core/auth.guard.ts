import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { catchError, map, of } from 'rxjs';
import { AuthService } from './auth.service';

export const authGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  if (!auth.token) {
    return router.createUrlTree(['/login']);
  }

  if (auth.currentUser) {
    return true;
  }

  return auth.restoreSession().pipe(
    map((user) => user !== null || router.createUrlTree(['/login'])),
    catchError(() => {
      return of(router.createUrlTree(['/login']));
    }),
  );
};
