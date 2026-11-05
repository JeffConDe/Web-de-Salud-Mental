import { Routes } from '@angular/router';
import { InicioComponent } from './inicio/inicio.component';
import { LoginComponent } from './login/login.component';
import { RegistroComponent } from './registro/registro.component';
import { ChatComponent } from './chat/chat.component';
import { PerfilComponent } from './perfil/perfil.component';
import { authGuard } from './core/auth.guard';
import { PacientesComponent } from './pacientes/pacientes.component';
import { roleGuard } from './core/role.guard';
import { MedicosComponent } from './medicos/medicos.component';

export const routes: Routes = [
  { path: '', component: InicioComponent },
  { path: 'login', component: LoginComponent },
  { path: 'registro', component: RegistroComponent },
  { path: 'chat', component: ChatComponent, canActivate: [authGuard] },
  { path: 'perfil', component: PerfilComponent, canActivate: [authGuard] },
  {
    path: 'pacientes',
    component: PacientesComponent,
    canActivate: [authGuard, roleGuard(['administrator', 'receptionist'])],
  },
  {
    path: 'medicos',
    component: MedicosComponent,
    canActivate: [authGuard, roleGuard(['administrator', 'receptionist', 'doctor'])],
  },
  { path: '**', redirectTo: '' },
];
