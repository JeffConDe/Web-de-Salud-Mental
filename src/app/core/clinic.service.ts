import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_URL } from './api-url';

export interface Specialty {
  id: number;
  name: string;
  description: string | null;
  active: boolean;
}

export interface SpecialtyInput {
  name: string;
  description: string;
  active?: boolean;
}

export interface DoctorSchedule {
  id: number;
  day_of_week: number;
  start_time: string;
  end_time: string;
  slot_duration_minutes: number;
  active: boolean;
}

export interface DoctorScheduleInput {
  day_of_week: number;
  start_time: string;
  end_time: string;
  slot_duration_minutes: number;
  active: boolean;
}

export interface DoctorProfile {
  id: number;
  user_id: number;
  name: string;
  email: string;
  specialty: { id: number; name: string };
  license_number: string;
  biography: string | null;
  active: boolean;
  schedules: DoctorSchedule[];
}

export interface DoctorInput {
  name: string;
  email: string;
  password?: string;
  password_confirmation?: string;
  specialty_id: number;
  license_number: string;
  biography: string;
  active: boolean;
}

@Injectable({ providedIn: 'root' })
export class ClinicService {
  constructor(private readonly http: HttpClient) {}

  listSpecialties(): Observable<{ data: Specialty[] }> {
    return this.http.get<{ data: Specialty[] }>(`${API_URL}/specialties`);
  }

  createSpecialty(specialty: SpecialtyInput): Observable<{ specialty: Specialty }> {
    return this.http.post<{ specialty: Specialty }>(`${API_URL}/admin/specialties`, specialty);
  }

  updateSpecialty(id: number, specialty: SpecialtyInput & { active?: boolean }): Observable<{ specialty: Specialty }> {
    return this.http.put<{ specialty: Specialty }>(`${API_URL}/admin/specialties/${id}`, specialty);
  }

  archiveSpecialty(id: number): Observable<void> {
    return this.http.delete<void>(`${API_URL}/admin/specialties/${id}`);
  }

  listDoctors(search = ''): Observable<{ data: DoctorProfile[] }> {
    let params = new HttpParams();
    if (search.trim()) {
      params = params.set('search', search.trim());
    }

    return this.http.get<{ data: DoctorProfile[] }>(`${API_URL}/doctors`, { params });
  }

  createDoctor(doctor: DoctorInput): Observable<{ doctor: DoctorProfile }> {
    return this.http.post<{ doctor: DoctorProfile }>(`${API_URL}/admin/doctors`, doctor);
  }

  updateDoctor(id: number, doctor: DoctorInput): Observable<{ doctor: DoctorProfile }> {
    return this.http.put<{ doctor: DoctorProfile }>(`${API_URL}/admin/doctors/${id}`, doctor);
  }

  createSchedule(doctorId: number, schedule: DoctorScheduleInput): Observable<{ schedule: DoctorSchedule }> {
    return this.http.post<{ schedule: DoctorSchedule }>(`${API_URL}/doctors/${doctorId}/schedules`, schedule);
  }

  deleteSchedule(doctorId: number, scheduleId: number): Observable<void> {
    return this.http.delete<void>(`${API_URL}/doctors/${doctorId}/schedules/${scheduleId}`);
  }
}
