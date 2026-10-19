import { onBeforeUnmount, onMounted, ref } from 'vue'

// Below lg the store is a phone app with its own screens (src/mobile).
export const PHONE_QUERY = '(max-width: 1023px)'

export function usePhone() {
  const phone = ref(typeof matchMedia !== 'undefined' && matchMedia(PHONE_QUERY).matches)
  let mq
  let timer
  // switching apps remounts the screen: wait for the width to settle, so a
  // passing resize (a rotation, a full-page capture) does not throw away its state
  const on = () => {
    clearTimeout(timer)
    timer = setTimeout(() => (phone.value = mq.matches), 250)
  }
  onMounted(() => {
    mq = matchMedia(PHONE_QUERY)
    phone.value = mq.matches
    mq.addEventListener('change', on)
  })
  onBeforeUnmount(() => {
    clearTimeout(timer)
    mq?.removeEventListener('change', on)
  })
  return phone
}
