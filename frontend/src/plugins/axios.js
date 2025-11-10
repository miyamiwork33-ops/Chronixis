import axios from "axios";

// Axios インスタンスを作成
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "http://localhost:8000",
  withCredentials: true, // Cookie を送信するために必要
});

// CSRF Cookie を取得して、トークンをヘッダーに設定
export const getCsrfToken = async () => {
  await api.get(`${import.meta.env.VITE_API_URL}/sanctum/csrf-cookie`);

  // Cookie から XSRF-TOKEN を取得してヘッダーに設定
  const token = getCookieValue("XSRF-TOKEN");
  if (token) {
    api.defaults.headers.common["X-XSRF-TOKEN"] = decodeURIComponent(token);
  }
};

// Cookie から値を取得するヘルパー関数
function getCookieValue(name) {
  const escapedName = name.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  const match = document.cookie.match(
    new RegExp("(^| )" + escapedName + "=([^;]+)")
  );
  return match ? match[2] : null;
}

// エラーハンドリングの例（必要に応じて拡張可能）
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      console.warn("認証エラー: ログインが必要です");
    }
    return Promise.reject(error);
  }
);

export default api;
