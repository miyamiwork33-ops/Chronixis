<template>
  <div class="layout-wrapper">
    <Header :isMobile="isMobile" @toggleSidebar="toggleSidebar" />
    <!-- ダッシュボード以外のときに表示 -->
    <Breadcrumb
      v-if="route.name !== 'dashboard'"
      :home="dashboard"
      :model="items"
      :router="true"
      class="p-2"
    >
      <template #item="{ item, index }">
        <!-- 最後の要素（現在地）は強調表示 -->
        <span
          v-if="index === items.length - 1"
          class="p-breadcrumb-item-label text-noir-600 font-semibold"
        >
          {{ item.label }}
        </span>

        <!-- それ以外で、リンクがある場合のみRouterLink -->
        <RouterLink
          v-else-if="item.to && item.to !== '/'"
          :to="item.to"
          class="p-breadcrumb-item-link hover:text-indigo-500 transition-colors duration-200"
        >
          {{ item.label }}
        </RouterLink>

        <!-- リンクがない中間階層 -->
        <span v-else class="p-breadcrumb-item-label text-gray-500">
          {{ item.label }}
        </span>
      </template>
    </Breadcrumb>

    <!-- オーバーレイ背景（モバイル時のみ） -->
    <div v-if="sidebarVisible" class="overlay" @click="closeSidebar"></div>

    <div class="layout-content">
      <Sidebar
        v-if="!isMobile || sidebarVisible"
        :visible="!isMobile || sidebarVisible"
        @close="closeSidebar"
      />
      <main class="main-content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";
import { useRoute } from "vue-router";
import Header from "@/components/Header.vue";
import Sidebar from "@/components/Sidebar.vue";
import Breadcrumb from "primevue/breadcrumb";
import { useI18n } from "vue-i18n";

const { t } = useI18n();
const route = useRoute();
const sidebarVisible = ref(false);
const isMobile = ref(false);

function toggleSidebar() {
  sidebarVisible.value = !sidebarVisible.value;
}
function closeSidebar() {
  sidebarVisible.value = false;
}

function handleResize() {
  isMobile.value = window.innerWidth < 1500;
}

onMounted(() => {
  handleResize();
  window.addEventListener("resize", handleResize);
});

onUnmounted(() => {
  window.removeEventListener("resize", handleResize);
});

const items = computed(() => {
  // meta.breadcrumb が設定されていればそれを使用
  if (route.meta.breadcrumb) {
    return route.meta.breadcrumb.map((b, index, arr) => ({
      label: t(b.label),
      to: index < arr.length - 1 ? b.to || "/" : undefined, // 最後の階層はリンクなし
    }));
  }

  // fallback: matched から生成
  return route.matched
    .filter((r) => r.meta?.label)
    .map((r, index, arr) => ({
      label: t(r.meta.label),
      to: index < arr.length - 1 ? r.path : undefined,
    }));
});

const dashboard = computed(() => ({
  label: t("menu.label.dashboard"),
  to: "/dashboard",
}));
</script>

<style scoped>
.overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.4);
  z-index: 40;
}
.layout-content {
  display: flex;
  height: 100vh;
}
.main-content {
  flex: 1;
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
}
</style>
