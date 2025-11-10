<template>
  <h2 class="text-xl font-bold mb-4">
    {{ t("menu.label.habitItems.tracking") }}
  </h2>
  <div
    class="habit-timer-page"
    style="display: flex; flex-direction: column; gap: 1rem"
  >
    <div class="grid">
      <div class="col-5">
        <!-- 習慣セレクト -->
        <label>{{ t("tracking.challengeHabit") }}</label>
        <Select
          v-model="selectedHabit"
          :options="habits"
          optionLabel="title"
          :placeholder="t('tracking.selectionHabitPH')"
        />
      </div>
      <div class="col-7">
        <label>{{ t("tracking.detail") }}</label>
        <span class="full-width"> {{ selectedDetail }}</span>
      </div>
      <div class="col-5">
        <div style="display: flex; gap: 2rem">
          <!-- 左：タイマー -->
          <div style="flex: 1">
            <h3>{{ t("tracking.timer") }}</h3>

            <div class="grid">
              <div class="col-6">
                <RadioButton v-model="mode" inputId="down" value="down" />
                <label for="down">{{ t("tracking.countDown") }}</label>
              </div>
              <div class="col-6">
                <RadioButton v-model="mode" inputId="up" value="up" />
                <label for="up">{{ t("tracking.countUp") }}</label>
              </div>
            </div>

            <div style="margin-top: 1rem">
              <div v-if="mode === 'down'">
                <label>{{ t("tracking.timeMinutes") }}</label>
                <InputNumber
                  v-model="countdownMinutes"
                  showButtons
                  :min="1"
                  :max="120"
                />
              </div>
            </div>
            <div class="grid" style="margin-top: 1rem; font-size: 3rem">
              <div class="col-8">
                {{ formattedTime }}
              </div>
              <div class="col-4">
                <Button
                  :label="t('tracking.log')"
                  @click="recordLog(false)"
                  class="p-button-outlined"
                  :disabled="!selectedHabit"
                />
              </div>
            </div>

            <div
              class="grid"
              style="margin-top: 1rem; display: flex; gap: 1rem"
            >
              <div class="col-5">
                <Button
                  :label="t('tracking.reset')"
                  @click="resetTimer"
                  class="circle-button p-button-outlined"
                  severity="secondary"
                />
              </div>
              <div class="col-5">
                <Button
                  :label="isRunning ? t('tracking.pause') : t('tracking.start')"
                  @click="toggleTimer"
                  class="circle-button p-button-outlined"
                  severity="warn"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-7">
        <!-- 右：ログ一覧 -->
        <div style="flex: 1">
          <h3>{{ t("tracking.logList.title") }}</h3>
          <DataTable :value="logs">
            <Column field="date" :header="t('tracking.logList.headers.date')" />
            <Column
              field="is_achieved"
              :header="t('tracking.logList.headers.isAchieved')"
            />
            <Column
              field="execution_time"
              :header="t('tracking.logList.headers.executionTime')"
            />
          </DataTable>
        </div>
      </div>
    </div>
  </div>

  <MySpinner ref="mySpinner" />
  <MyToast ref="myToast" />

  <Dialog
    v-model:visible="visible"
    modal
    :header="t('tracking.dialog.header')"
    :style="{ width: '25rem' }"
  >
    <span class="text-surface-500 dark:text-surface-400 block mb-8">{{
      t("tracking.dialog.message")
    }}</span>
    <div class="flex justify-end gap-2">
      <Button
        type="button"
        :label="t('tracking.dialog.cancelButton')"
        severity="secondary"
        @click="visible = false"
      ></Button>
      <Button
        type="button"
        :label="t('tracking.dialog.registerButton')"
        class="p-button-outlined"
        @click="recordLog(true)"
      ></Button>
    </div>
  </Dialog>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch } from "vue";
import axios from "@/plugins/axios";
import Select from "primevue/select";
import InputText from "primevue/inputtext";
import RadioButton from "primevue/radiobutton";
import InputNumber from "primevue/inputnumber";
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import MyToast from "@/components/MyToast.vue";
import { useI18n } from "vue-i18n";
import MySpinner from "@/components/MySpinner.vue";
import Dialog from "primevue/dialog";
import dayjs from "dayjs";

const myToast = ref(null);
const mySpinner = ref(null);
const { t } = useI18n();

const habits = ref([]);
const selectedHabit = ref(null);
const selectedDetail = ref(null);
const mode = ref("down"); // 'up' or 'down'
const countdownMinutes = ref(5);
const time = ref(0);
const timer = ref(null);
const isRunning = ref(false);
const visible = ref(false);
const is_achieved = ref(false);
const execution_time = ref(0);

// ログ一覧
const logs = ref([]);
// タイマー制御
const isPaused = ref(false);

/**
 *
 */
onMounted(async () => {
  getData("/api/habit/log/init");
});

onBeforeUnmount(() => {
  if (timer.value) {
    clearInterval(timer.value);
  }
});

/**
 * ログデータ取得
 */
const getData = async (url) => {
  mySpinner.value.setData(true, t("mySpinnerMsg.loading"));

  try {
    const response = await axios.get(url);
    if (response.data.status == "ok") {
      habits.value = response.data.habits;
    }
  } catch (error) {
    myToast.value?.show(
      "danger",
      t("tracking.myToast.summaryFailure"),
      t("tracking.myToast.dataLoadFail"),
      3000
    );
  } finally {
    mySpinner.value.setData(false, "");
  }
};

/**
 * タイマーを切り替える
 */
const toggleTimer = () => {
  if (!isRunning.value) {
    // 初回または再開
    isRunning.value = true;
    isPaused.value = false;
    if (mode.value === "down" && time.value === 0) {
      time.value = countdownMinutes.value * 60;
    }

    timer.value = setInterval(() => {
      if (mode.value === "up") {
        time.value++;
      } else {
        time.value--;
        if (time.value <= 0) {
          stopTimer();
        }
      }
    }, 1000);
  } else {
    // 一時停止
    clearInterval(timer.value);
    isRunning.value = false;
    isPaused.value = true;
  }
};

/**
 * タイマーを停止
 */
const stopTimer = () => {
  clearInterval(timer.value);
  isRunning.value = false;
  isPaused.value = false;
};

/**
 * タイマーをリセット
 */
const resetTimer = () => {
  clearInterval(timer.value);
  isRunning.value = false;
  isPaused.value = false;
  time.value = 0;
};

/**
 * 時間・詳細・ログを変更する
 */
const changeTimeAndDetailAndLog = (habitData) => {
  selectedDetail.value = habitData.detail;
  time.value = habitData.duration_minutes * 60;
  countdownMinutes.value = habitData.duration_minutes;
  logs.value = [];
  let habitLogs = habitData.habitLogs;
  for (const log of habitLogs) {
    logs.value.push({
      date: dayjs(log.log_time).format("YYYY/MM/DD HH:mm:ss"),
      is_achieved: log.is_achieved ? "〇" : "×",
      execution_time: formatTime(log.execution_time),
    });
  }
};

/**
 * 達成したかを計算する
 */
function calculateAchievement() {
  if (mode.value === "down") {
    if (time.value == 0) {
      is_achieved.value = true;
    } else {
      is_achieved.value = false;
    }
    execution_time.value =
      selectedHabit.value.duration_minutes * 60 - time.value;
  } else {
    if (selectedHabit.value.duration_minutes * 60 <= time.value) {
      is_achieved.value = true;
    } else {
      is_achieved.value = false;
    }
    execution_time.value = time.value;
  }
  return is_achieved.value;
}

// 記録ボタン押下
const recordLog = async (is_verified) => {
  if (!is_verified) {
    if (!calculateAchievement()) {
      visible.value = true;
      return;
    }
  }

  visible.value = false;

  const payload = {
    log_time: dayjs().format("YYYY-MM-DD HH:mm:ss"),
    habit_goal_id: selectedHabit.value.habit_goal_id,
    is_achieved: is_achieved.value,
    execution_time: execution_time.value,
  };
  try {
    const response = await axios.post("/api/habit/log/store", payload);
    if (response.data.status == "ok") {
      let storeData = response.data.habitLogs;
      logs.value.push({
        date: dayjs(storeData.log_time).format("YYYY/MM/DD HH:mm:ss"),
        is_achieved: storeData.is_achieved ? "〇" : "×",
        execution_time: formatTime(storeData.execution_time),
      });
      visible.value = false;
    }
  } catch (error) {
    myToast.value?.show(
      "danger",
      t("habitManage.myToast.summaryFailure"),
      t("habitManage.myToast.dataStoreFail"),
      3000
    );
  }
};

/**
 * 時間を成型
 */
const formatTime = (targetTime) => {
  const min = Math.floor(targetTime / 60)
    .toString()
    .padStart(2, "0");
  const sec = (targetTime % 60).toString().padStart(2, "0");
  return `${min}:${sec}`;
};

/**
 * 表示用の時間を成型する
 */
const formattedTime = computed(() => {
  return formatTime(time.value);
});

watch(mode, () => {
  resetTimer();
});

watch(selectedHabit, (newHabit) => {
  if (newHabit) {
    changeTimeAndDetailAndLog(newHabit);
  } else {
    logs.value = [];
    selectedDetail.value = null;
    resetTimer();
  }
});
</script>

<style scoped>
.habit-timer-page {
  padding: 1rem;
}
label {
  margin: 0 10px;
}
.p-inputtext {
  width: 90px !important;
}
.circle-button {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  padding: 0;
  text-align: center;
  font-weight: bold;
}
.full-width {
  width: 80% !important;
}
</style>
