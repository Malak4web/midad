// TST-25: load on staging (never on production, never on `php artisan serve`).
//   k6 run -e BASE=https://staging.midad.example loadtest/k6-store.js
// Browsing traffic (home, category, product, search) with a smaller share that
// adds to cart and places a cash-on-delivery order. Thresholds fail the run.
//
// Before running: staging must have PAYMENT_DRIVER=fake, CHECKOUT_PER_MINUTE=100000
// (all traffic comes from one IP; the default 10/min would answer 429), and enough stock
// (php artisan tinker → ProductVariant::query()->update(['stock' => 100000])).
// Orders created here are test data: wipe staging after.
// Search (60/min) and cart (120/min) are also limited per IP, on purpose. Run the
// browse scenario from several load generators (or k6 cloud, which spreads IPs);
// from one machine, any 429 in the report means the limiter, not the server, said no.
import http from 'k6/http'
import { check, group, sleep } from 'k6'

const BASE = __ENV.BASE || 'http://localhost:8391'
const SLUGS = ['her-blossom', 'victory-elixir', 'ocean-wave', 'white-cream', 'black-oud-velvet', 'qahwa-fusion']
const SEARCHES = ['عود', 'شانيل', 'فانيليا', 'كريد', 'بخور']
const JSON_HEADERS = { Accept: 'application/json', 'Content-Type': 'application/json' }

export const options = {
  scenarios: {
    browse: { executor: 'ramping-vus', exec: 'browse', startVUs: 0, stages: [{ duration: '1m', target: 50 }, { duration: '3m', target: 50 }, { duration: '30s', target: 0 }] },
    buy: { executor: 'constant-arrival-rate', exec: 'buy', rate: 2, timeUnit: '1s', duration: '4m', preAllocatedVUs: 10, maxVUs: 30, startTime: '30s' },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    'http_req_duration{kind:page}': ['p(95)<800'],
    'http_req_duration{kind:api}': ['p(95)<400'],
    'http_req_duration{kind:search}': ['p(95)<500'],
    'http_req_duration{kind:checkout}': ['p(95)<1500'],
    checks: ['rate>0.99'],
  },
}

const pick = (a) => a[Math.floor(Math.random() * a.length)]

export function browse() {
  group('home', () => {
    check(http.get(`${BASE}/`, { tags: { kind: 'page' } }), { 'home 200': (r) => r.status === 200 })
    check(http.get(`${BASE}/api/home`, { headers: JSON_HEADERS, tags: { kind: 'api' } }), { 'home api 200': (r) => r.status === 200 })
  })
  sleep(1 + Math.random() * 2)
  group('category', () => {
    const cat = pick(['men', 'women', 'oriental'])
    check(http.get(`${BASE}/api/products?category=${cat}&per_page=24`, { headers: JSON_HEADERS, tags: { kind: 'api' } }), { 'category 200': (r) => r.status === 200 })
  })
  sleep(1 + Math.random() * 2)
  group('product', () => {
    const slug = pick(SLUGS)
    check(http.get(`${BASE}/p/${slug}`, { tags: { kind: 'page' } }), { 'product page 200': (r) => r.status === 200 })
    check(http.get(`${BASE}/api/products/${slug}`, { headers: JSON_HEADERS, tags: { kind: 'api' } }), { 'product api 200': (r) => r.status === 200 })
  })
  sleep(1 + Math.random() * 2)
  group('search', () => {
    check(http.get(`${BASE}/api/search?q=${encodeURIComponent(pick(SEARCHES))}`, { headers: JSON_HEADERS, tags: { kind: 'search' } }), { 'search 200': (r) => r.status === 200 })
  })
  sleep(2 + Math.random() * 3)
}

export function buy() {
  // a guest session: the cart lives in the midad_cart cookie k6 keeps per VU
  const jar = http.cookieJar()
  jar.clear(BASE)
  const add = http.post(`${BASE}/api/cart/items`, JSON.stringify({ slug: pick(SLUGS), size_ml: 30, quantity: 1 }), { headers: JSON_HEADERS, tags: { kind: 'api' } })
  check(add, { 'added to cart': (r) => r.status === 200 || r.status === 201 })
  const phone = '010' + String(Math.floor(10000000 + Math.random() * 89999999))
  const order = http.post(
    `${BASE}/api/checkout`,
    JSON.stringify({ customer_name: 'حِمل اختبار', phone, governorate_id: 1, city: 'مدينة نصر', street: 'شارع الاختبار ١', payment_method: 'cod' }),
    { headers: JSON_HEADERS, tags: { kind: 'checkout' } },
  )
  check(order, { 'order placed': (r) => r.status === 201 })
}
