<template>
  <transition
    name="page"
    mode="out-in"
    @before-enter="beforeEnter"
    @enter="enter"
    @leave="leave"
  >
    <slot />
  </transition>
</template>

<script setup>
const beforeEnter = (el) => {
  el.style.opacity = 0
  el.style.transform = 'translateY(20px)'
}

const enter = (el, done) => {
  el.offsetHeight // trigger reflow
  el.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)'
  el.style.opacity = 1
  el.style.transform = 'translateY(0)'
  setTimeout(done, 500)
}

const leave = (el, done) => {
  el.style.transition = 'all 0.3s ease'
  el.style.opacity = 0
  el.style.transform = 'translateY(-20px)'
  setTimeout(done, 300)
}
</script>

<style scoped>
.page-enter-active,
.page-leave-active {
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.page-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
</style>
