# Notied

A web-based sticky-note board. Create boards, drop notes on a canvas, drag them
around, resize them, colour them, and write in Markdown.

## Repository layout

```
notied/
├── frontend/        Vue 3 SPA
└── backend/         PHP Slim 4 REST API
```

## Stack

**Frontend**

- Vue 3 (Composition API) + Vite
- Vue Router, Pinia, Tailwind CSS
- Axios with a JWT request interceptor
- marked for Markdown rendering inside notes
- Built-in in-browser mock backend (no server needed for demos)

**Backend**

- PHP 8.1+ with Slim 4
- PHP-DI for dependency injection
- Firebase JWT for token signing
- PDO + MySQL 8

---

## Frontend setup

Requires Node.js 18+.

```bash
cd frontend
npm install
npm run dev
```

Open http://localhost:5173.

The app ships with a built-in mock backend that runs entirely in the browser, so
you can use every screen without the PHP API running. Sign in with:

```
email:    demo@notied.app
password: password123
```

Accounts and notes from the mock persist in `localStorage`.

```bash
npm run build      # output goes to frontend/dist/
npm run preview    # serve the built files locally
```

---

## Backend setup

Requires PHP 8.1+, Composer, and a running MySQL 8 instance.

### 1. Create the database

```sql
CREATE DATABASE notied CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Run the schema

```bash
mysql -u root -p notied < backend/database/schema.sql
```

### 3. Configure environment

```bash
cp backend/.env.example backend/.env
```

Edit `backend/.env` and fill in your values:

```
APP_ENV=development
JWT_SECRET=<random string, at least 32 characters>

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=notied
DB_USER=root
DB_PASS=
```

Generate a secure `JWT_SECRET`:

```bash
openssl rand -base64 32
```

### 4. Install PHP dependencies

```bash
cd backend
composer install
```

### 5. Start the dev server

```bash
php -S localhost:8080 -t public
```

The API is now available at `http://localhost:8080/api`.

---

## Connecting the frontend to the real backend

By default the frontend routes all calls to the in-browser mock. To point it at
the PHP backend, create `frontend/.env` with:

```
VITE_API_BASE=http://localhost:8080/api
```

Alternatively, leave `VITE_API_BASE` unset and start the Vite dev server. It
automatically proxies `/api` requests to `http://localhost:8080` (configured in
`frontend/vite.config.js`), so no extra env variable is needed during local development.

---

## Project layout

```
frontend/
  src/
    assets/main.css          Tailwind setup and design tokens
    components/              Reusable UI (nav, modal, sticky note, toasts, etc.)
    composables/
      useForm.js             Form state and validation
      useDraggable.js        Drag and resize for notes (pointer events)
      useNoteColors.js       Note colour palette
    router/index.js          Routes and auth guards
    services/
      http.js                Axios instance, JWT interceptor, 401 handling
      api.js                 API endpoint wrappers (mock or real)
      mock.js                In-browser mock backend
    stores/
      auth.js                Token and current user
      toast.js               Notifications
    views/                   One component per page

backend/
  public/index.php           Slim bootstrap (web root)
  src/
    Controllers/             One controller per resource group
    Middleware/
      AuthMiddleware.php     JWT verification, injects user_id into request
      CorsMiddleware.php     CORS headers for local development
  routes/api.php             All route definitions
  database/schema.sql        MySQL table definitions
```

---

## Pages

| Path                  | What it does                                      |
| --------------------- | ------------------------------------------------- |
| `/login`              | Sign in                                           |
| `/register`           | Create an account                                 |
| `/dashboard`          | Board gallery, create/delete boards, activity     |
| `/board/:id`          | The note canvas: drag, resize, colour, edit notes |
| `/board/:id/settings` | Rename a board, manage collaborators, delete      |
| `/profile`            | Update display name and password                  |

Routes under auth redirect to `/login` when there is no valid token. When the
API returns 401, the auth store clears the session and sends the user back to login.

---

## API endpoints

All protected routes require `Authorization: Bearer <token>`.

```
POST   /api/auth/register
POST   /api/auth/login

GET    /api/me
PUT    /api/me
PUT    /api/me/password

GET    /api/boards
POST   /api/boards
GET    /api/boards/{id}
PUT    /api/boards/{id}
DELETE /api/boards/{id}

GET    /api/boards/{id}/notes
POST   /api/boards/{id}/notes
PUT    /api/notes/{id}
DELETE /api/notes/{id}

GET    /api/search?q=
GET    /api/activity?limit=
```

---

## Notes

- Mock data lives in `localStorage` under `notied_mock_db_v2`. Clear site data to
  reset it, or call `resetMockDb()` from `frontend/src/services/mock.js`.
- Google sign-in and live collaborator invites are placeholders for the future.
