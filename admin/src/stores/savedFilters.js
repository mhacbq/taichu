import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useSavedFiltersStore = defineStore('savedFilters', () => {
  const savedFilters = ref({})

  function getFilter(page) {
    return savedFilters.value[page] || {}
  }

  function saveFilter(page, filter) {
    savedFilters.value[page] = filter
    localStorage.setItem('savedFilters', JSON.stringify(savedFilters.value))
  }

  function deleteFilter(page) {
    delete savedFilters.value[page]
    localStorage.setItem('savedFilters', JSON.stringify(savedFilters.value))
  }

  function loadFromStorage() {
    const stored = localStorage.getItem('savedFilters')
    if (stored) {
      savedFilters.value = JSON.parse(stored)
    }
  }

  loadFromStorage()

  return {
    savedFilters,
    getFilter,
    saveFilter,
    deleteFilter
  }
})
