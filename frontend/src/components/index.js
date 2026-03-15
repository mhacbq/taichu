/**
 * 太初命理 - 组件库导出
 * 
 * 本文件集中导出所有可复用的 UI 组件
 */

// ========== 基础组件 ==========
export { default as Toast, useToast } from './Toast.vue'
export { default as EmptyState } from './EmptyState.vue'
export { default as ErrorBoundary } from './ErrorBoundary.vue'

// ========== 加载/骨架屏 ==========
export { default as PageSkeleton } from './PageSkeleton.vue'
export { default as BaziLoading } from './BaziLoading.vue'

// ========== 交互组件 ==========
export { default as ShareModal } from './ShareModal.vue'
export { default as TarotDrawAnimation } from './TarotDrawAnimation.vue'
export { default as FloatingActionButton } from './FloatingActionButton.vue'
export { default as PullToRefresh } from './PullToRefresh.vue'
export { default as HapticFeedback } from './HapticFeedback.vue'

// ========== 引导/提示 ==========
export { default as GuideModal } from './GuideModal.vue'
export { default as GuideModalImproved } from './GuideModalImproved.vue'
export { default as FirstTimeGuide } from './FirstTimeGuide.vue'
export { default as DailyReminder } from './DailyReminder.vue'
export { default as PushNotificationModal } from './PushNotificationModal.vue'

// ========== 反馈/动效 ==========
export { default as AchievementBadge } from './AchievementBadge.vue'
export { default as ConfettiEffect } from './ConfettiEffect.vue'
export { default as CheckinCard } from './CheckinCard.vue'
export { default as LazyImage } from './LazyImage.vue'
export { default as VirtualList } from './VirtualList.vue'

// ========== 骨架屏系列 ==========
export { 
  SkeletonCard, 
  SkeletonList, 
  SkeletonAvatar 
} from './skeletons'
