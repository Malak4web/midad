import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { fileURLToPath, URL } from 'node:url'

// FE-51: the hero headline is Amiri Bold; fetch it with the CSS instead of after it
const preloadFonts = (patterns) => ({
  name: 'midad-preload-fonts',
  transformIndexHtml(html, ctx) {
    if (!ctx.bundle) return html
    return Object.keys(ctx.bundle)
      .filter((f) => f.endsWith('.woff2') && patterns.some((re) => re.test(f)))
      .map((f) => ({ tag: 'link', attrs: { rel: 'preload', as: 'font', type: 'font/woff2', href: '/' + f, crossorigin: '' }, injectTo: 'head' }))
  },
})

export default defineConfig({
  plugins: [vue(), tailwindcss(), preloadFonts([/amiri-arabic-700/])],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  server: {
    port: 5391,
    strictPort: true,
    proxy: {
      '/api': 'http://127.0.0.1:8391',
      '/sanctum': 'http://127.0.0.1:8391',
      '/storage': 'http://127.0.0.1:8391',
    },
  },
  test: {
    environment: 'jsdom',
    include: ['tests/unit/**/*.test.js'],
  },
})
