import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAppStore = defineStore('app', () => {
  // State
  const sidebar = ref({
    opened: true,
    withoutAnimation: false
  })
  const device = ref('desktop')
  const size = ref(localStorage.getItem('size') || 'default')

  // Actions
  function toggleSidebar() {
    sidebar.value.opened = !sidebar.value.opened
    sidebar.value.withoutAnimation = false
  }

  function closeSidebar(withoutAnimation) {
    sidebar.value.opened = false
    sidebar.value.withoutAnimation = withoutAnimation
  }

  function openSidebar(withoutAnimation) {
    sidebar.value.opened = true
    sidebar.value.withoutAnimation = withoutAnimation
  }

  function toggleDevice(deviceType) {
    device.value = deviceType
  }

  function setSize(sizeType) {
    size.value = sizeType
    localStorage.setItem('size', sizeType)
  }

  return {
    sidebar,
    device,
    size,
    toggleSidebar,
    closeSidebar,
    openSidebar,
    toggleDevice,
    setSize
  }
})
