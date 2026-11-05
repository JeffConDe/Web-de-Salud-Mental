import { BehaviorSubject, of } from 'rxjs';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { AuthService } from '../core/auth.service';

import { InicioComponent } from './inicio.component';

describe('InicioComponent', () => {
  let component: InicioComponent;
  let fixture: ComponentFixture<InicioComponent>;
  let currentUser$: BehaviorSubject<unknown>;
  let logout: jasmine.Spy;

  beforeEach(async () => {
    currentUser$ = new BehaviorSubject<unknown>(null);
    logout = jasmine.createSpy('logout').and.returnValue(of(void 0));

    await TestBed.configureTestingModule({
      imports: [InicioComponent],
      providers: [
        provideRouter([]),
        { provide: AuthService, useValue: { currentUser$, logout } },
      ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InicioComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('shows a clear login button for visitors', () => {
    const loginLink = fixture.nativeElement.querySelector('a[routerLink="/login"]') as HTMLAnchorElement;
    expect(loginLink.textContent).toContain('Iniciar sesión');
  });

  it('shows a logout button for an authenticated user', () => {
    currentUser$.next({ id: 1, name: 'Admin Demo', email: 'admin@example.test', role: 'administrator' });
    fixture.detectChanges();

    const logoutButton = fixture.nativeElement.querySelector('button') as HTMLButtonElement;
    expect(logoutButton.textContent).toContain('Cerrar sesión');
    logoutButton.click();
    expect(logout).toHaveBeenCalled();
  });
});
