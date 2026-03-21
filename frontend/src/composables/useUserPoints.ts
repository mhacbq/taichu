import { ref, computed } from 'vue'
import { getPointsBalance } from '../api'

export function useUserPoints() {
  const isLoggedIn = ref(false)
  const userPoints = ref(null)
  const isFirstBaziEligible = ref(null)

  const resolveFirstBaziFlag = (flag) => {
    if (flag === null || flag === undefined) return null
    if (typeof flag === 'boolean') return flag
    if (typeof flag === 'number') return flag === 1
    if (typeof flag === 'string') return flag === '1' || flag.toLowerCase() === 'true'
    return null
  }

  const formatDisplayValue = (value: number | null) => {
    if (value === null || value === undefined) return '--'
    return value.toLocaleString('zh-CN')
  }

  const formattedUserPoints = computed(() => formatDisplayValue(userPoints.value))

  const baziOfferState = computed(() => {
    if (!isLoggedIn.value) return 'guest'
    if (isFirstBaziEligible.value == null) return 'loading'
    return isFirstBaziEligible.value ? 'free' : 'priced'
  })

  const loadUserPoints = async () => {
    const token = localStorage.getItem('token')
    if (!token) {
      isLoggedIn.value = false
      userPoints.value = null
      isFirstBaziEligible.value = null
      return
    }

    isLoggedIn.value = true
    try {
      const response = await getPointsBalance()
      if (response.code === 200) {
        userPoints.value = response.data.balance
        isFirstBaziEligible.value = resolveFirstBaziFlag(response.data?.first_bazi)
      } else {
        userPoints.value = null
        isFirstBaziEligible.value = null
      }
    } catch (error) {
      userPoints.value = null
      isFirstBaziEligible.value = null
    }
  }

  const refreshHomeAccountState = () => {
    loadUserPoints()
  }

  return {
    isLoggedIn,
    userPoints,
    formattedUserPoints,
    isFirstBaziEligible,
    baziOfferState,
    loadUserPoints,
    refreshHomeAccountState,
  }
}
