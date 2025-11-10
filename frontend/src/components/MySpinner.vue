<template>
  <div v-if="loading" class="overlay">
    <div class="spinner-box">
      <ProgressSpinner style="width: 50px; height: 50px" />
      <p>{{ message }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import ProgressSpinner from "primevue/progressspinner";

const message = ref("");
const loading = ref(false);

const setData = (isLoading, msg) => {
  message.value = msg;
  loading.value = isLoading;
};

// このコンポーネント外から使えるように公開
defineExpose({
  setData,
});
</script>

<style scoped>
.overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(128, 128, 128, 0.4); /* 半透明グレー */
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999; /* 最前面に表示 */
  pointer-events: all; /* 背景の操作をブロック */
}

.spinner-box {
  background: rgb(0, 0, 0);
  padding: 20px;
  border-radius: 8px;
  text-align: center!important;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
}
</style>
