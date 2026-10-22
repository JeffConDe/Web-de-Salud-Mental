import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../core/auth.service';
import { apiErrorMessage } from '../core/api-error';

@Component({
  selector: 'app-registro',
  imports: [FormsModule, RouterLink],
  templateUrl: './registro.component.html',
  styleUrl: './registro.component.css'
})
export class RegistroComponent {
  name = '';
  email = '';
  password = '';
  confirmPassword = '';
  message = '';
  isSubmitting = false;

  constructor(private readonly auth: AuthService, private readonly router: Router) {}

  onSubmit(): void {
    if (this.password !== this.confirmPassword) {
      this.message = 'Las contraseñas no coinciden. Revísalas e inténtalo de nuevo.';
      return;
    }

    this.isSubmitting = true;
    this.message = '';

    this.auth.register(this.name, this.email, this.password).subscribe({
      next: () => this.router.navigate(['/chat']),
      error: (error: HttpErrorResponse) => {
        this.message = apiErrorMessage(error, 'No se pudo crear la cuenta. Comprueba tu conexión e inténtalo de nuevo.');
        this.isSubmitting = false;
      },
      complete: () => {
        this.isSubmitting = false;
      },
    });
  }
}
