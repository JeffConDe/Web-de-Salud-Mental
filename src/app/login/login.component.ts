import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../core/auth.service';
import { apiErrorMessage } from '../core/api-error';

@Component({
  selector: 'app-login',
  imports: [FormsModule, RouterLink],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css'
})
export class LoginComponent {
  email: string = '';
  password: string = '';
  message = '';
  isSubmitting = false;

  constructor(private readonly auth: AuthService, private readonly router: Router) {}

  onSubmit(): void {
    this.isSubmitting = true;
    this.message = '';

    this.auth.login(this.email, this.password).subscribe({
      next: () => this.router.navigate(['/chat']),
      error: (error: HttpErrorResponse) => {
        this.message = apiErrorMessage(error, 'No se pudo iniciar sesión. Comprueba tu conexión e inténtalo de nuevo.');
        this.isSubmitting = false;
      },
      complete: () => {
        this.isSubmitting = false;
      },
    });
  }
}
