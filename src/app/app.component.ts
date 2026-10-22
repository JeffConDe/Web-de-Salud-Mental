import { AsyncPipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from './core/auth.service';

@Component({
  selector: 'app-root',
  imports: [AsyncPipe, RouterLink, RouterLinkActive, RouterOutlet],
  templateUrl: './app.component.html',
  styleUrl: './app.component.css'
})
export class AppComponent implements OnInit {
  title = 'Salud Mental';
  sessionMessage = '';

  constructor(readonly auth: AuthService) {}

  ngOnInit(): void {
    this.auth.restoreSession().subscribe({
      error: () => {
        this.sessionMessage = 'No se pudo comprobar tu sesión. Revisa que el backend esté disponible.';
      },
    });
  }

  logout(): void {
    this.sessionMessage = '';
    this.auth.logout().subscribe({
      error: () => {
        this.sessionMessage = 'La sesión local se cerró, pero no se pudo confirmar el cierre con el servidor.';
      },
    });
  }
}
