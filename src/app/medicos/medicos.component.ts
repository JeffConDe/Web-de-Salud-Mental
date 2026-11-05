import { HttpErrorResponse } from '@angular/common/http';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { apiErrorMessage } from '../core/api-error';
import { AuthService } from '../core/auth.service';
import {
  ClinicService,
  DoctorInput,
  DoctorProfile,
  DoctorSchedule,
  DoctorScheduleInput,
  Specialty,
  SpecialtyInput,
} from '../core/clinic.service';

@Component({
  selector: 'app-medicos',
  imports: [FormsModule],
  templateUrl: './medicos.component.html',
  styleUrl: './medicos.component.css',
})
export class MedicosComponent implements OnInit {
  readonly weekdays = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
  doctors: DoctorProfile[] = [];
  specialties: Specialty[] = [];
  search = '';
  isLoading = false;
  isSaving = false;
  editingDoctorId: number | null = null;
  editingSpecialtyId: number | null = null;
  schedulingDoctorId: number | null = null;
  errorMessage = '';
  successMessage = '';
  doctorForm = this.emptyDoctorForm();
  specialtyForm = this.emptySpecialtyForm();
  scheduleForm = this.emptyScheduleForm();

  constructor(
    readonly auth: AuthService,
    private readonly clinicService: ClinicService,
  ) {}

  get isAdministrator(): boolean {
    return this.auth.currentUser?.role === 'administrator';
  }

  ngOnInit(): void {
    this.loadData();
  }

  loadData(): void {
    this.isLoading = true;
    this.errorMessage = '';
    this.clinicService.listSpecialties().subscribe({
      next: ({ data }) => {
        this.specialties = data;
        this.clinicService.listDoctors(this.search).subscribe({
          next: ({ data: doctors }) => {
            this.doctors = doctors;
            this.isLoading = false;
          },
          error: (error: HttpErrorResponse) => this.fail(error, 'No se pudo cargar el directorio de médicos.'),
        });
      },
      error: (error: HttpErrorResponse) => this.fail(error, 'No se pudieron cargar las especialidades.'),
    });
  }

  searchDoctors(): void {
    this.loadData();
  }

  saveSpecialty(): void {
    if (this.isSaving) {
      return;
    }

    this.isSaving = true;
    this.errorMessage = '';
    const request = this.editingSpecialtyId === null
      ? this.clinicService.createSpecialty(this.specialtyForm)
      : this.clinicService.updateSpecialty(this.editingSpecialtyId, this.specialtyForm);

    request.subscribe({
      next: () => {
        this.successMessage = this.editingSpecialtyId === null ? 'Especialidad creada.' : 'Especialidad actualizada.';
        this.cancelSpecialtyEdit();
        this.isSaving = false;
        this.loadData();
      },
      error: (error: HttpErrorResponse) => this.fail(error, 'No se pudo guardar la especialidad.'),
    });
  }

  editSpecialty(specialty: Specialty): void {
    this.editingSpecialtyId = specialty.id;
    this.specialtyForm = { name: specialty.name, description: specialty.description ?? '' };
    this.errorMessage = '';
  }

  cancelSpecialtyEdit(): void {
    this.editingSpecialtyId = null;
    this.specialtyForm = this.emptySpecialtyForm();
  }

  archiveSpecialty(specialty: Specialty): void {
    if (!window.confirm(`¿Archivar la especialidad ${specialty.name}?`)) {
      return;
    }

    this.clinicService.archiveSpecialty(specialty.id).subscribe({
      next: () => {
        this.successMessage = 'Especialidad archivada.';
        this.loadData();
      },
      error: (error: HttpErrorResponse) => this.fail(error, 'No se pudo archivar la especialidad.'),
    });
  }

  activateSpecialty(specialty: Specialty): void {
    this.clinicService.updateSpecialty(specialty.id, {
      name: specialty.name,
      description: specialty.description ?? '',
      active: true,
    }).subscribe({
      next: () => {
        this.successMessage = 'Especialidad activada.';
        this.loadData();
      },
      error: (error: HttpErrorResponse) => this.fail(error, 'No se pudo activar la especialidad.'),
    });
  }

  saveDoctor(): void {
    if (this.isSaving) {
      return;
    }

    this.isSaving = true;
    this.errorMessage = '';
    const request = this.editingDoctorId === null
      ? this.clinicService.createDoctor(this.doctorForm)
      : this.clinicService.updateDoctor(this.editingDoctorId, this.doctorForm);

    request.subscribe({
      next: () => {
        this.successMessage = this.editingDoctorId === null ? 'Médico registrado correctamente.' : 'Perfil médico actualizado.';
        this.cancelDoctorEdit();
        this.isSaving = false;
        this.loadData();
      },
      error: (error: HttpErrorResponse) => this.fail(error, 'No se pudo guardar el perfil médico.'),
    });
  }

  editDoctor(doctor: DoctorProfile): void {
    this.editingDoctorId = doctor.id;
    this.doctorForm = {
      name: doctor.name,
      email: doctor.email,
      specialty_id: doctor.specialty.id,
      license_number: doctor.license_number,
      biography: doctor.biography ?? '',
      active: doctor.active,
    };
    this.errorMessage = '';
  }

  toggleDoctor(doctor: DoctorProfile): void {
    const action = doctor.active ? 'desactivar' : 'activar';
    if (!window.confirm(`¿Deseas ${action} el perfil de ${doctor.name}?`)) {
      return;
    }

    this.clinicService.updateDoctor(doctor.id, {
      name: doctor.name,
      email: doctor.email,
      specialty_id: doctor.specialty.id,
      license_number: doctor.license_number,
      biography: doctor.biography ?? '',
      active: !doctor.active,
    }).subscribe({
      next: () => {
        this.successMessage = `Perfil de ${doctor.name} ${doctor.active ? 'desactivado' : 'activado'}.`;
        this.loadData();
      },
      error: (error: HttpErrorResponse) => this.fail(error, 'No se pudo cambiar el estado del perfil.'),
    });
  }

  cancelDoctorEdit(): void {
    this.editingDoctorId = null;
    this.doctorForm = this.emptyDoctorForm();
  }

  openScheduleForm(doctorId: number): void {
    this.schedulingDoctorId = this.schedulingDoctorId === doctorId ? null : doctorId;
    this.scheduleForm = this.emptyScheduleForm();
    this.errorMessage = '';
  }

  saveSchedule(doctorId: number): void {
    if (this.isSaving) {
      return;
    }

    this.isSaving = true;
    this.errorMessage = '';
    this.clinicService.createSchedule(doctorId, this.scheduleForm).subscribe({
      next: () => {
        this.successMessage = 'Horario semanal agregado.';
        this.schedulingDoctorId = null;
        this.scheduleForm = this.emptyScheduleForm();
        this.isSaving = false;
        this.loadData();
      },
      error: (error: HttpErrorResponse) => this.fail(error, 'No se pudo agregar el horario. Revisa que no se cruce con otro bloque.'),
    });
  }

  deleteSchedule(doctor: DoctorProfile, schedule: DoctorSchedule): void {
    if (!window.confirm(`¿Eliminar el horario del ${this.weekdays[schedule.day_of_week]}?`)) {
      return;
    }

    this.clinicService.deleteSchedule(doctor.id, schedule.id).subscribe({
      next: () => {
        this.successMessage = 'Horario eliminado.';
        this.loadData();
      },
      error: (error: HttpErrorResponse) => this.fail(error, 'No se pudo eliminar el horario.'),
    });
  }

  canManageSchedules(doctor: DoctorProfile): boolean {
    const user = this.auth.currentUser;
    return user?.role === 'administrator' || (user?.role === 'doctor' && user.id === doctor.user_id);
  }

  private fail(error: HttpErrorResponse, fallback: string): void {
    this.errorMessage = apiErrorMessage(error, fallback);
    this.isLoading = false;
    this.isSaving = false;
  }

  private emptyDoctorForm(): DoctorInput {
    return { name: '', email: '', password: '', password_confirmation: '', specialty_id: 0, license_number: '', biography: '', active: true };
  }

  private emptySpecialtyForm(): SpecialtyInput {
    return { name: '', description: '' };
  }

  private emptyScheduleForm(): DoctorScheduleInput {
    return { day_of_week: 1, start_time: '09:00', end_time: '13:00', slot_duration_minutes: 30, active: true };
  }
}
