import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_URL } from './api-url';

export interface Patient {
  id: number;
  document: string;
  name: string;
  phone: string | null;
  email: string | null;
}

export interface PatientInput {
  document: string;
  name: string;
  phone: string;
  email: string;
}

export interface PatientPage {
  data: Patient[];
  current_page: number;
  last_page: number;
  total: number;
}

@Injectable({ providedIn: 'root' })
export class PatientService {
  constructor(private readonly http: HttpClient) {}

  list(search = '', page = 1): Observable<PatientPage> {
    let params = new HttpParams().set('page', page).set('per_page', 20);
    if (search) {
      params = params.set('search', search);
    }

    return this.http.get<PatientPage>(`${API_URL}/patients`, { params });
  }

  create(patient: PatientInput): Observable<{ patient: Patient }> {
    return this.http.post<{ patient: Patient }>(`${API_URL}/patients`, patient);
  }

  update(id: number, patient: PatientInput): Observable<{ patient: Patient }> {
    return this.http.put<{ patient: Patient }>(`${API_URL}/patients/${id}`, patient);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_URL}/patients/${id}`);
  }
}
