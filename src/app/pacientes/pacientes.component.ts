import { HttpErrorResponse } from '@angular/common/http';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Patient, PatientInput, PatientService } from '../core/patient.service';
import { apiErrorMessage } from '../core/api-error';

@Component({
  selector: 'app-pacientes',
  imports: [FormsModule],
  templateUrl: './pacientes.component.html',
  styleUrl: './pacientes.component.css',
})
export class PacientesComponent implements OnInit {
  patients: Patient[] = [];
  search = '';
  total = 0;
  currentPage = 1;
  lastPage = 1;
  isLoading = false;
  isSaving = false;
  editingId: number | null = null;
  errorMessage = '';
  successMessage = '';
  form: PatientInput = this.emptyForm();

  constructor(private readonly patientService: PatientService) {}

  ngOnInit(): void {
    this.loadPatients();
  }

  loadPatients(): void {
    this.isLoading = true;
    this.errorMessage = '';
    this.patientService.list(this.search.trim(), this.currentPage).subscribe({
      next: (page) => {
        this.patients = page.data;
        this.total = page.total;
        this.currentPage = page.current_page;
        this.lastPage = page.last_page;
        this.isLoading = false;
      },
      error: (error: HttpErrorResponse) => {
        this.errorMessage = apiErrorMessage(error, 'No se pudo cargar la lista de pacientes.');
        this.isLoading = false;
      },
    });
  }

  searchPatients(): void {
    this.currentPage = 1;
    this.loadPatients();
  }

  changePage(page: number): void {
    this.currentPage = page;
    this.loadPatients();
  }

  save(): void {
    if (this.isSaving) {
      return;
    }

    this.isSaving = true;
    this.errorMessage = '';
    this.successMessage = '';
    const request = this.editingId === null
      ? this.patientService.create(this.form)
      : this.patientService.update(this.editingId, this.form);

    request.subscribe({
      next: () => {
        this.successMessage = this.editingId === null
          ? 'Paciente registrado correctamente.'
          : 'Datos del paciente actualizados.';
        this.cancelEdit();
        this.isSaving = false;
        this.loadPatients();
      },
      error: (error: HttpErrorResponse) => {
        this.errorMessage = apiErrorMessage(error, 'No se pudo guardar el paciente.');
        this.isSaving = false;
      },
    });
  }

  edit(patient: Patient): void {
    this.editingId = patient.id;
    this.form = {
      document: patient.document,
      name: patient.name,
      phone: patient.phone ?? '',
      email: patient.email ?? '',
    };
    this.errorMessage = '';
    this.successMessage = '';
  }

  cancelEdit(): void {
    this.editingId = null;
    this.form = this.emptyForm();
  }

  delete(patient: Patient): void {
    if (!window.confirm(`¿Archivar el registro de ${patient.name}?`)) {
      return;
    }

    this.errorMessage = '';
    this.successMessage = '';
    this.patientService.delete(patient.id).subscribe({
      next: () => {
        this.successMessage = 'Registro de paciente archivado.';
        if (this.editingId === patient.id) {
          this.cancelEdit();
        }
        this.loadPatients();
      },
      error: (error: HttpErrorResponse) => {
        this.errorMessage = apiErrorMessage(error, 'No se pudo archivar el paciente.');
      },
    });
  }

  private emptyForm(): PatientInput {
    return { document: '', name: '', phone: '', email: '' };
  }
}
