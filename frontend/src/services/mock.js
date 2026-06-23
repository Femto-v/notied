// ---------------------------------------------------------------------------
// Mock backend
// ---------------------------------------------------------------------------
// The progress evaluation (4 June) grades the FRONT-END: SPA structure, forms,
// validation, and async interaction. The real PHP Slim API may not be deployed
// yet, so this module simulates it entirely in the browser with realistic
// latency. Flip USE_MOCK to false (or set VITE_API_BASE) once the backend is up.
//
// It mirrors the exact shapes the real API returns, so swapping back is a
// one-line change.
// ---------------------------------------------------------------------------

export const USE_MOCK = !import.meta.env.VITE_API_BASE

const LATENCY = [180, 420] // ms range, to make async feel real
const delay = () =>
  new Promise((res) =>
    setTimeout(res, LATENCY[0] + Math.random() * (LATENCY[1] - LATENCY[0]))
  )

const STORE_KEY = 'notied_mock_db_v2'

function seed() {
  const now = Date.now()
  return {
    users: [
      {
        id: 1,
        name: 'Fadhil Raihan',
        email: 'demo@notied.app',
        password: 'password123', // plain in mock only; real API uses bcrypt
        avatar_url: null,
        created_at: now,
      },
    ],
    boards: [],
    notes: [],
    tags: [],
    board_members: [],
    activity: [],
    _seq: { users: 2, boards: 1, notes: 1, tags: 1, members: 1, activity: 1 },
  }
}

function load() {
  const raw = localStorage.getItem(STORE_KEY)
  if (raw) {
    try { return JSON.parse(raw) } catch { /* fallthrough */ }
  }
  const fresh = seed()
  save(fresh)
  return fresh
}
function save(db) {
  localStorage.setItem(STORE_KEY, JSON.stringify(db))
}

// Fake JWT (not signed — purely so the front-end flow is exercisable).
function fakeToken(userId) {
  const payload = btoa(JSON.stringify({ sub: userId, exp: Date.now() + 86400000 }))
  return `mock.${payload}.sig`
}
function userIdFromToken() {
  const t = localStorage.getItem('notied_token') || ''
  try {
    return JSON.parse(atob(t.split('.')[1])).sub
  } catch {
    return null
  }
}

class ApiError extends Error {
  constructor(status, message, errors) {
    super(message)
    this.response = { status, data: { message, errors } }
  }
}

export const mock = {
  async register({ name, email, password }) {
    await delay()
    const db = load()
    if (db.users.some((u) => u.email === email)) {
      throw new ApiError(409, 'That email is already registered.')
    }
    const user = {
      id: db._seq.users++, name, email, password,
      avatar_url: null, created_at: Date.now(),
    }
    db.users.push(user)
    save(db)
    return { token: fakeToken(user.id), user: strip(user) }
  },

  async login({ email, password }) {
    await delay()
    const db = load()
    const user = db.users.find((u) => u.email === email)
    if (!user || user.password !== password) {
      throw new ApiError(401, 'Email or password is incorrect.')
    }
    return { token: fakeToken(user.id), user: strip(user) }
  },

  async me() {
    await delay()
    const db = load()
    const user = db.users.find((u) => u.id === userIdFromToken())
    if (!user) throw new ApiError(401, 'Session expired.')
    return strip(user)
  },

  async updateMe(payload) {
    await delay()
    const db = load()
    const user = db.users.find((u) => u.id === userIdFromToken())
    Object.assign(user, { name: payload.name ?? user.name, avatar_url: payload.avatar_url ?? user.avatar_url })
    save(db)
    return strip(user)
  },

  async listBoards() {
    await delay()
    const db = load()
    const uid = userIdFromToken()
    return db.boards
      .filter((b) => b.owner_id === uid && !b.is_archived)
      .map((b) => ({ ...b, note_count: db.notes.filter((n) => n.board_id === b.id && !n.is_archived).length }))
      .sort((a, b) => b.updated_at - a.updated_at)
  },

  async createBoard({ title, description }) {
    await delay()
    const db = load()
    const board = {
      id: db._seq.boards++, owner_id: userIdFromToken(),
      title, description: description || '', is_archived: 0,
      created_at: Date.now(), updated_at: Date.now(),
    }
    db.boards.push(board)
    logActivity(db, 'board_created', { board_id: board.id, board_title: board.title })
    save(db)
    return { ...board, note_count: 0 }
  },

  async getBoard(id) {
    await delay()
    const db = load()
    const board = db.boards.find((b) => b.id === +id)
    if (!board) throw new ApiError(404, 'Board not found.')
    if (board.owner_id !== userIdFromToken()) throw new ApiError(403, 'No access to this board.')
    const notes = db.notes.filter((n) => n.board_id === board.id && !n.is_archived)
    return { ...board, notes }
  },

  async updateBoard(id, payload) {
    await delay()
    const db = load()
    const board = db.boards.find((b) => b.id === +id)
    if (!board) throw new ApiError(404, 'Board not found.')
    Object.assign(board, payload, { updated_at: Date.now() })
    logActivity(db, 'board_updated', { board_id: board.id, board_title: board.title })
    save(db)
    return board
  },

  async deleteBoard(id) {
    await delay()
    const db = load()
    const board = db.boards.find((b) => b.id === +id)
    db.boards = db.boards.filter((b) => b.id !== +id)
    db.notes = db.notes.filter((n) => n.board_id !== +id)
    logActivity(db, 'board_deleted', { board_id: +id, board_title: board?.title })
    save(db)
    return { deleted: true }
  },

  async listNotes(boardId) {
    await delay()
    const db = load()
    return db.notes.filter((n) => n.board_id === +boardId && !n.is_archived)
  },

  async createNote(boardId, payload) {
    await delay()
    const db = load()
    const note = {
      id: db._seq.notes++, board_id: +boardId, author_id: userIdFromToken(),
      content: payload.content ?? '', pos_x: payload.pos_x ?? 60, pos_y: payload.pos_y ?? 60,
      width: payload.width ?? 220, height: payload.height ?? 180,
      color: payload.color ?? 'yellow', is_archived: 0, updated_at: Date.now(),
    }
    db.notes.push(note)
    touchBoard(db, +boardId)
    logActivity(db, 'note_created', { board_id: +boardId, board_title: boardTitle(db, +boardId), note_id: note.id })
    save(db)
    return note
  },

  async updateNote(id, payload) {
    await delay()
    const db = load()
    const note = db.notes.find((n) => n.id === +id)
    if (!note) throw new ApiError(404, 'Note not found.')
    Object.assign(note, payload, { updated_at: Date.now() })
    touchBoard(db, note.board_id)
    logActivity(db, 'note_updated', { board_id: note.board_id, board_title: boardTitle(db, note.board_id), note_id: note.id })
    save(db)
    return note
  },

  async deleteNote(id) {
    await delay()
    const db = load()
    const note = db.notes.find((n) => n.id === +id)
    if (note) {
      note.is_archived = 1
      touchBoard(db, note.board_id)
      logActivity(db, 'note_deleted', { board_id: note.board_id, board_title: boardTitle(db, note.board_id), note_id: note.id })
    }
    save(db)
    return { deleted: true }
  },

  async search(term) {
    await delay()
    const db = load()
    const uid = userIdFromToken()
    const myBoardIds = db.boards.filter((b) => b.owner_id === uid).map((b) => b.id)
    const q = term.toLowerCase()
    return db.notes
      .filter((n) => myBoardIds.includes(n.board_id) && !n.is_archived)
      .filter((n) => n.content.toLowerCase().includes(q))
      .map((n) => ({
        ...n,
        board_title: db.boards.find((b) => b.id === n.board_id)?.title,
      }))
  },

  async listActivity(limit = 12) {
    await delay()
    const db = load()
    const uid = userIdFromToken()
    const names = Object.fromEntries(db.users.map((u) => [u.id, u.name]))
    return (db.activity || [])
      .filter((a) => a.user_id === uid)
      .sort((a, b) => b.created_at - a.created_at)
      .slice(0, limit)
      .map((a) => ({ ...a, user_name: names[a.user_id] ?? 'Someone' }))
  },
}

function strip(user) {
  const { password, ...rest } = user
  return rest
}
function touchBoard(db, boardId) {
  const b = db.boards.find((x) => x.id === boardId)
  if (b) b.updated_at = Date.now()
}
function boardTitle(db, boardId) {
  return db.boards.find((b) => b.id === boardId)?.title ?? null
}

// Append an activity entry. `type` drives the verb/icon on the dashboard.
// We snapshot board_title so the entry still reads correctly after the
// board is later renamed or deleted.
function logActivity(db, type, { board_id = null, board_title = null, note_id = null } = {}) {
  if (!db.activity) db.activity = []
  if (!db._seq.activity) db._seq.activity = 1
  db.activity.push({
    id: db._seq.activity++,
    user_id: userIdFromToken(),
    type,
    board_id,
    board_title,
    note_id,
    created_at: Date.now(),
  })
}

export function resetMockDb() {
  localStorage.removeItem(STORE_KEY)
}
