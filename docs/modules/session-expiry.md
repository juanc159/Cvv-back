# Caducidad de sesiones y cierre global

## Propósito

Las sesiones se autentican con **Laravel Passport** (personal access tokens). Antes
**no se configuraba expiración**, así que los tokens usaban el default de Passport
(**1 año**): había ~42.500 sesiones activas y la tabla `oauth_access_tokens` crecía sin
límite (140k+ filas, ninguna purgada). Este módulo:

1. Caduca las sesiones por **tipo de usuario** (caducidad absoluta desde el login).
2. Añade un botón **"Cerrar todas las sesiones"** (solo Super Administrador).
3. Programa la **purga** de tokens revocados/expirados.

## Slice vertical

### 1. Expiración por tipo de usuario
- **Config:** [config/session_token.php](../../config/session_token.php) — horas por
  `type_user` (`student`/`teacher`/`admin`) + `default_hours`, todo por `.env`.
- **Lógica:** en `PassportAuthController::login()`, justo antes de `createToken`, se llama
  `Passport::personalAccessTokensExpireIn(now()->addHours($ttl))` según `$user->type_user`.
  Cada login es una petición nueva con un único `createToken`, así que el TTL aplica limpio
  (Passport resuelve el authorization server una vez por proceso).
- **Defaults:** alumnos **2h**, docentes **8h**, admin **8h**.
- Solo afecta a **logins nuevos**; los tokens ya emitidos conservan su vencimiento.

### 2. Cerrar todas las sesiones
- **Endpoint:** `POST /api/sessions/revoke-all` — [routes/session.php](../../routes/session.php)
  (registrado en `App\Helpers\RoutesApi::ROUTES_AUTH_API`, middleware `auth:api`).
- **Controlador:** `PassportAuthController::revokeAllSessions()`. Autoriza solo al rol
  Super Administrador (`config('session_token.super_admin_role_id')`). Hace
  `UPDATE oauth_access_tokens SET revoked=1` **excluyendo el token del propio admin**, para
  no expulsarse a sí mismo. Devuelve el número de sesiones cerradas.
- **Frontend:** ítem "Cerrar todas las sesiones" en
  `src/layouts/components/UserProfile.vue`, visible solo si `user.role_id` === SuperAdmin.
  Confirma con SweetAlert y llama al endpoint vía `useAxios`.

### 3. Purga
- `Schedule::command('passport:purge')->daily()->at('02:00')` en
  [routes/console.php](../../routes/console.php). Requiere el scheduler corriendo
  (ya hay backups diarios programados ahí).

## Contrato con el frontend
- El SPA ya maneja el `401`: al vencer el token, `useAxios` limpia la cookie, muestra
  "Sesión expirada" y redirige a Login. Acortar el TTL no requiere cambios adicionales.
- No hay refresh token: la caducidad es **absoluta**, no por inactividad. Al expirar, el
  usuario vuelve a introducir usuario/contraseña.

## Cómo extender / ajustar
- Cambiar duraciones sin tocar código: en `.env`
  `SESSION_TTL_STUDENT_HOURS`, `SESSION_TTL_TEACHER_HOURS`, `SESSION_TTL_ADMIN_HOURS`.
- Alcance del botón (por rol/curso, o expulsar 1 usuario): añadir métodos/rutas análogos.

## Pendientes / notas
- Caducidad por **inactividad** (sliding) requeriría renovar el token en cada request o
  usar refresh tokens; hoy es absoluta por decisión de diseño.
- En producción, si se usa `config:cache`/`route:cache`, re-ejecutarlos tras desplegar.

## Cómo verificar
- TTL por tipo (proceso aislado por tipo, como una petición real):
  `php artisan tinker` → fijar `Passport::personalAccessTokensExpireIn` según `type_user` y
  comprobar `expires_at` (student→2h, teacher/admin→8h).
- Endpoint: `POST /api/sessions/revoke-all` como SuperAdmin devuelve `revoked_sessions`;
  otros roles reciben 403; sin token, 401.
