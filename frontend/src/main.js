import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import en from '@/locales/en.json';
import ja from '@/locales/ja.json';

import App from '@/App.vue'
import router from '@/router/index.js';
import primevue from '@/plugins/primevue';

// axios プラグイン設定（CSRFなど）
import '@/plugins/axios.js';

const app = createApp(App);

// プラグイン
app.use(router);
app.use(createPinia());
app.use(primevue);

const i18n = createI18n({
  legacy: false,
  locale: 'ja',  // デフォルト言語
  fallbackLocale: 'en',
  messages: { en, ja },
});
app.use(i18n);

import MyToast from "@/components/MyToast.vue";
// グローバル登録
app.component('MyToast', MyToast);

app.mount('#app');
