// TODO:簡単なログイン機能の実装_後ほど修正する
<template>
  <BlockUI :blocked="loading">
    <div class="login-container">
      <h1 class="login-title">{{ $t("login.title") }}</h1>
      <form @submit.prevent="login">
        <div class="form-field">
          <label for="email">{{ $t("login.emailLabel") }}</label>
          <InputText
            id="email"
            v-model="email"
            :placeholder="`${$t('login.emailUser')}@${$t('login.emailDomain')}`"
            class="input-field"
            :aria-invalid="!!emailError"
            data-testid="email-input"
            aria-describedby="email-error"
          />
          <p
            v-if="emailError"
            id="email-error"
            class="error-message"
            role="alert"
          >
            {{ emailError }}
          </p>
        </div>
        <div class="form-field">
          <label for="password">{{ $t("login.passwordLabel") }}</label>
          <InputText
            id="password"
            v-model="password"
            type="password"
            :placeholder="$t('login.passwordPlaceholder')"
            class="input-field"
            :aria-invalid="!!passwordError"
            data-testid="password-input"
            aria-describedby="password-error"
          />
          <p
            v-if="passwordError"
            id="password-error"
            class="error-message"
            role="alert"
          >
            {{ passwordError }}
          </p>
        </div>
        <Button
          type="submit"
          :label="
            loading
              ? $t('loginParts.loginButton.sending')
              : $t('loginParts.loginButton.login')
          "
          :loading="loading"
          data-testid="login-button"
        />
      </form>
    </div>
  </BlockUI>
  <MyToast ref="myToast" />
</template>

<script setup>
// VueのComposition API: ref（リアクティブ変数）を使う
import { ref } from "vue";
// axiosでAPI通信
import axios, { getCsrfToken } from "@/plugins/axios";
// Vue Router でページ遷移するためのフック
import { useRouter, useRoute } from "vue-router";
// useUserStoreはPiniaのストア関数。
// 呼び出すことで、このコンポーネントからユーザー情報の状態やアクションにアクセスできる。
import { useUserStore } from "../stores/user";

import BlockUI from "primevue/blockui";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import { useI18n } from "vue-i18n";

// フォーム入力値とエラーメッセージ
const email = ref("");
const password = ref("");
const emailError = ref("");
const passwordError = ref("");
const router = useRouter(); // Routerのインスタンスを取得
const route = useRoute(); // 現在のルート情報（クエリなど）
const userStore = useUserStore();
const myToast = ref(null);

// Viteの環境変数からAPIのベースURLを取得
const baseURL = import.meta.env.VITE_API_URL;
if (!baseURL) {
  throw new Error("VITE_API_URL environment variable is not defined");
}
const loading = ref(false);

const { t } = useI18n();

// ログイン処理の関数
const login = async () => {
  loading.value = true;

  if (!checkLogin()) {
    loading.value = false;
    return;
  }

  try {
    await getCsrfToken();
    // 2. Laravel のログインAPIへメールアドレスとパスワードをPOSTし、セッション認証を試みる
    await axios.post(`${baseURL}/login`, {
      email: email.value,
      password: password.value,
    });

    // 4. /api/user を取得してストアに保存
    await userStore.fetchUser();

    // 5. ユーザー情報が取得できていれば成功
    if (userStore.user) {
      emailError.value = ""; // エラーメッセージをクリア
      passwordError.value = ""; // エラーメッセージをクリア
      const redirectPath =
        typeof route.query.redirect === "string" &&
        route.query.redirect.startsWith("/") &&
        !route.query.redirect.startsWith("//")
          ? route.query.redirect
          : "/dashboard";
      router.push(redirectPath);
    } else {
      // 6. 万が一ユーザー情報が取得できなければエラーをスロー
      myToast.value?.show(
        "error",
        t("login.myToast.errorNotif"),
        t("login.myToast.errorNotifMsg"),
        3000
      );
    }
  } catch (err) {
    // 7. ここに来たらログイン失敗（認証エラーや通信エラーなど）
    if (err.response?.status === 401) {
      myToast.value?.show(
        "warn",
        t("login.myToast.authError"),
        t("login.myToast.authErrorMsg"),
        3000
      );
    } else {
      myToast.value?.show(
        "error",
        t("login.myToast.commError"),
        t("login.myToast.commErrorMsg"),
        3000
      );
    }
    // 開発者向けにエラー詳細をコンソールに出力
    console.error(err);
  } finally {
    loading.value = false;
  }
};

// 入力チェック
const checkLogin = () => {
  emailError.value = "";
  passwordError.value = "";

  if (!email.value) {
    emailError.value = t("login.emailRequired");
  } else if (!validateEmail(email.value)) {
    emailError.value = t("login.emailInvalid");
  }

  if (!password.value) {
    passwordError.value = t("login.passwordRequired");
  } else if (password.value.length < 6) {
    passwordError.value = t("login.passwordTooShort");
  }

  if (!emailError.value && !passwordError.value) {
    return true;
  }

  return false;
};

const validateEmail = (value) => {
  const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return regex.test(value);
};
</script>

<style scoped>
.login-container {
  max-width: 400px;
  margin: auto;
  padding: 1rem;
}

.login-title {
  margin-bottom: 1rem;
}

.error-message {
  color: red;
  margin-top: 1rem;
}

.input-field {
  display: block;
  margin-bottom: 1rem;
  width: 100%;
}
</style>
