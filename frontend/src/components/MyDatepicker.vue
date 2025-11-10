<script setup>
import Calendar from "primevue/calendar";

const props = defineProps({
  modelValue: {
    type: [Date, String, Array],
    default: null,
  },
});

const emit = defineEmits(["update:modelValue"]);

const updateDate = (value) => {
  emit("update:modelValue", value);
};
</script>

<template>
  <div class="calendar-wrapper">
    <Calendar :modelValue="modelValue" @update:modelValue="updateDate" inline />
  </div>
</template>

<style scoped>
.calendar-wrapper {
  transform: scale(0.9);
  transform-origin: center;
  display: inline-block;
  width: fit-content;
}
</style>
