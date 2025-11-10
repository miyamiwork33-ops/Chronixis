<template>
  <transition name="slide">
    <div v-if="visible" class="sidebar-panel">
      <div class="sidebar-content">
        <PanelMenu :model="items" />
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref } from "vue";
import PanelMenu from "primevue/panelmenu";
import { useRouter } from "vue-router";
import { useUserStore } from "@/stores/user";
import { useI18n } from "vue-i18n";

defineProps({ visible: Boolean });

const { t } = useI18n();
const userStore = useUserStore();
const router = useRouter();

const handleLogout = async () => {
  try {
    await userStore.logout();
    router.push("/login");
  } catch (error) {
    console.error("ログアウト失敗:", error);
  } finally {
  }
};

const items = ref([
  { label: t("menu.label.dashboard"), icon: "pi pi-home",  command: () => router.push({ name: 'dashboard' })},
  {
    label: t("menu.label.calendar"),
    icon: "pi pi-calendar",
    items: [
      { label: t("menu.label.calendarItems.registration"), command: () => router.push({ name: 'calendar-registration' })},
      { label: t("menu.label.calendarItems.detail"), command: () => router.push({ name: 'calendar-detail' })},
    ],
  },
  { // 一日の予定表
    label: t("menu.label.dailySchedule"),
    icon: "pi pi-chart-pie",
    items: [
      { label: t("menu.label.scheduleItems.scheduleManage"), command: () => router.push({ name: 'schedule-manage' })},
      { label: t("menu.label.scheduleItems.actCategoryMaster"), command: () => router.push({ name: 'act-category-master' })},
    ],
  },
  { // 習慣化
    label: t("menu.label.habits"),
    icon: "pi pi-list",
    items: [
      { label: t("menu.label.habitItems.setGoals"), command: () => router.push({ name: 'habits-set-goals' })},
      { label: t("menu.label.habitItems.tracking"), command: () => router.push({ name: 'habits-tracking' })},
    ],
  },
  { label: t("menu.label.settings"), icon: "pi pi-cog", command: () => router.push({ name: 'settings' })},
  { label: t("menu.label.logout"), icon: "pi pi-sign-out", command: handleLogout },]);
</script>

<style scoped>
.sidebar-panel {
  position: fixed;
  top: 60px;
  left: 0;
  width: 280px;
  height: 100vh;
  padding-top: 1rem;
  background-color: var(--surface-ground);
  border-right: 1px solid var(--surface-border);
  z-index: 1001;
  display: flex;
  flex-direction: column;
}
.sidebar-content {
  flex: 1;
}
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease;
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(-100%);
}
.p-panelmenu {
  gap: 0 !important;
}
:deep(.p-panelmenu-panel) {
  border-radius: 0 !important;
  opacity: 0.9 !important;
  border-style: none !important;
  padding: 0.5rem;
}
</style>
