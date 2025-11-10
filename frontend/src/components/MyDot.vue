<template>
  <div class="dot-select">
    <Select
      v-model="selectedHabit"
      :options="habits"
      optionLabel="title"
      placeholder="習慣の選択"
      class="w-full md:w-56"
      aria-label="習慣を選択"
    />
  </div>
  <div
    ref="containerRef"
    class="grid-container dot-container"
    role="grid"
    aria-label="習慣記録カレンダー"
    :style="{
      gridTemplateColumns: `repeat(${columnCount}, ${cellSize}px)`,
    }"
  >
    <div
      v-for="(cell, index) in squares"
      :key="index"
      class="grid-cell"
      :class="{ active: cell }"
      :style="{
        width: `${cellSize}px`,
        height: `${cellSize / 2}px`,
        borderRadius: `${cellSize * 0.15}px`,
        backgroundColor: cell ? activeColor : '#9f9f9f',
      }"
    ></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import Select from "primevue/select";
import axios from "@/plugins/axios";
import dayjs from "dayjs";

const selectedHabit = ref(null);
const habits = ref([]);
const squares = ref([]);
const columnCount = computed(() => 5);
const activeColor = ref("#6366f1"); // 初期色（インディゴ）
const containerRef = ref(null); // コンテナの幅を取得

// リサイズイベント
const handleResize = () => {
  if (containerRef.value) {
    containerRef.value.style.margin = "0 auto";
  }
};

const fetchHabits = async () => {
  try {
    const response = await axios.get("/api/habit/log/init");
    habits.value = response.data.habits;

    if (habits.value.length > 0) {
      selectedHabit.value = habits.value[0];
    } else {
      // データが空の場合の処理
      console.warn("No habits found");
    }
  } catch (error) {
    console.error("Error fetching habits:", error);
    // エラー表示コンポーネントの表示など
  }
};

onMounted(async () => {
  await fetchHabits();
  window.addEventListener("resize", handleResize);
});

// クリーンアップ
onUnmounted(() => {
  window.removeEventListener("resize", handleResize);
});

// セルサイズを計算（40px〜60pxの範囲）
const cellSize = computed(() => {
  const count = squares.value.length;
  const maxSize = 60;
  const minSize = 40;

  const size = maxSize - Math.floor((count / 100) * (maxSize - minSize));
  return Math.max(minSize, size);
});

function generateSquares(habitLogs) {
  if (!habitLogs || habitLogs.length === 0) return [];

  // ① 最も古い日付を取得
  const startDate = dayjs(
    habitLogs.reduce((min, log) => {
      return log.log_time < min ? log.log_time : min;
    }, habitLogs[0].log_time)
  ).startOf("day");

  const today = dayjs().startOf("day");

  // ② 日付ごとの達成状況をマップ化
  const logMap = {};
  habitLogs.forEach((log) => {
    const date = dayjs(log.log_time).format("YYYY-MM-DD");
    logMap[date] = log.is_achieved;
  });

  // ③ 日付一覧を作成し、squaresを構築
  const squares = [];
  let current = startDate;

  while (current.isSame(today) || current.isBefore(today)) {
    const dateStr = current.format("YYYY-MM-DD");
    squares.push(logMap[dateStr] ?? false); // データがなければ false
    current = current.add(1, "day");
  }

  return squares;
}

watch(selectedHabit, (newHabit) => {
  if (!newHabit) {
    squares.value = [];
    activeColor.value = "#6366f1";
    return;
  }
  // データ配列（true: 色付き, false: グレー）
  squares.value = generateSquares(newHabit.habitLogs);
  activeColor.value = newHabit.hex_color_code || "#6366f1";
});
</script>

<style scoped>
.dot-select {
  padding: 10px;
}
.grid-container {
  display: grid;
  gap: 4px;
  width: max-content;
  margin: 0 auto;
}
.dot-container {
  padding: 30px 10px 10px 10px;
  max-height: 300px;
  overflow-y: auto;
  overflow-x: hidden;
  width: fit-content;
}
.grid-cell {
  background-color: #9f9f9f;
}
</style>
