# 🏓 Ping Masters

Plataforma web para la gestión de **torneos de tenis de mesa**: inscripciones, sorteos de llaves, marcador en vivo, ranking ELO, progresión de jugadores (XP, niveles, logros), temporadas y documentos PDF.

Construida con **Laravel 12 + Inertia.js + React 19 + TypeScript + Tailwind CSS 4**. La interfaz está en español.

---

## Tabla de contenidos

- [Características](#características)
- [Stack tecnológico](#stack-tecnológico)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Variables de entorno](#variables-de-entorno)
- [Comandos útiles](#comandos-útiles)
- [Roles y permisos](#roles-y-permisos)
- [Arquitectura](#arquitectura)
- [Rutas principales](#rutas-principales)
- [Seguridad: bloqueo de IPs](#seguridad-bloqueo-de-ips)
- [Pruebas y calidad de código](#pruebas-y-calidad-de-código)
- [Despliegue](#despliegue)

---

## Características

### Torneos
- Creación y edición de torneos con **categorías (divisiones)** y **formularios de inscripción dinámicos** (constructor de campos personalizados).
- **Plantillas reutilizables** de categorías y formularios.
- Inscripción pública, incluso para invitados (sin cuenta), con correo de confirmación.
- Aprobación/rechazo de inscripciones por el organizador.
- Control de cupos y visibilidad (torneos activos/inactivos, fechas opcionales).

### Formatos de competencia
| Formato | Descripción |
|---|---|
| Eliminación simple | Llave de eliminación directa |
| Doble eliminación | Llaves de ganadores y perdedores |
| Round Robin | Todos contra todos |
| Suizo | Rondas generadas dinámicamente según desempeño |
| Grupos + eliminatoria | Fase de grupos seguida de llave final |

Incluye **siembra** (seeding), avance automático en la llave y tabla de posiciones.

### Marcador en vivo
- Consola de arbitraje punto a punto con **deshacer**, **walkover** y asignación de árbitros.
- Cálculo automático de **rotación de saque**.
- **Sistema de expedite** (ITTF): detecta el minuto 10 y pasa a saque en cada punto.
- Transmisión en tiempo real con **Laravel Reverb** (WebSockets) para que el público siga el partido en vivo.

### Retos casuales
- Partidos amistosos entre jugadores mediante **código de invitación**.
- **Apuestas de puntos** (wager) opcionales, integradas al cálculo ELO.
- Marcador en vivo, abandono y cancelación.

### Ranking y progresión
- **Rating ELO** con factor K variable (nuevo, intermedio, estándar, élite) — ver `config/rating.php`.
- **XP y 10 niveles**: Iniciante → Aprendiz → Competidor → … → Gran Maestro → Leyenda.
- **Logros** (Primera Victoria, Diez Victorias, Veterano, Racha de 5, Campeón…).
- **Temporadas**: al cerrar una temporada se archiva la clasificación y se reinicia el rating (XP, niveles y logros se conservan).
- **Perfil público de jugador** con informe de scouting, estadísticas de forma, seguidores y seguidos.

### Documentos PDF
Cronograma del torneo, llaves, hojas de puntuación (scorecards) y certificados — generados con DomPDF.

### Administración
- Gestión de usuarios (CRUD, asignación de roles, reinicio de contraseña, eliminación lógica).
- Gestión de temporadas.
- Bloqueo automático de IPs maliciosas.

---

## Stack tecnológico

**Backend**
- PHP 8.2+ · Laravel 12
- Inertia.js 2 (servidor) · Ziggy · Laravel Wayfinder
- Laravel Reverb (WebSockets)
- Spatie Laravel Permission (roles)
- barryvdh/laravel-dompdf (PDF)
- Resend (correo) · Flysystem S3 (Cloudflare R2 para avatares)

**Frontend**
- React 19 · TypeScript · Vite 6
- Tailwind CSS 4 · shadcn/ui (Radix UI) · lucide-react
- Recharts · Framer Motion · dnd-kit · canvas-confetti
- Laravel Echo + Pusher JS

**Calidad**
- PHPUnit 11 · Laravel Pint · ESLint · Prettier

---

## Requisitos

- PHP **8.2+** con extensiones `gd`, `mbstring`, `zip`, `bcmath`, `pdo_sqlite` (o `pdo_mysql`)
- Composer 2
- Node.js **20+** y npm
- SQLite (por defecto) o MySQL

---

## Instalación

```bash
# 1. Clonar el repositorio
git clone <url-del-repositorio> ping-masters
cd ping-masters

# 2. Dependencias
composer install
npm install

# 3. Entorno
cp .env.example .env
php artisan key:generate

# 4. Base de datos (SQLite por defecto)
touch database/database.sqlite
php artisan migrate --seed
```

El seeder crea los roles, niveles, logros y un usuario **super_admin**:

| Campo | Valor por defecto |
|---|---|
| Correo | `admin@pingmasters.test` |
| Contraseña | `password` |

> ⚠️ En producción define siempre `ADMIN_EMAIL` y `ADMIN_PASSWORD`.

Opcionalmente, carga datos de demostración:

```bash
php artisan db:seed --class=DemoTournamentSeeder
```

### Ejecutar en desarrollo

```bash
composer dev
```

Levanta en paralelo: servidor (`php artisan serve`), cola (`queue:listen`), logs (`pail`) y Vite.

Para el marcador en vivo con WebSockets, inicia además Reverb:

```bash
php artisan reverb:start
```

(y configura `BROADCAST_CONNECTION=reverb` junto con las variables `REVERB_*`).

Con **Laragon** también puedes servir el proyecto en `http://ping-masters.test`.

---

## Variables de entorno

Las principales (ver `.env.example` para la lista completa):

| Variable | Descripción |
|---|---|
| `APP_URL`, `APP_LOCALE` | URL y idioma de la aplicación |
| `DB_CONNECTION` | `sqlite` (defecto) o `mysql` |
| `QUEUE_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER` | Por defecto `database` |
| `BROADCAST_CONNECTION` | `log` (sin tiempo real) o `reverb` |
| `REVERB_*` / `VITE_REVERB_*` | Configuración de WebSockets |
| `MAIL_MAILER`, `RESEND_KEY` | Correo (`log` en local, `resend` en producción) |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD` | Credenciales del super_admin sembrado |
| `ADMIN_DEFAULT_RESET_PASSWORD` | Contraseña temporal al reiniciar la de un usuario |
| `R2_*` | Cloudflare R2 (almacenamiento de avatares) |
| `IP_BLOCKER_BAN_SECONDS`, `IP_BLOCKER_MAX_REQUESTS`, `IP_BLOCKER_WINDOW_SECONDS` | Bloqueo de IPs |

---

## Comandos útiles

```bash
composer dev                 # Entorno de desarrollo completo
npm run dev                  # Solo Vite
npm run build                # Compilar assets para producción
npm run build:ssr            # Compilar con SSR
npm run lint                 # ESLint (con --fix)
npm run format               # Prettier
./vendor/bin/pint            # Estilo de código PHP
php artisan test             # Ejecutar pruebas

# Bloqueo de IPs
php artisan ip-blocker:list
php artisan ip-blocker:ban {ip} --hours=24
php artisan ip-blocker:unban {ip}
```

---

## Roles y permisos

Gestionados con Spatie Laravel Permission:

| Rol | Descripción |
|---|---|
| `super_admin` | Acceso total: usuarios, temporadas, torneos |
| `organizer` | Crea y administra torneos, sorteos y plantillas |
| `referee` | Arbitra partidos desde la consola de marcador |
| `player` | Participa en torneos y retos, perfil público |

La autorización de recursos se implementa con Policies (`TournamentPolicy`, `MatchPolicy`, `CasualMatchPolicy`).

---

## Arquitectura

```
app/
├── Console/Commands/      # Comandos de bloqueo de IPs
├── Events/                # Eventos de broadcast (marcador en vivo)
├── Http/
│   ├── Controllers/       # Admin, Auth, Public, Settings y de dominio
│   └── Middleware/        # BlockMaliciousRequests, HandleInertiaRequests
├── Mail/                  # Confirmación de inscripción
├── Models/                # Eloquent (Tournament, Player, Season, ...)
├── Policies/
├── Services/
│   ├── Achievements/      # Motor de logros
│   ├── Brackets/          # Generadores de llaves, siembra, standings
│   ├── Pdf/               # Cronograma, llave, scorecard, certificado
│   ├── Progression/       # Progresión del jugador
│   ├── Ratings/           # Cálculo ELO
│   ├── Scoring/           # Marcador, saque, expedite
│   ├── Scouting/          # Informe de scouting
│   ├── Seasons/           # Cierre y reinicio de temporadas
│   └── Xp/                # XP y niveles
└── Support/               # Utilidades (BannedIps, Prerequisite)

resources/js/
├── pages/                 # Páginas Inertia (public, tournaments, games, admin...)
├── components/            # Componentes de dominio y shadcn/ui
├── layouts/               # Layouts de app, auth y público
├── hooks/                 # Hooks (apariencia, canal de partido en vivo, ...)
└── lib/                   # Utilidades

config/
├── rating.php             # Parámetros ELO
├── admin.php              # Credenciales del admin sembrado
└── ip-blocker.php         # Reglas de bloqueo de IPs
```

La lógica de negocio vive en `app/Services`, con generadores de llaves intercambiables a través de `BracketGeneratorFactory` (patrón Strategy/Factory).

---

## Rutas principales

**Públicas**

| Ruta | Descripción |
|---|---|
| `/` | Página de inicio |
| `/torneos`, `/torneos/{slug}` | Listado y detalle de torneos |
| `/torneos/{slug}/inscribirse` | Inscripción |
| `/partidos/{match}` | Partido en vivo |
| `/ranking` | Ranking ELO |
| `/jugadores/{player}` | Perfil público |
| `/temporadas`, `/temporadas/{season}` | Histórico de temporadas |

**Autenticadas**

| Ruta | Descripción |
|---|---|
| `/dashboard` | Panel principal |
| `/tournaments` | Gestión de torneos y divisiones |
| `/scoring` | Panel del árbitro |
| `/retos` | Retos casuales |
| `/plantillas/categorias`, `/plantillas/formularios` | Plantillas |
| `/admin/users`, `/admin/seasons` | Administración |
| `/settings/*` | Perfil, contraseña y apariencia |

---

## Seguridad: bloqueo de IPs

El middleware `BlockMaliciousRequests` protege la aplicación contra escáneres de vulnerabilidades:

- **Bloqueo instantáneo** (403) al solicitar rutas sospechosas (`wp-admin`, `.env`, `.git/`, `phpunit`, `api/`, etc.).
- **Límite de peticiones**: más de 120 por minuto devuelve 429 y banea la IP.
- Duración del bloqueo configurable (24 h por defecto).
- Gestión manual con los comandos `ip-blocker:*`.

Toda la configuración está en `config/ip-blocker.php`.

---

## Pruebas y calidad de código

```bash
php artisan test        # o ./vendor/bin/phpunit
```

La suite cubre generación de llaves (todos los formatos), marcador, ELO, XP y logros, temporadas, retos casuales con apuestas, inscripciones, generación de PDF, seguidores, scouting y control de roles.

GitHub Actions ejecuta `tests` y `lint` en cada push y pull request a `main` / `develop`.

---

## Despliegue

El proyecto incluye un `Dockerfile` multi-etapa (build de assets con Node 20 + runtime PHP 8.3) y `railway.json` para desplegar en **[Railway](https://railway.app)**:

- Healthcheck en `/up`.
- Ejecuta `php artisan migrate --force` y cachea configuración y rutas al arrancar.
- Variables `VITE_*` se inyectan como build args.

Checklist de producción:

1. `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` generado.
2. Base de datos MySQL y `ADMIN_EMAIL` / `ADMIN_PASSWORD` definidos.
3. `MAIL_MAILER=resend` con `RESEND_KEY`.
4. Credenciales `R2_*` para avatares.
5. Si se desea tiempo real, desplegar un servicio Reverb y configurar `BROADCAST_CONNECTION=reverb`.

---

## Licencia

Proyecto basado en el [Laravel React Starter Kit](https://github.com/laravel/react-starter-kit) (MIT).
