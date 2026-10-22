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
- Perfil para consultar y actualizar nombre y correo.
- Chat autenticado que conserva una conversación por cuenta y responde con un mensaje automático fijo.
- Las rutas de chat y perfil requieren inicio de sesión.

Para probar en local, los mensajes se guardan en PostgreSQL. **No uses datos personales o clínicos reales**: esta configuración es de desarrollo y todavía no incluye controles de producción, política de retención ni despliegue seguro. El chat no usa IA ni ofrece atención profesional.

El token se guarda en `sessionStorage` y caduca al cerrar la pestaña; es una medida de desarrollo, no una decisión final para producción.

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
