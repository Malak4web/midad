<script setup>
// One stroke family (1.5 @ 24px) for every icon in the store.
defineProps({
  name: { type: String, required: true },
  size: { type: Number, default: 20 },
})
const paths = {
  search: '<circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4.2-4.2"/>',
  bag: '<path d="M5 8h14l-1 12H6L5 8z"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8"/>',
  user: '<circle cx="12" cy="8.5" r="3.5"/><path d="M5 20c1.2-3.6 4-5 7-5s5.8 1.4 7 5"/>',
  menu: '<path d="M4 8h16M4 16h11"/>',
  close: '<path d="M6 6l12 12M18 6L6 18"/>',
  'chevron-right': '<path d="M9 5l7 7-7 7"/>',
  'chevron-left': '<path d="M15 5l-7 7 7 7"/>',
  'arrow-left': '<path d="M19 12H5M11 6l-6 6 6 6"/>',
  plus: '<path d="M12 5v14M5 12h14"/>',
  minus: '<path d="M5 12h14"/>',
  check: '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
  gift: '<rect x="4" y="9" width="16" height="11" rx="1"/><path d="M3 9h18v-3H3zM12 6v14"/><path d="M12 6c-1.5-3-5-3-5-.8C7 6 9 6 12 6zM12 6c1.5-3 5-3 5-.8C17 6 15 6 12 6z"/>',
  truck: '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="17.5" r="1.6"/><circle cx="17" cy="17.5" r="1.6"/>',
  cash: '<rect x="3" y="6" width="18" height="12" rx="1.5"/><circle cx="12" cy="12" r="2.6"/><path d="M6 9.5v5M18 9.5v5"/>',
  drop: '<path d="M12 3.5c3 4 5.5 7 5.5 10a5.5 5.5 0 0 1-11 0c0-3 2.5-6 5.5-10z"/>',
  whatsapp: '<path d="M4 20l1.2-3.6A8 8 0 1 1 8 19z"/><path d="M9 9.2c.3 1.8 2 3.6 3.9 4.2l1.2-1.1 1.8.8-.4 1.6c-3.4.2-7-3.2-7-6.6l1.6-.5.8 1.8z"/>',
  instagram: '<rect x="4" y="4" width="16" height="16" rx="4.5"/><circle cx="12" cy="12" r="3.6"/><circle cx="17" cy="7" r=".6" fill="currentColor"/>',
  tiktok: '<path d="M14 4v10.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 4c.5 2.6 2.3 4 5 4.2"/>',
  // the phone tab bar
  home: '<path d="M4 10.5L12 4l8 6.5"/><path d="M6 9v11h12V9"/><path d="M10 20v-5.5h4V20"/>',
  shop: '<rect x="4" y="4" width="6.5" height="6.5" rx="1"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1"/>',
  'arrow-right': '<path d="M5 12h14M13 6l6 6-6 6"/>',
  package: '<path d="M4 7.5L12 4l8 3.5v9L12 20l-8-3.5z"/><path d="M4 7.5L12 11l8-3.5M12 11v9"/>',
  help: '<circle cx="12" cy="12" r="8"/><path d="M9.8 9.6a2.3 2.3 0 0 1 4.4.8c0 1.6-2.2 2-2.2 3.4"/><circle cx="12" cy="16.6" r=".5" fill="currentColor"/>',
}
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" v-html="paths[name]" />
</template>
