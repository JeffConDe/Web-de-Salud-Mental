import { AsyncPipe } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../core/auth.service';

@Component({
  selector: 'app-inicio',
  imports: [AsyncPipe, RouterLink],
  templateUrl: './inicio.component.html',
  styleUrl: './inicio.component.css'
})
export class InicioComponent {
  sessionMessage = '';

  constructor(readonly auth: AuthService) {}

  logout(): void {
    this.sessionMessage = '';
    this.auth.logout().subscribe({
      error: (_error: HttpErrorResponse) => {
        this.sessionMessage = 'La sesión local se cerró, pero no se pudo confirmar el cierre con el servidor.';
      },
    });
  }
}
