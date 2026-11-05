import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting, HttpTestingController } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MedicosComponent } from './medicos.component';

describe('MedicosComponent', () => {
  let fixture: ComponentFixture<MedicosComponent>;
  let http: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MedicosComponent],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();

    fixture = TestBed.createComponent(MedicosComponent);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('loads the specialty and doctor directory with weekly schedules', () => {
    fixture.detectChanges();

    http.expectOne((request) => request.url.endsWith('/specialties')).flush({
      data: [{ id: 1, name: 'Psicología general', description: null, active: true }],
    });
    http.expectOne((request) => request.url.endsWith('/doctors')).flush({
      data: [{
        id: 1,
        user_id: 3,
        name: 'Médico de prueba',
        email: 'medico@example.test',
        specialty: { id: 1, name: 'Psicología general' },
        license_number: 'LIC-123',
        biography: null,
        active: true,
        schedules: [{
          id: 1,
          day_of_week: 1,
          start_time: '09:00',
          end_time: '13:00',
          slot_duration_minutes: 30,
          active: true,
        }],
      }],
    });

    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).toContain('Médico de prueba');
    expect(fixture.nativeElement.textContent).toContain('Psicología general');
    expect(fixture.nativeElement.textContent).toContain('09:00–13:00');
    expect(fixture.componentInstance.doctors[0].schedules.length).toBe(1);
  });
});
