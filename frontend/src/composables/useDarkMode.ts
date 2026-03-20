import { ref, computed, watch, onMounted } from 'vue'

const THEME_KEY = 'taichu-theme'
const DARK_THEME = 'dark'
const LIGHT_THEME = 'light'

type Theme = 'light' | 'dark'

// 全局主题状态
const currentTheme = ref<Theme>(LIGHT_THEME)

/**
 * 深色模式管理Hook
 */
export function useDarkMode() {
  const isDark = computed(() => currentTheme.value === DARK_THEME)

  /**
   * 初始化主题
   */
  const initTheme = () => {
    // 优先从localStorage获取
    const savedTheme = localStorage.getItem(THEME_KEY) as Theme

    if (savedTheme) {
      setTheme(savedTheme)
      return
    }

    // 其次从系统偏好获取
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
    setTheme(prefersDark ? DARK_THEME : LIGHT_THEME)
  }

  /**
   * 设置主题
   */
  const setTheme = (theme: Theme) => {
    currentTheme.value = theme
    localStorage.setItem(THEME_KEY, theme)
    updateThemeClass(theme)
  }

  /**
   * 切换主题
   */
  const toggleTheme = () => {
    const newTheme = currentTheme.value === LIGHT_THEME ? DARK_THEME : LIGHT_THEME
    setTheme(newTheme)
  }

  /**
   * 更新DOM类名
   */
  const updateThemeClass = (theme: Theme) => {
    const html = document.documentElement

    if (theme === DARK_THEME) {
      html.classList.add(DARK_THEME)
      html.setAttribute('data-theme', DARK_THEME)
    } else {
      html.classList.remove(DARK_THEME)
      html.setAttribute('data-theme', LIGHT_THEME)
    }
  }

  /**
   * 监听系统主题变化
   */
  const watchSystemTheme = () => {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')

    const handleChange = (e: MediaQueryListEvent) => {
      // 只在用户未手动设置过主题时跟随系统
      if (!localStorage.getItem(THEME_KEY)) {
        setTheme(e.matches ? DARK_THEME : LIGHT_THEME)
      }
    }

    mediaQuery.addEventListener('change', handleChange)
  }

  // 组件挂载时初始化
  onMounted(() => {
    initTheme()
    watchSystemTheme()
  })

  return {
    isDark,
    currentTheme,
    setTheme,
    toggleTheme,
  }
}
