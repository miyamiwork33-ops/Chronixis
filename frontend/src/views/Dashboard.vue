<template>
  <div class="dashboard-grid">
    <div class="item my-24h-space">
      <div class="my-24h-hed">
        <DatePicker
          class="pie_chart_calendar"
          v-model="selectedDate"
          showIcon
          fluid
          iconDisplay="input"
          dateFormat="yy/mm/dd"
          @update:modelValue="handleDateChange"
        />
      </div>
      <div class="my-24h">
        <PieChart24h
          :chartData="chartData"
          :targetDate="dayjs(selectedDate).format('YYYY-MM-DD')"
        />
      </div>
      <div class="diary">
        <p class="diary-title">Diary</p>
        <MyMarkdown />
      </div>
    </div>
    <div class="item my-dot-space">
      <MyDot />
    </div>
    <div class="item my-calendar-space"><MyDatepicker /></div>
    <div class="item my-wbs-space"></div>
    <div class="item my-board-space"></div>
  </div>
  <MySpinner ref="mySpinner" />
  <MyToast ref="myToast" />
</template>

<style scoped>
.logout-button {
  margin-top: 50px;
}
</style>

<script setup>
import { useRouter } from "vue-router";
import axios from "@/plugins/axios";
import { ref, onMounted, computed } from "vue";
import Button from "primevue/button";
import DatePicker from "primevue/datepicker";
import PieChart24h from "@/components/PieChart24h.vue";
import MyDatepicker from "@/components/MyDatepicker.vue";
import MyMarkdown from "@/components/MyMarkdown.vue";
import MyDot from "@/components/MyDot.vue";
import { formatSchedulesForChart } from "@/utils/scheduleFormatter.js";
import MySpinner from "@/components/MySpinner.vue";
import dayjs from "dayjs";
import { useI18n } from "vue-i18n";

const { t } = useI18n();
const loading = ref(false);
const chartData = ref({ labels: [], values: [], colors: [] });
const selectedDate = ref(dayjs().format("YYYY/MM/DD"));
const actCategoryMaster = ref([]);
const schedules = ref([]);
const mySpinner = ref(null);
const myToast = ref(null);

onMounted(async () => {
  let targetDate = dayjs(selectedDate.value).format("YYYY-MM-DD");
  getData(targetDate, "/api/schedules/init", true);
});

const getData = async (targetDate, url, isInit) => {
  try {
    mySpinner.value.setData(true, t("mySpinnerMsg.loading"));
    const payload = {
      targetDate: targetDate,
      isInit
    };
    const response = await axios.post(url, payload);
    if (response.data.status == "ok") {
      if (isInit) {
        actCategoryMaster.value = response.data.actCategories;
      }
      const fetchedData = response.data.schedules;
      schedules.value = fetchedData.map((item) => ({
        key: item.id,
        data: {
          id: item.id,
          start_time: item.start_time,
          end_time: item.end_time,
          color: item.color,
          activity: item.activity ?? null,
          act_category_id: item.act_category_id ?? null,
        },
      }));
    }
  } catch (error) {
    console.error(t("scheduleManage.myToast.dataGetFailureMsg") + ":", error);
    myToast.value?.show(
      "error",
      t("scheduleManage.myToast.summaryFailure"),
      t("scheduleManage.myToast.dataGetFailureMsg"),
      3000
    );
  } finally {
    if (schedules.value.length === 0) {
      const key = Date.now();
      schedules.value = [
        {
          key: String(key),
          data: {
            id: null,
            start_time: null,
            end_time: null,
            color: "",
            activity: "",
            act_category_id: null,
          },
        },
      ];
      chartData.value = formatSchedulesForChart(schedules.value, targetDate, t);
    } else {
      const filtered = schedules.value
        .map((item) => {
          const start_time = item.data.start_time;
          const end_time = item.data.end_time;
          const category = actCategoryMaster.value.find(
            (cat) => cat.id === item.data.act_category_id
          );

          return {
            start_time,
            end_time,
            activity: category?.activity || t("scheduleManage.unCategorized"),
            color: category?.hex_color_code || "#9E9E9E",
          };
        })
        .filter((item) => item.start_time && item.end_time);

      chartData.value = formatSchedulesForChart(filtered, targetDate, t);
    }
    mySpinner.value.setData(false, "");
  }
};

/**
 * カレンダーの変更処理
 */
function handleDateChange(newDate) {
  let targetDate = dayjs(newDate).format("YYYY-MM-DD");
  getData(targetDate, "/api/schedules/init", false);
}
</script>

<style scoped>
.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  grid-template-rows: auto auto auto auto;
  gap: 8px;
}

.item {
  background: #27272a;
  padding: 1rem;
  text-align: center;
}
.pie_chart_calendar {
  width: 160px;
}
/* 配置調整 */
.my-24h-space {
  grid-row: 1 / span 3;
  grid-column: 1;
}
.my-24h-hed {
  display: flex;
  align-items: center; /* 垂直方向の中央揃え */
  justify-content: center; /* 水平方向の中央揃え（任意） */
  gap: 1rem;
}
.my-24h-hed > p {
  color: #9f9f9f;
  font-weight: 600;
}
.my-24h-prev-btn,
.my-24h-next-btn {
  background: #27272a;
  color: #9f9f9f;
  border: 0px !important;
}
.diary {
  /* height: 30%; */
}
.diary-title {
  text-align: left;
  color: #9f9f9f;
}
:deep(.my-markdown-text-area em) {
  color: #9f9f9f !important;
}
.my-dot-space {
  grid-row: 1;
  grid-column: 2;
}

.my-calendar-space {
  grid-row: 1;
  grid-column: 3;
}

.my-wbs-space {
  grid-row: 2 / span 2;
  grid-column: 2 / span 2;
  height: 150px;
}

.my-board-space {
  grid-row: 4;
  grid-column: 1 / span 3;
  height: 200px;
}
</style>
