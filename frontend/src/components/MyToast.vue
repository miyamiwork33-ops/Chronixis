<template>
    <Toast />
</template>

<script setup>
  import Toast from 'primevue/toast';
  import { useToast } from "primevue/usetoast";
  const toast = useToast();

  const show = (severity, summary, message, life) => {
    toast.add({
      severity: severity,
      summary: summary,
      detail: message,
      life: life,
    });
  };
  defineExpose({
    show
  });
</script>
