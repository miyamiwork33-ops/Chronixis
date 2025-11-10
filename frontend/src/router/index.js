import { createRouter, createWebHistory } from "vue-router";
import { useUserStore } from "@/stores/user";
import Login from "@/views/Login.vue";
import MainLayout from "@/layouts/MainLayout.vue";
import NotFound from "@/views/NotFound.vue";
import Dashboard from "@/views/Dashboard.vue";
import ScheduleManage from "@/views/schedule/ScheduleManage.vue";
import ActCategoryMaster from "@/views/schedule/ActCategoryMaster.vue";
import SetGoals from "@/views/habits/SetGoals.vue";
import Tracking from "@/views/habits/Tracking.vue";
// import Calendar from "@/views/Calendar.vue";
// import Tasks from "@/views/Tasks.vue";

const routes = [
  { path: "/login", name: "login", component: Login }, // ログイン
  { path: "/", redirect: { name: "dashboard" } }, // home(ダッシュボード)
  // home(ダッシュボード)
  {
    path: "/dashboard",
    component: MainLayout,
    meta: { requiresAuth: true, breadcrumb: false },
    children: [
      {
        path: "",
        name: "dashboard",
        component: Dashboard,
        meta: { label: 'menu.label.dashboard', breadcrumb: false },
      },
    ],
  },
  // スケジュール管理 - 一日の予定の管理
  // スケジュール管理 - 行動カテゴリマスタ管理
  {
    path: "/schedule/manage",
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: "",
        name: "schedule-manage",
        component: ScheduleManage,
        meta: {
          label: "menu.label.scheduleItems.scheduleManage",
          breadcrumb: [
            { label: "menu.label.dailySchedule" }, // ← 中間階層（実ページなし）
            { label: "menu.label.scheduleItems.scheduleManage" },
          ],
        },
      },
    ],
  },
  {
    path: "/schedule/actCategoryMaster",
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: "",
        name: "act-category-master",
        component: ActCategoryMaster,
        meta: {
          label: "menu.label.scheduleItems.actCategoryMaster",
          breadcrumb: [
            { label: "menu.label.dailySchedule" }, // ← 中間階層（実ページなし）
            { label: "menu.label.scheduleItems.actCategoryMaster" },
          ],
        },
      },
    ],
  },
  // 習慣化 - 目標を決める
  // 習慣化 - 記録の管理
  {
    path: "/habits/setGoals",
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: "",
        name: "habits-set-goals",
        component: SetGoals,
        meta: {
          label: "menu.label.habitItems.setGoals",
          breadcrumb: [
            { label: "menu.label.habits" }, // ← 中間階層（実ページなし）
            { label: "menu.label.habitItems.setGoals" },
          ],
        },
      },
    ],
  },
  {
    path: "/habits/tracking",
    component: MainLayout,
    meta: { requiresAuth: true, breadcrumb: false },
    children: [
      {
        path: "",
        name: "habits-tracking",
        component: Tracking,
        meta: {
          label: "menu.label.habitItems.tracking",
          breadcrumb: [
            { label: "menu.label.habits" }, // ← 中間階層（実ページなし）
            { label: "menu.label.habitItems.tracking" },
          ],
        },
      },
    ],
  },
  {
    path: "/:pathMatch(.*)*",
    name: "not-found",
    component: NotFound,
    meta: { label: "404" },
  },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
});

// ルートガード
router.beforeEach(async (to, from, next) => {
  const userStore = useUserStore();

  // 404ページは認証不要
  if (to.name === "not-found") {
    return next();
  }

  // ユーザー情報が未取得の場合は取得を試みる
  if (!userStore.user) {
    try {
      await userStore.fetchUser();
    } catch (error) {
      if (import.meta.env.DEV) {
        console.error("Failed to fetch user:", error);
      }
      /* 認証されていないものとして扱う */
    }
  }

  // ログイン済みユーザーが login ページにアクセスした場合は dashboard にリダイレクト
  if (to.name === "login" && userStore.user) {
    return next({ name: "dashboard" });
  }

  const requiresAuth = to.matched.some((r) => r.meta?.requiresAuth);
  if (requiresAuth) {
    if (!userStore.user) {
      return next({ name: "login", query: { redirect: to.fullPath } });
    }
  }
  return next();
});

export default router;
