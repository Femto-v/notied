import http from './http'
import { USE_MOCK, mock } from './mock'

// All app code calls these wrappers. When no real backend is configured
// (VITE_API_BASE unset), calls route to the in-memory mock instead of axios.
// The return shapes are identical, so switching to the live PHP Slim API is
// a zero-change operation for every consumer.

export const authApi = {
  register: (p) => (USE_MOCK ? mock.register(p) : http.post('/auth/register', p).then((r) => r.data)),
  login: (p) => (USE_MOCK ? mock.login(p) : http.post('/auth/login', p).then((r) => r.data)),
}

export const boardsApi = {
  list: () => (USE_MOCK ? mock.listBoards() : http.get('/boards').then((r) => r.data.boards)),
  create: (p) => (USE_MOCK ? mock.createBoard(p) : http.post('/boards', p).then((r) => r.data.board)),
  get: (id) => (USE_MOCK ? mock.getBoard(id) : http.get(`/boards/${id}`).then((r) => ({ ...r.data.board, notes: r.data.notes }))),
  update: (id, p) => (USE_MOCK ? mock.updateBoard(id, p) : http.put(`/boards/${id}`, p).then((r) => r.data.board)),
  remove: (id) => (USE_MOCK ? mock.deleteBoard(id) : http.delete(`/boards/${id}`)),
}

export const notesApi = {
  listForBoard: (bid) => (USE_MOCK ? mock.listNotes(bid) : http.get(`/boards/${bid}/notes`).then((r) => r.data.notes)),
  create: (bid, p) => (USE_MOCK ? mock.createNote(bid, p) : http.post(`/boards/${bid}/notes`, p).then((r) => r.data.note)),
  update: (id, p) => (USE_MOCK ? mock.updateNote(id, p) : http.put(`/notes/${id}`, p).then((r) => r.data.note)),
  remove: (id) => (USE_MOCK ? mock.deleteNote(id) : http.delete(`/notes/${id}`)),
}

export const searchApi = {
  query: (term) => (USE_MOCK ? mock.search(term) : http.get('/search', { params: { q: term } }).then((r) => r.data.results)),
}

export const activityApi = {
  list: (limit = 12) => (USE_MOCK ? mock.listActivity(limit) : http.get('/activity', { params: { limit } }).then((r) => r.data.activity)),
}

export const profileApi = {
  me: () => (USE_MOCK ? mock.me() : http.get('/me').then((r) => r.data.user)),
  update: (p) => (USE_MOCK ? mock.updateMe(p) : http.put('/me', p).then((r) => r.data.user)),
  changePassword: (p) => (USE_MOCK ? Promise.resolve({ ok: true }) : http.put('/me/password', p).then((r) => r.data)),
}
