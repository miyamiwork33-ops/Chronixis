import { defineStore } from "pinia";
import axios, { getCsrfToken } from "@/plugins/axios";

export const useUserStore = defineStore("user", {
  state: () => ({
    user: null, // ユーザー情報
  }),

  actions: {
    async fetchUser() {
      try {
        // CSRF Cookie を取得
        await getCsrfToken();
        const { data } = await axios.get("/api/user");
        this.user = data;
        return data;
      } catch (error) {
        if (!error.response) {
          // ネットワークエラーやその他のレスポンスなしエラー
          console.error("ネットワークエラー:", error);
          throw error;
        }
        const status = error.response?.status;
        if (status === 401 || status === 419) {
          // 未認証/セッション失効時はローカル状態を確実に破棄
          this.user = null;
          return null;
        }
        console.error("ユーザー情報取得失敗:", error);
        throw error;
      }
    },
    setUser(userData) {
      this.user = userData;
    },
    async logout() {
      try {
        // CSRF Cookie を取得
        await getCsrfToken();
        await axios.post("/logout");
        return true;
      } catch (error) {
        const status = error.response?.status;
        if (status !== 401 && status !== 419) {
          console.error("ログアウト失敗:", error);
        }
        return false;
      } finally {
        // サーバー結果に関わらずクライアント状態は破棄し、UI の一貫性を保つ
        this.user = null;
      }
    },
  },
  getters: {
    userName: (state) => state.user?.name ?? "",
    userId: (state) => state.user?.id ?? null,
  },
});
