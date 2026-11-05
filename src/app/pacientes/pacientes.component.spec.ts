import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting, HttpTestingController } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { PacientesComponent } from './pacientes.component';

describe('PacientesComponent', () => {
  let fixture: ComponentFixture<PacientesComponent>;
  let http: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PacientesComponent],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();

    fixture = TestBed.createComponent(PacientesComponent);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('should load the authorized patient directory', () => {
    fixture.detectChanges();

    const request = http.expectOne((req) => req.url.endsWith('/patients'));
    expect(request.request.method).toBe('GET');
    request.flush({
      data: [{ id: 1, document: '12345', name: 'Paciente de prueba', phone: null, email: null }],
      current_page: 1,
      last_page: 1,
      total: 1,
    });

    fixture.detectChanges();
    expect(fixture.componentInstance.patients.length).toBe(1);
    expect(fixture.nativeElement.textContent).toContain('Paciente de prueba');
  });
});
