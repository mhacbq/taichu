import { useHead } from '@vueuse/head'
import { computed } from 'vue'

/**
 * SEO配置组合函数
 * 用于动态设置页面标题、描述、关键词、Open Graph等SEO相关信息
 * 
 * @param {Object} options - SEO配置选项
 * @param {string} options.title - 页面标题
 * @param {string} options.description - 页面描述
 * @param {string|string[]} options.keywords - 关键词
 * @param {string} options.image - 分享图片URL
 * @param {string} options.url - 页面URL
 * @param {string} options.type - 内容类型 (website, article, product)
 * @param {Object} options.structuredData - JSON-LD结构化数据
 * @param {string} options.canonical - 规范链接
 * @param {string} options.author - 作者
 * @param {string} options.robots - robots指令 (index,follow/noindex,nofollow)
 */
export function useSEO(options = {}) {
  const {
    title = '太初命理 - AI智能命理分析平台',
    description = '太初命理提供专业的八字排盘、塔罗占卜、紫微斗数、每日运势等AI智能命理分析服务',
    keywords = ['命理', '八字', '塔罗', '运势'],
    image = '/images/og-default.jpg',
    url = '',
    type = 'website',
    structuredData = null,
    canonical = '',
    author = '太初命理',
    robots = 'index,follow'
  } = options

  // 格式化关键词
  const keywordsString = computed(() => {
    if (Array.isArray(keywords)) {
      return keywords.join(',')
    }
    return keywords
  })

  // 完整标题（带品牌后缀）
  const fullTitle = computed(() => {
    const brandSuffix = ' - 太初命理'
    if (title.includes('太初命理')) {
      return title
    }
    return title + brandSuffix
  })

  // 站点基础URL
  const baseUrl = import.meta.env.VITE_SITE_URL || 'https://taichu.chat'
  const fullUrl = url ? `${baseUrl}${url}` : baseUrl
  const fullImage = image.startsWith('http') ? image : `${baseUrl}${image}`

  // 使用useHead设置所有meta标签
  useHead({
    // 基础meta
    title: fullTitle.value,
    meta: [
      // 基础SEO
      { name: 'description', content: description },
      { name: 'keywords', content: keywordsString.value },
      { name: 'author', content: author },
      { name: 'robots', content: robots },
      
      // 视口和兼容性
      { name: 'viewport', content: 'width=device-width, initial-scale=1.0, maximum-scale=5.0' },
      { 'http-equiv': 'X-UA-Compatible', content: 'IE=edge,chrome=1' },
      
      // 主题色
      { name: 'theme-color', content: '#6366f1' },
      { name: 'msapplication-TileColor', content: '#6366f1' },
      
      // Open Graph
      { property: 'og:title', content: fullTitle.value },
      { property: 'og:description', content: description },
      { property: 'og:type', content: type },
      { property: 'og:url', content: fullUrl },
      { property: 'og:image', content: fullImage },
      { property: 'og:image:width', content: '1200' },
      { property: 'og:image:height', content: '630' },
      { property: 'og:site_name', content: '太初命理' },
      { property: 'og:locale', content: 'zh_CN' },
      
      // Twitter Card
      { name: 'twitter:card', content: 'summary_large_image' },
      { name: 'twitter:title', content: fullTitle.value },
      { name: 'twitter:description', content: description },
      { name: 'twitter:image', content: fullImage },
      { name: 'twitter:site', content: '@taichu_mingli' },
      
      // 百度专用
      { name: 'baidu-site-verification', content: '' },
      
      // 360搜索
      { name: '360-site-verification', content: '' },
      
      // 搜狗
      { name: 'sogou_site_verification', content: '' },
      
      // 防止转码
      { 'http-equiv': 'Cache-Control', content: 'no-transform' },
      { 'http-equiv': 'Cache-Control', content: 'no-siteapp' }
    ],
    
    // 链接标签
    link: [
      // 规范链接
      canonical ? { rel: 'canonical', href: canonical } : {},
      
      // 多语言/地区替代链接
      { rel: 'alternate', hreflang: 'zh-CN', href: fullUrl },
      { rel: 'alternate', hreflang: 'x-default', href: fullUrl },
      
      // 预连接优化
      { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
      { rel: 'dns-prefetch', href: 'https://fonts.googleapis.com' }
    ].filter(Boolean),
    
    // 结构化数据
    script: structuredData ? [
      {
        type: 'application/ld+json',
        children: JSON.stringify(structuredData)
      }
    ] : []
  })

  // 返回一些有用的方法
  return {
    title: fullTitle,
    description,
    keywords: keywordsString,
    updateSEO: (newOptions) => useSEO({ ...options, ...newOptions })
  }
}

/**
 * 预定义的页面SEO配置
 */
export const seoConfigs = {
  // 首页
  home: {
    title: '太初命理 - 专业八字排盘_塔罗占卜_每日运势',
    description: '太初命理是专业的AI智能命理分析平台，提供八字排盘、塔罗占卜、每日运势、紫微斗数等服务。精准分析，科学解读，10万+用户信赖的命理工具。',
    keywords: ['八字排盘', '塔罗占卜', '每日运势', '命理分析', 'AI算命', '在线排盘', '生辰八字', '星座运势'],
    image: '/images/og-home.jpg'
  },
  
  // 八字排盘
  bazi: {
    title: '免费八字排盘_在线生辰八字测算_专业命理分析',
    description: '免费在线八字排盘工具，输入出生日期即可生成专业八字命盘。包含四柱、藏干、十神、神煞分析，精准解读您的性格、事业、财运、婚姻运势。',
    keywords: ['八字排盘', '免费八字', '生辰八字', '四柱八字', '八字测算', '八字算命', '八字命盘', '八字分析'],
    image: '/images/og-bazi.jpg'
  },
  
  // 塔罗占卜
  tarot: {
    title: '免费塔罗牌占卜_在线塔罗测试_AI智能解牌',
    description: '免费在线塔罗牌占卜，涵盖爱情、事业、财运、运势等多个维度。AI智能解牌，专业塔罗师解读，让塔罗指引您的人生方向。',
    keywords: ['塔罗占卜', '塔罗牌', '塔罗测试', '免费塔罗', '在线塔罗', '塔罗解牌', '塔罗牌阵', '塔罗爱情'],
    image: '/images/og-tarot.jpg'
  },
  
  // 每日运势
  daily: {
    title: '今日运势查询_每日星座运势_黄历宜忌',
    description: '查看今日运势，包含十二星座每日运势、黄历宜忌、时辰吉凶。每日更新，科学预测，助您趋吉避凶，把握最佳时机。',
    keywords: ['今日运势', '每日运势', '星座运势', '黄历查询', '今日宜忌', '时辰吉凶', '每日运程', '运势预测'],
    image: '/images/og-daily.jpg'
  },
  
  // 个人中心
  profile: {
    title: '个人中心_我的排盘记录_积分管理',
    description: '管理您的太初命理个人账户，查看历史排盘记录、收藏内容、积分余额和充值记录。',
    keywords: ['个人中心', '命理记录', '八字记录', '塔罗记录', '积分充值'],
    image: '/images/og-profile.jpg',
    robots: 'noindex,follow'
  },
  
  // 登录页
  login: {
    title: '登录/注册 - 太初命理',
    description: '登录太初命理账户，开启您的AI智能命理分析之旅。',
    keywords: ['登录', '注册', '太初命理登录'],
    robots: 'noindex,follow'
  },
  
  // 充值页
  recharge: {
    title: '积分充值_购买命理服务 - 太初命理',
    description: '充值积分，解锁更多专业的AI命理分析服务。八字排盘、塔罗占卜、深度解读等您体验。',
    keywords: ['积分充值', '命理服务', '八字付费', '塔罗付费'],
    image: '/images/og-recharge.jpg'
  },
  
  // 帮助中心
  help: {
    title: '帮助中心_使用指南_常见问题 - 太初命理',
    description: '太初命理使用指南和常见问题解答，帮助您更好地使用八字排盘、塔罗占卜等功能。',
    keywords: ['帮助中心', '使用指南', '常见问题', '命理教程', '八字教程', '塔罗教程'],
    image: '/images/og-help.jpg'
  },
  
  // 404页面
  notFound: {
    title: '页面未找到 - 太初命理',
    description: '抱歉，您访问的页面不存在。返回太初命理首页，探索八字排盘、塔罗占卜等AI智能命理服务。',
    keywords: ['404', '页面未找到'],
    robots: 'noindex,follow'
  }
}

/**
 * 生成网站结构化数据
 */
export function generateWebsiteSchema() {
  return {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: '太初命理',
    url: 'https://taichu.chat',
    description: '专业的AI智能命理分析平台',
    potentialAction: {
      '@type': 'SearchAction',
      target: 'https://taichu.chat/search?q={search_term_string}',
      'query-input': 'required name=search_term_string'
    }
  }
}

/**
 * 生成组织结构化数据
 */
export function generateOrganizationSchema() {
  return {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: '太初命理',
    url: 'https://taichu.chat',
    logo: 'https://taichu.chat/logo.png',
    description: '专业的AI智能命理分析平台',
    sameAs: [
      'https://weibo.com/taichu',
      'https://mp.weixin.qq.com/taichu'
    ]
  }
}

/**
 * 生成FAQ结构化数据
 */
export function generateFAQSchema(faqs) {
  return {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: faqs.map(faq => ({
      '@type': 'Question',
      name: faq.question,
      acceptedAnswer: {
        '@type': 'Answer',
        text: faq.answer
      }
    }))
  }
}

/**
 * 生成面包屑结构化数据
 */
export function generateBreadcrumbSchema(items) {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: items.map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: item.name,
      item: item.url
    }))
  }
}

export default useSEO
