import { defineStore } from 'pinia';

export const useActCategoryStore = defineStore('category', {
  state: () => ({
    categories: []
  }),
  actions: {
    addCategory(category) {
      this.categories.push({
        ...category,
      });
    },
    updateCategory(id, updated) {
      const index = this.categories.findIndex(c => c.id === id);
      if (index !== -1) {
        this.categories[index] = {
          ...this.categories[index],
          ...updated,
        };
      }
    },
    deleteCategory(id) {
      this.categories = this.categories.filter(c => c.id !== id);
    }
  }
});
