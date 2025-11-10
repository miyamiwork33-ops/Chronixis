<template>
  <div ref="chartContainer" class="chart-container">
    <canvas ref="chartCanvas" class="canvas"></canvas>
  </div>
</template>

<style scoped>
.canvas {
  width: 250px !important;
  height: 250px !important;
}
</style>
<script setup>
import { ref, onMounted, watch } from "vue";
import {
  Chart,
  DoughnutController,
  ArcElement,
  Tooltip,
  Legend,
} from "chart.js";
import ChartDataLabels from "chartjs-plugin-datalabels";
import dayjs from "dayjs";

Chart.register(
  DoughnutController,
  ArcElement,
  Tooltip,
  Legend,
  ChartDataLabels
);

const chartContainer = ref(null);

// 時間目盛りを描画するプラグイン
const hourTicksPlugin = {
  id: "hourTicks",
  afterDraw: (chart) => {
    const { ctx, chartArea, data } = chart;
    const centerX = (chartArea.left + chartArea.right) / 2;
    const centerY = (chartArea.top + chartArea.bottom) / 2;
    const radius =
      Math.min(
        chartArea.right - chartArea.left,
        chartArea.bottom - chartArea.top
      ) / 2;

    ctx.save();
    ctx.translate(centerX, centerY);
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillStyle = "#9F9F9F";
    ctx.font = "12px sans-serif";

    // 0〜23時の目盛りを描画
    for (let i = 0; i < 24; i++) {
      const angle = (i / 24) * 2 * Math.PI - Math.PI / 2; // 0:00 を上に
      const labelRadius = radius + 20; // 外側に余裕を持たせる
      const x = Math.cos(angle) * labelRadius;
      const y = Math.sin(angle) * labelRadius;
      ctx.fillText(i.toString(), x, y);
    }

    ctx.restore();
  },
};

// 円の中心に日付を描画するプラグイン
const centerDatePlugin = {
  id: "centerDate",
  beforeDraw: (chart) => {
    const { ctx, chartArea } = chart;
    const centerX = (chartArea.left + chartArea.right) / 2;
    const centerY = (chartArea.top + chartArea.bottom) / 2;

    ctx.save();
    const date = dayjs(props.targetDate);
    const year = date.format("YYYY");
    const monthDay = date.format("M/D");
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.font = "bold 14px sans-serif";
    ctx.fillStyle = "#9F9F9F";
    // 上段：年
    ctx.fillText(year, centerX, centerY - 10);
    // 下段：月/日
    ctx.fillText(monthDay, centerX, centerY + 10);
    ctx.restore();
  },
};

const props = defineProps({
  chartData: { type: Object, required: true },
  targetDate: { type: String, required: true },
});

const chartCanvas = ref(null);
let chartInstance = null;

// チャート生成関数
const createChart = () => {
  if (chartInstance) chartInstance.destroy();

  chartInstance = new Chart(chartCanvas.value, {
    type: "doughnut",
    data: {
      labels: props.chartData.labels,
      datasets: [
        {
          data: props.chartData.values,
          backgroundColor: props.chartData.colors,
        },
      ],
    },
    options: {
      responsive: true,
      cutout: "50%",
      layout: {
        padding: {
          top: 30,
          bottom: 30,
          left: 20,
          right: 20,
        },
      },
      plugins: {
        legend: {
          display: false, // 凡例を非表示にする
        },
        datalabels: {
          color: "#fff",
          formatter: (value, context) => {
            const label = context.chart.data.labels[context.dataIndex];
            const hours = (value / 60).toFixed(1);
            return value >= 90 ? `${label}\n${hours}h` : "";
          },
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              const label = context.label || "";
              const minutes = context.raw;
              const hours = Math.floor(minutes / 60);
              const mins = Math.round(minutes % 60); // ← 秒切り捨て誤差対策
              return `${label}: ${hours}時間${mins}分`;
            },
          },
        },
      },
    },
    plugins: [hourTicksPlugin, centerDatePlugin],
  });
};

onMounted(() => {
  createChart();
  if (chartContainer.value) {
    chartContainer.value.style.width = "fit-content";
    chartContainer.value.style.margin = "0 auto";
  }
});
watch(
  () => props.chartData,
  () => {
    createChart();
  },
  { deep: true }
);
</script>
