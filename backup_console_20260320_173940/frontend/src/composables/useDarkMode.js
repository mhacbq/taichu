import { ref, watch, onMounted } from 'vue'

const isDark = ref(false)

export function useDarkMode() {
  // 切换暗黑模式
  const toggleDark = () => {
    isDark.value = !isDark.value
    updateDarkMode()
  }

  // 设置暗黑模式
  const setDarkMode = (dark) => {
    isDark.value = dark
    updateDarkMode()
  }

  // 更新DOM和存储
  const updateDarkMode = () => {
    const html = document.documentElement
    
    if (isDark.value) {
      html.classList.add('dark')
    } else {
      html.classList.remove('dark')
    }
    
    // 保存到localStorage
    localStorage.setItem('darkMode', isDark.value ? 'true' : 'false')
    
    // 同步到Element Plus
    if (window.__ELEMENT_PLUS__) {
      window.__ELEMENT_PLUS__.config.globalProperties.$ELEMENT = {
        size: 'default',
        zIndex: 3000
      }
    }
  }

  // 监听系统主题变化
  const listenToSystemTheme = () => {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    
    mediaQuery.addEventListener('change', (e) => {
      // 只有当用户没有手动设置时才跟随系统
      const userPreference = localStorage.getItem('darkMode')
      if (userPreference === null) {
        setDarkMode(e.matches)
      }
    })
    
    return mediaQuery
  }

  // 初始化
  onMounted(() => {
    // 检查用户偏好
    const savedMode = localStorage.getItem('darkMode')
    
    if (savedMode !== null) {
      isDark.value = savedMode === 'true'
    } else {
      // 跟随系统
      isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches
    }
    
    updateDarkMode()
    listenToSystemTheme()
  })

  return {
    isDark,
    toggleDark,
    setDarkMode
  }
}

export default useDarkMode
