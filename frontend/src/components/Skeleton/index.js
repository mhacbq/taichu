import SkeletonCard from './SkeletonCard.vue'
import SkeletonFortune from './SkeletonFortune.vue'

export { SkeletonCard, SkeletonFortune }

export default {
  install(app) {
    app.component('SkeletonCard', SkeletonCard)
    app.component('SkeletonFortune', SkeletonFortune)
  }
}
