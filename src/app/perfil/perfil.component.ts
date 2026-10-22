import { HttpErrorResponse } from '@angular/common/http';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../core/auth.service';
import { apiErrorMessage } from '../core/api-error';

@Component({
  selector: 'app-perfil',
  imports: [FormsModule],
  templateUrl: './perfil.component.html',
  styleUrl: './perfil.component.css'
})
export class PerfilComponent {
  name: string;
  email: string;
  message = '';
  isSaving = false;

  constructor(private readonly auth: AuthService) {
    this.name = auth.currentUser?.name ?? '';
    this.email = auth.currentUser?.email ?? '';
  }

  save(): void {
    this.isSaving = true;
    this.message = '';

    this.auth.updateProfile(this.name, this.email).subscribe({
      next: (user) => {
        this.name = user.name;
        this.email = user.email;
        this.message = 'Tu perfil se actualizó correctamente.';
        this.isSaving = false;
      },
      error: (error: HttpErrorResponse) => {
        this.message = apiErrorMessage(error, 'No se pudo actualizar el perfil. Inténtalo de nuevo.');
        this.isSaving = false;
      },
    });
  }
}
