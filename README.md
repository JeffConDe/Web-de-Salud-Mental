# Salud Mental

Aplicación de bienestar emocional en desarrollo. El repositorio contiene un frontend Angular y una API Laravel con PostgreSQL.

## Requisitos

- Node.js y npm
- Docker Desktop iniciado

No hace falta instalar PHP, Composer ni PostgreSQL directamente en el equipo.

## Iniciar el backend

Desde la raíz del repositorio, prepara la configuración local:

```powershell
Copy-Item backend\.env.example backend\.env
```

En macOS o Linux, usa `cp backend/.env.example backend/.env`.

Construye e inicia Laravel y PostgreSQL:

```bash
docker compose up --build -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

La API queda en `http://localhost:8000`. El endpoint `http://localhost:8000/api/health` verifica que responde.

Si cambias dependencias PHP, sincroniza `composer.lock` y el volumen del contenedor con:

```bash
docker compose exec app composer install
```

## Iniciar el frontend

En otra terminal:

```bash
npm install
npm start
```

Abre `http://localhost:4200/`.

## Funciones disponibles

- Registro e inicio de sesión con tokens revocables.
- Roles `administrator`, `receptionist`, `doctor` y `patient`; el registro público siempre crea pacientes.
- Gestión autenticada de pacientes (solo administración y recepción) y administración de cuentas/roles (solo administración).
- Administración de especialidades y perfiles médicos; médicos pueden gestionar sus propios horarios semanales y recepción puede consultar el directorio.
- Validación de horarios repetidos para impedir cruces activos del mismo médico y día.
- Perfil para consultar y actualizar nombre y correo.
- Chat autenticado que conserva una conversación por cuenta y responde con un mensaje automático fijo.
- Las rutas de chat y perfil requieren inicio de sesión.

Para probar en local, los mensajes se guardan en PostgreSQL. **No uses datos personales o clínicos reales**: esta configuración es de desarrollo y todavía no incluye controles de producción, política de retención ni despliegue seguro. El chat no usa IA ni ofrece atención profesional.

El token se guarda en `sessionStorage` y caduca al cerrar la pestaña; es una medida de desarrollo, no una decisión final para producción.

Para crear el primer administrador: registra una cuenta normal y asígnale el rol desde la terminal:

```bash
docker compose exec app php artisan app:assign-user-role correo@example.com administrator
```

La consola administrativa de roles todavía no tiene pantalla web; el API está en `/api/admin/users`. La gestión de pacientes está en `/pacientes`; la de médicos, especialidades y horarios está en `/medicos`. El puerto PostgreSQL local publicado por Docker es `5433` (el backend sigue usando `5432` dentro de Docker).

Para pruebas locales, crea cuentas para cada rol después de ejecutar las migraciones:

```bash
docker compose exec app php artisan db:seed --class=DevelopmentUsersSeeder
```

Todas usan la contraseña `SaludMental2026!`: `admin@saludmental.test`, `recepcion@saludmental.test`, `medico@saludmental.test` y `paciente@saludmental.test`. El seeder crea para el médico una especialidad y un perfil ficticios, pero ningún horario. Solo funciona con `APP_ENV=local`; no uses estas credenciales en producción. Ejecutarlo de nuevo restablece los datos de esas cuatro cuentas y el perfil demo del médico.

## Médicos, especialidades y horarios

Ejecuta `docker compose exec app php artisan migrate` para crear las nuevas tablas. Administración registra especialidades y médicos desde `/medicos`; crear un médico también crea su cuenta con rol `doctor`. Administración puede gestionar todos los horarios y cada médico puede administrar los propios. Recepción puede consultar el directorio y los horarios, pero no modificarlos.

Los horarios son bloques semanales recurrentes, de lunes a domingo, con duración de cita configurable entre 10 y 240 minutos. No se permiten cruces entre bloques activos del mismo día. Esta fase todavía no agenda citas ni contempla festivos, vacaciones o ausencias.

## Datos y cambios de base

Laravel crea y cambia las tablas mediante migraciones:

```bash
docker compose exec app php artisan migrate
```

Cada integrante ejecuta las migraciones en su propia base. El volumen `postgres_data` conserva datos al reiniciar; `docker compose down -v` los elimina.

## Pruebas

```bash
docker compose exec app php artisan test
npm run build
```
