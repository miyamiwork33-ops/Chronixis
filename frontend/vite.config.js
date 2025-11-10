import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import { fileURLToPath } from "url";
import path from "path";

// https://vite.dev/config/
export default defineConfig({
  base: "/",
  plugins: [vue()],
  resolve: {
    alias: {
      "@": fileURLToPath(new URL("./src", import.meta.url)),
    },
  },
  build: {
    emptyOutDir: true,
  },
  server: {
    host: "0.0.0.0", // コンテナ外からアクセス可能にする
    port: 5173, // 明示的にポート指定
    strictPort: true, // ポートが使えなければ失敗する（予測しやすくなる）
    hmr: {
      host: "localhost", // ホスト側のブラウザが接続する先
      port: 5173,
    },
  },
});
