import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useTagsViewStore = defineStore('tagsView', () => {
  // State
  const visitedViews = ref([])
  const cachedViews = ref([])

  // Actions
  function addView(view) {
    if (visitedViews.value.some(v => v.path === view.path)) return
    visitedViews.value.push({
      name: view.name,
      path: view.path,
      title: view.meta.title || 'no-name',
      query: view.query,
      params: view.params
    })
    addCachedView(view)
  }

  function addCachedView(view) {
    if (cachedViews.value.includes(view.name)) return
    if (view.meta && view.meta.keepAlive) {
      cachedViews.value.push(view.name)
    }
  }

  function delView(view) {
    return new Promise(resolve => {
      for (const [i, v] of visitedViews.value.entries()) {
        if (v.path === view.path) {
          visitedViews.value.splice(i, 1)
          break
        }
      }
      resolve([...visitedViews.value])
    })
  }

  function delCachedView(view) {
    return new Promise(resolve => {
      const index = cachedViews.value.indexOf(view.name)
      index > -1 && cachedViews.value.splice(index, 1)
      resolve([...cachedViews.value])
    })
  }

  function delOthersViews(view) {
    return new Promise(resolve => {
      visitedViews.value = visitedViews.value.filter(v => {
        return v.meta?.affix || v.path === view.path
      })
      resolve([...visitedViews.value])
    })
  }

  function delAllViews() {
    return new Promise(resolve => {
      const affixTags = visitedViews.value.filter(tag => tag.meta?.affix)
      visitedViews.value = affixTags
      cachedViews.value = []
      resolve([...visitedViews.value])
    })
  }

  return {
    visitedViews,
    cachedViews,
    addView,
    addCachedView,
    delView,
    delCachedView,
    delOthersViews,
    delAllViews
  }
})
