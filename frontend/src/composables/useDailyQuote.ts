import { computed } from 'vue'

export function useDailyQuote() {
  const quotes = [
    '"迷茫不是软弱，而是你在认真思考人生"',
    '"每一次困惑，都是重新认识自己的机会"',
    '"相信自己的直觉，你比想象中更有力量"',
    '"生活不会辜负每一个认真的人"',
    '"今天的迷茫，是为了明天更坚定的选择"',
    '"慢慢来，没关系，每个人都在自己的时区"',
  ]

  const dailyQuote = computed(() => {
    const dayOfYear = Math.floor(
      (new Date().getTime() - new Date(new Date().getFullYear(), 0, 0).getTime()) / 
      (1000 * 60 * 60 * 24)
    )
    return quotes[dayOfYear % quotes.length]
  })

  return {
    dailyQuote,
  }
}
