<template>
  <header class="topbar">
    <div class="left-section">
      <h1 class="logo">Chronixis</h1>
      <button
        class="menu-button"
        type="button"
        aria-label="サイドバーを開閉"
        @click="$emit('toggleSidebar')"
        v-if="isMobile"
      >
        ☰
      </button>    </div>

    <div class="right-section">
      <span class="username">{{ user?.name || 'ゲスト' }}</span>
      <Avatar :image="user?.icon_path || ''" shape="circle" size="large" />
    </div>
  </header>
</template>

<script setup>
import Avatar from 'primevue/avatar';
import { computed } from "vue";
import { useUserStore } from "@/stores/user.js";
defineProps({ isMobile: Boolean });

const userStore = useUserStore();
// ローカルのユーザー情報参照用。Piniaの状態を反映
const user = computed(() => userStore.user);

</script>

<style scoped>
.topbar {
  height: 60px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background-color: var(--primary-color);
  color: white;
}

.left-section {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo {
  font-size: 1.2rem;
  font-weight: bold;
}

.menu-button {
  margin-left: 30%;
  font-size: 1.5rem;
  background: none;
  border: none;
  color: white;
  cursor: pointer;
}

.right-section {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.username {
  font-weight: 500;
}
</style>
