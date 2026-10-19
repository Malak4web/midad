// Hand-written fetch client for the Laravel API (Sanctum SPA cookies + CSRF).

export class ApiError extends Error {
  constructor(status, message, errors = {}) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

function readCookie(name) {
  const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'))
  return match ? decodeURIComponent(match[1]) : null
}

let csrfReady = null
export function ensureCsrf() {
  csrfReady ||= fetch('/sanctum/csrf-cookie', { credentials: 'include' }).catch(() => {
    csrfReady = null
  })
  return csrfReady
}

export async function http(path, { method = 'GET', body, signal } = {}) {
  const unsafe = method !== 'GET' && method !== 'HEAD'
  if (unsafe) await ensureCsrf()
  const headers = { Accept: 'application/json' }
  if (body !== undefined) headers['Content-Type'] = 'application/json'
  const xsrf = readCookie('XSRF-TOKEN')
  if (unsafe && xsrf) headers['X-XSRF-TOKEN'] = xsrf

  let res
  try {
    res = await fetch('/api' + path, {
      method,
      headers,
      body: body === undefined ? undefined : JSON.stringify(body),
      credentials: 'include',
      signal,
    })
  } catch (e) {
    if (e.name === 'AbortError') throw e
    throw new ApiError(0, 'تعذّر الاتصال بالخادم. تأكد من الإنترنت وحاول مرة أخرى.')
  }

  const payload = res.status === 204 ? null : await res.json().catch(() => null)
  if (!res.ok) {
    throw new ApiError(res.status, payload?.message || 'حدث خطأ غير متوقع. حاول مرة أخرى بعد قليل.', payload?.errors || {})
  }
  return payload
}
