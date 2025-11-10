<template>
  <!-- 表示領域A -->
  <div 
    class="p-3 border" 
    role="button" 
    tabindex="0" 
    aria-label="Markdown入力エリアを開く"
    @click="openModal"
    @keyup.enter="openModal"
    @keyup.space.prevent="openModal"
  >
    <div class="my-markdown-text-area" v-if="renderedText" v-html="renderedText"></div>
    <div class="my-markdown-text-area" v-else><em>ここをクリックして入力</em></div>
  </div>
  <!-- モーダル -->
  <Dialog
    v-model:visible="showModal"
    modal
    header="Markdown入力"
    :style="{ width: '30rem' }"
  >
    <Textarea v-model="inputText" rows="10" cols="50" autoResize />
    <template #footer>
      <Button
        label="キャンセル"
        severity="secondary"
        @click="showModal = false"
      />
      <Button label="登録" @click="saveText" />
    </template>
  </Dialog>
</template>

<script setup>
import { ref } from "vue";
import Dialog from "primevue/dialog";
import Textarea from "primevue/textarea";
import Button from "primevue/button";
import { marked } from "marked";
import DOMPurify from "dompurify";

const showModal = ref(false);
const inputText = ref("");
const renderedText = ref("");
const originalText = ref("");

const openModal = () => {
  inputText.value = originalText.value;
  showModal.value = true;
};

const saveText = () => {
  originalText.value = inputText.value;
  renderedText.value = DOMPurify.sanitize(marked.parse(inputText.value));
  showModal.value = false;
};
</script>
