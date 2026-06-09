# Notied

A web-based sticky-note board. Create boards, drop notes on a canvas, drag them
around, resize them, colour them, and write in Markdown.

## Stack

- Vue 3 (Composition API) + Vite
- Vue Router for client-side routing
- Pinia for state (auth, toasts)
- Tailwind CSS
- Axios for HTTP, with a JWT request interceptor
- marked for Markdown rendering inside notes

## Running it locally

You need Node.js 18+ installed.

```bash
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

You can also register a new account; accounts and notes persist in localStorage.

To build for production:

```bash
npm run build      # output goes to dist/
npm run preview    # serve the built files locally
```

## Connecting to the real backend

By default there's no API base set, so calls go to the mock. To use the live
Slim API instead, copy `.env.example` to `.env` and set the base URL:

```
VITE_API_BASE=http://localhost:8080/api
```

During local development you can also leave `VITE_API_BASE` unset and rely on the
Vite dev proxy, which forwards `/api` to `http://localhost:8080` (configurable in
`vite.config.js`).

Every API call goes through the wrappers in `src/services/api.js`. Those wrappers
return the same data whether they hit the mock or the real server, so switching
between them doesn't require touching any component code.

## Project layout

```
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
    api.js                 API endpoint wrappers
    mock.js                In-browser mock backend
  stores/
    auth.js                Token and current user
    toast.js               Notifications
  views/                   One component per page
```

## Pages

| Path                  | What it does                                      |
|-----------------------|---------------------------------------------------|
| `/login`              | Sign in                                           |
| `/register`           | Create an account                                 |
| `/dashboard`          | Board gallery, create/delete boards, search       |
| `/board/:id`          | The note canvas: drag, resize, colour, edit notes |
| `/board/:id/settings` | Rename a board, manage collaborators, delete      |
| `/profile`            | Update display name and password                  |

Routes under auth redirect to `/login` when there's no valid token. When the API
returns 401, the auth store clears the session and sends the user back to login.

## API endpoints used

```
POST   /auth/register
POST   /auth/login
GET    /boards
POST   /boards
GET    /boards/{id}
PUT    /boards/{id}
DELETE /boards/{id}
GET    /boards/{id}/notes
POST   /boards/{id}/notes
PUT    /notes/{id}
DELETE /notes/{id}
GET    /search?q=
```

All protected routes expect an `Authorization: Bearer <token>` header.

## Notes

- Mock data lives in localStorage under `notied_mock_db`. Clear site data to
  reset it, or call `resetMockDb()` from `src/services/mock.js`.
- Google sign-in and live collaborator invites are placeholders for now.
