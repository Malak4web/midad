<script setup>
// Authored line drawings of perfume ingredients (80×80), stroked in the
// spotlight accent. Names map to spotlight.ingredients in the admin.
const NAMES = {
  vanilla: 'الفانيليا', amber: 'العنبر', lavender: 'الخزامى', cardamom: 'الهيل',
  strawberry: 'الفراولة', blackberry: 'التوت الأسود', jasmine: 'الياسمين', musk: 'المسك',
  incense: 'البخور', tobacco: 'التبغ', oud: 'العود', resin: 'الراتنجات',
}
const ART = {
  vanilla: `<path d="M18 66C30 46 44 28 64 12"/><path d="M23 68C35 49 48 31 67 16"/><path d="M18 66c1.5 1.8 3.4 2.6 5 2"/><path d="M64 12c1.8.4 3 1.8 3 4"/><path d="M14 30c6-6 14-6 18 0-6 4-12 5-18 0z"/><path d="M24 22c2 7-1 12-6 15"/><path d="M28 33c4 2 6 6 5 10"/><circle cx="25" cy="31" r="1.6"/>`,
  amber: `<path d="M40 14c10 12 16 22 16 32a16 16 0 0 1-32 0c0-10 6-20 16-32z"/><path d="M33 44c0 6 3 10 8 11"/><path d="M60 20c4 5 6 9 6 12a6 6 0 0 1-12 0c0-3 2-7 6-12z"/><path d="M20 54c3 3.6 4.5 6.4 4.5 8.6a4.5 4.5 0 0 1-9 0c0-2.2 1.5-5 4.5-8.6z"/>`,
  lavender: `<path d="M40 74C40 50 38 32 34 10"/><path d="M40 60C46 48 52 40 60 34"/><path d="M40 58C33 50 26 46 18 44"/>${[14,20,26,32,38].map((y,i)=>`<ellipse cx="${34+i*0.9}" cy="${y}" rx="3.2" ry="2.2" transform="rotate(-20 ${34+i*0.9} ${y})"/><ellipse cx="${38+i*0.9}" cy="${y+3}" rx="3.2" ry="2.2" transform="rotate(25 ${38+i*0.9} ${y+3})"/>`).join('')}<ellipse cx="60" cy="33" rx="3" ry="2" transform="rotate(-40 60 33)"/><ellipse cx="18" cy="44" rx="3" ry="2"/>`,
  cardamom: `<path d="M22 54c-4-14 4-30 22-36 12-4 20 2 18 12-3 14-22 30-40 24z"/><path d="M26 50c2-12 12-22 30-28"/><path d="M30 54c4-10 14-18 28-22"/><path d="M24 44c4-9 12-16 24-21"/><path d="M62 30c3-2 6-2 8 0"/>`,
  strawberry: `<path d="M40 24c14 0 24 8 22 20-2 14-12 24-22 28-10-4-20-14-22-28-2-12 8-20 22-20z"/><path d="M40 24l-8-8 8 4 0-10 4 10 8-4-8 8"/>${[[32,36],[44,34],[38,46],[50,46],[28,48],[44,58],[34,58],[40,66]].map(([x,y])=>`<path d="M${x} ${y}l1 2"/>`).join('')}`,
  blackberry: `${[[34,34],[44,32],[30,44],[40,42],[50,42],[34,53],[44,52],[39,62]].map(([x,y])=>`<circle cx="${x}" cy="${y}" r="5.6"/>`).join('')}<path d="M40 27c-2-6 0-12 6-16"/><path d="M46 11c6 0 10 3 12 8-6 1-10-2-12-8z"/>`,
  jasmine: `${[0,72,144,216,288].map(a=>`<path d="M40 40c-4-8-4-18 0-26 4 8 4 18 0 26z" transform="rotate(${a} 40 40)"/>`).join('')}<circle cx="40" cy="40" r="3"/><path d="M40 43c2 12 8 22 18 30"/><path d="M50 60c6-2 10 0 12 4-6 2-10 0-12-4z"/>`,
  musk: `<path d="M20 50c-6 0-8-10 0-12 0-10 12-14 18-6 6-8 20-4 18 6 10 0 10 14 0 14H22"/><circle cx="34" cy="62" r="1.4"/><circle cx="44" cy="66" r="1.2"/><circle cx="52" cy="61" r="1.4"/><circle cx="27" cy="68" r="1"/>`,
  incense: `<path d="M24 72L46 40"/><path d="M22 74l6-2"/><circle cx="47" cy="38.5" r="2"/><path d="M48 36c-6-6 6-10 0-16s6-10 2-16"/><path d="M52 34c2-6 10-6 8-14"/><path d="M44 30c-6-2-8-8-4-12"/>`,
  tobacco: `<path d="M16 64C20 34 42 16 66 14c-2 26-20 46-50 50z"/><path d="M16 64C30 48 46 34 66 14"/><path d="M28 50l-4-12"/><path d="M38 40l-2-14"/><path d="M48 30l0-10"/><path d="M34 46l12 2"/><path d="M44 36l12 2"/>`,
  oud: `<path d="M14 52l40-30 12 8-40 30z"/><path d="M14 52l0 6 12 8 0-6"/><path d="M66 30l0 6-40 30"/><path d="M24 50c8-4 16-12 28-20"/><path d="M30 54c8-5 16-12 26-19"/><path d="M36 41c3 1 6 0 8-2"/>`,
  resin: `<path d="M30 20c8 9 12 16 12 23a12 12 0 0 1-24 0c0-7 4-14 12-23z"/><path d="M54 34c6 7 9 12 9 17a9 9 0 0 1-18 0c0-5 3-10 9-17z"/><path d="M26 44c0 4 2 6 5 7"/>`,
}
defineProps({
  name: { type: String, required: true },
  size: { type: Number, default: 72 },
  caption: { type: Boolean, default: true },
})
</script>

<template>
  <figure class="m-0 flex flex-col items-center gap-1.5">
    <svg :width="size" :height="size" viewBox="0 0 80 80" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" v-html="ART[name] || ART.resin" />
    <figcaption v-if="caption" class="text-[0.8125rem] leading-none opacity-85">{{ NAMES[name] || name }}</figcaption>
  </figure>
</template>
