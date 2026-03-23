import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getVipPackages, purchaseVip, getUserVipStatus } from '../../api/vip'
import { getVipInfo, getVipBenefits } from '../../api'
import { Star, MagicStick, Calendar, Document } from '@element-plus/icons-vue'

export function useVip() {
  const router = useRouter()

  // ===== 状态 =====
  const userInfo = ref({})
  const isVip = ref(false)
  const vipExpireTime = ref('')
  const vipPlans = ref([])
  const privileges = ref([])
  const loading = ref(true)
  const purchasing = ref(false)

  // 图标映射
  const iconMap = {
    star: Star,
    magic: MagicStick,
    calendar: Calendar,
    document: Document,
  }

  // ===== 加载用户信息 =====
  const loadUserInfo = () => {
    try {
      const stored = localStorage.getItem('userInfo')
      if (stored) {
        userInfo.value = JSON.parse(stored)
      }
    } catch {
      // noop
    }
  }

  // ===== 加载 VIP 状态 =====
  const loadVipStatus = async () => {
    try {
      const response = await getUserVipStatus()
      if (response.code === 200 && response.data) {
        isVip.value = response.data.is_vip || false
        vipExpireTime.value = response.data.expire_time || ''
      }
    } catch {
      // 降级到本地数据
      isVip.value = userInfo.value.is_vip || false
      vipExpireTime.value = userInfo.value.vip_expire_time || ''
    }
  }

  // ===== 加载 VIP 套餐列表（后端管理端控制） =====
  const loadVipPlans = async () => {
    try {
      const response = await getVipPackages()
      if (response.code === 200 && Array.isArray(response.data) && response.data.length > 0) {
        vipPlans.value = response.data.map((pkg) => ({
          id: pkg.id,
          name: pkg.name,
          price: pkg.price,
          duration: formatDuration(pkg.duration),
          points: pkg.points || 0,
          features: parseFeatures(pkg.features || pkg.description),
          recommended: pkg.is_recommended === 1 || pkg.recommended === true,
        }))
      } else {
        // 接口无数据时使用默认兜底
        vipPlans.value = getDefaultPlans()
      }
    } catch {
      // 接口异常时使用默认兜底
      vipPlans.value = getDefaultPlans()
    }
  }

  // ===== 加载 VIP 权益（后端管理端控制） =====
  const loadPrivileges = async () => {
    try {
      const response = await getVipBenefits()
      if (response.code === 200 && response.data?.features?.length > 0) {
        privileges.value = response.data.features.map((item) => ({
          icon: iconMap[item.icon] || Star,
          title: item.title,
          desc: item.desc,
        }))
      } else {
        privileges.value = getDefaultPrivileges()
      }
    } catch {
      privileges.value = getDefaultPrivileges()
    }
  }

  // ===== 格式化有效期（月数 → 可读文本） =====
  const formatDuration = (months) => {
    if (months >= 12 && months % 12 === 0) return `${months / 12}年`
    return `${months}个月`
  }

  // ===== 解析特性列表 =====
  const parseFeatures = (raw) => {
    if (Array.isArray(raw)) return raw
    if (typeof raw === 'string') {
      try {
        const parsed = JSON.parse(raw)
        if (Array.isArray(parsed)) return parsed
      } catch {
        // 按换行或逗号分割
        return raw.split(/[,，\n]/).map((s) => s.trim()).filter(Boolean)
      }
    }
    return []
  }

  // ===== 默认兜底数据 =====
  const getDefaultPlans = () => [
    {
      id: 'month',
      name: '连续包月',
      price: '29',
      duration: formatDuration(1),
      points: 500,
      features: ['每月赠送 500 积分', '解锁深度解读', '塔罗专属牌阵', '优先客服响应'],
      recommended: false,
    },
    {
      id: 'quarter',
      name: '连续包季',
      price: '68',
      duration: formatDuration(3),
      points: 1800,
      features: ['每季赠送 1800 积分', '解锁深度解读', '塔罗专属牌阵', '优先客服响应', '专属身份标识'],
      recommended: true,
    },
    {
      id: 'year',
      name: '连续包年',
      price: '198',
      duration: formatDuration(12),
      points: 8000,
      features: ['每年赠送 8000 积分', '解锁深度解读', '塔罗专属牌阵', '优先客服响应', '专属身份标识', '新功能优先体验'],
      recommended: false,
    },
  ]

  const getDefaultPrivileges = () => [
    { icon: Star, title: '海量积分赠送', desc: '开通即送大量积分，畅享全站核心功能，排盘占卜无压力。' },
    { icon: MagicStick, title: '深度解读解锁', desc: '解锁八字流年大运、性格内观等专业版深度解读内容。' },
    { icon: Calendar, title: '专属塔罗牌阵', desc: '使用 VIP 专属的高级塔罗牌阵，获得更全面的指引。' },
    { icon: Document, title: '专属身份标识', desc: '全站展示 VIP 专属尊贵标识，彰显独特身份。' },
  ]

  // ===== 购买/订阅 =====
  const handleSubscribe = async (plan) => {
    // 检查登录
    const token = localStorage.getItem('token')
    if (!token) {
      ElMessage.warning('请先登录')
      router.push({ name: 'Login', query: { redirect: '/vip' } })
      return
    }

    try {
      await ElMessageBox.confirm(
        `确认开通「${plan.name}」？将消耗 ${plan.price} 积分`,
        '确认开通',
        { confirmButtonText: '确认开通', cancelButtonText: '取消', type: 'info' }
      )
    } catch {
      return // 用户取消
    }

    purchasing.value = true
    try {
      const response = await purchaseVip({
        package_id: plan.id,
        payment_method: 'points', // 积分支付
      })

      if (response.code === 200) {
        ElMessage.success('🎉 恭喜！VIP 开通成功')
        isVip.value = true
        vipExpireTime.value = response.data?.expire_time || ''
        // 刷新本地用户信息
        await loadVipStatus()
      } else {
        ElMessage.error(response.message || '开通失败，请稍后重试')
      }
    } catch (error) {
      const msg = error?.response?.data?.message || '网络异常，请稍后重试'
      ElMessage.error(msg)
    } finally {
      purchasing.value = false
    }
  }

  // ===== 初始化 =====
  const init = async () => {
    loading.value = true
    loadUserInfo()
    // 并行加载
    await Promise.allSettled([loadVipStatus(), loadVipPlans(), loadPrivileges()])
    loading.value = false
  }

  onMounted(init)

  return {
    userInfo,
    isVip,
    vipExpireTime,
    vipPlans,
    privileges,
    loading,
    purchasing,
    handleSubscribe,
  }
}
