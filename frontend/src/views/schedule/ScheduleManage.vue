<template>
  <div class="schedule-container">
    <!-- 一日の予定の管理 -->
    <h2>{{ t("menu.label.scheduleItems.scheduleManage") }}</h2>
    <div class="grid">
      <div class="col-4 preview"></div>
      <div class="col-8 date">
        <!-- 追加ボタン -->
        <Button
          icon="pi pi-plus"
          :label="t('scheduleManage.addButton')"
          severity="success"
          class="p-button-sm p-button-success"
          @click="addRow"
        />
        <!-- 登録ボタン -->
        <Button
          icon="pi pi-save"
          :label="t('scheduleManage.registrationButton')"
          class="p-button-primary"
          style="margin-left: 80%"
          @click="saveActs"
        />
      </div>
    </div>
    <div class="grid">
      <div class="col-4 preview">
        <!-- 日にち選択 -->
        <DatePicker
          class="calendar"
          v-model="selectedDate"
          showIcon
          fluid
          iconDisplay="input"
          dateFormat="yy/mm/dd"
          @update:modelValue="handleDateChange"
        />
        <p>
          <span style="font-size: 22px">preview</span>
          <!-- 更新ボタン -->
          <Button
            icon="pi pi-refresh"
            :label="t('scheduleManage.updateButton')"
            severity="secondary"
            style="margin-left: 45%"
            @click="replay"
          />
        </p>
        <!-- 円グラフ -->
        <PieChart24h
          :chartData="chartData"
          :targetDate="dayjs(selectedDate).format('YYYY-MM-DD')"
        />
      </div>
      <div class="col-8 date scroll-area">
        <draggable
          v-model="schedules"
          item-key="key"
          handle=".drag-handle"
          animation="200"
          class="schedule-list"
        >
          <template #item="{ element }">
            <div :key="element.key" class="schedule-row">
              <span class="drag-handle" style="cursor: grab">☰</span>
              <DatePicker
                v-model="element.data.start_time"
                showIcon
                iconDisplay="input"
                timeOnly
                :placeholder="t('scheduleManage.datePicker.start')"
                class="time"
              >
                <template #inputicon="slotProps">
                  <i class="pi pi-clock" @click="slotProps.clickCallback" />
                </template>
              </DatePicker>
              <DatePicker
                v-model="element.data.end_time"
                showIcon
                iconDisplay="input"
                timeOnly
                :placeholder="t('scheduleManage.datePicker.end')"
                v-tooltip.top="
                  $t('scheduleManage.datePicker.endTimeDescription')
                "
                class="time"
              >
                <template #inputicon="slotProps">
                  <i class="pi pi-clock" @click="slotProps.clickCallback" />
                </template>
              </DatePicker>
              <Select
                v-model="element.data.act_category_id"
                :options="actCategoryMaster"
                optionLabel="activity"
                optionValue="id"
                style="width: 24rem"
              >
                <template #value="{ value }">
                  <div class="flex items-center gap-2">
                    <span
                      :style="{
                        backgroundColor: getHexColor(value),
                        width: '25px',
                        height: '25px',
                        borderRadius: '2px',
                        border: '1px solid #000',
                        display: 'inline-block',
                        flexShrink: 0,
                      }"
                    />
                    <span>{{ getActDetailById(value) }}</span>
                  </div>
                </template>
                <template #option="{ option }">
                  <div class="flex items-center gap-2">
                    <span
                      :style="{
                        backgroundColor: option.hex_color_code,
                        width: '25px',
                        height: '25px',
                        borderRadius: '2px',
                        border: '1px solid #000',
                        display: 'inline-block',
                        flexShrink: 0,
                        margin: 'auto',
                      }"
                    />
                    <span>{{ option.activity }}</span>
                  </div>
                </template>
              </Select>
              <Button
                icon="pi pi-trash"
                class="p-button-rounded p-button-sm"
                severity="danger"
                @click="removeRow(element.key)"
              />
              <span
                v-if="errors[element.key]?.start_time"
                class="text-red-500 text-sm"
              >
                {{ errors[element.key].start_time }}
              </span>
              <span
                v-if="errors[element.key]?.end_time"
                class="text-red-500 text-sm"
              >
                {{ errors[element.key].end_time }}
              </span>
              <span
                v-if="errors[element.key]?.activity"
                class="text-red-500 text-sm"
              >
                {{ errors[element.key].activity }}
              </span>
            </div>
          </template>
        </draggable>
      </div>
    </div>
  </div>
  <MySpinner ref="mySpinner" />
  <MyToast ref="myToast" />
</template>

<script setup>
import { useRouter } from "vue-router";
import { ref, onMounted } from "vue";
import { formatSchedulesForChart } from "@/utils/scheduleFormatter.js";
import { useI18n } from "vue-i18n";
import axios from "@/plugins/axios";
import PieChart24h from "@/components/PieChart24h.vue";
import draggable from "vuedraggable";
import DatePicker from "primevue/datepicker";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Select from "primevue/select";
import dayjs from "dayjs";
import MySpinner from "@/components/MySpinner.vue";

const myToast = ref(null);
const mySpinner = ref(null);
const { t } = useI18n();
const actCategoryMaster = ref([]);
const selectedActCategory = ref();

const selectedDate = ref(new Date());
const chartData = ref({ labels: [], values: [], colors: [] });

const schedules = ref([]);
const errors = ref({});

/**
 * 行追加
 */
const addRow = () => {
  const key = Date.now();
  schedules.value.push({
    key: String(key),
    data: {
      id: null,
      start_time: "",
      end_time: "",
      color: "",
      activity: "",
      act_category_id: null,
    },
  });
};

/**
 * 円グラフの更新処理
 */
const replay = () => {
  const selected = dayjs(selectedDate.value).format("YYYY-MM-DD");

  const filtered = schedules.value
    .map((item) => {
      const start_time =
        typeof item.data.start_time === "object"
          ? dayjs(item.data.start_time).format("HH:mm")
          : item.data.start_time;
      const end_time =
        typeof item.data.end_time === "object"
          ? dayjs(item.data.end_time).format("HH:mm")
          : item.data.end_time;

      const startTime = dayjs(`${selected} ${start_time}`);
      const endTime = dayjs(`${selected} ${end_time}`);

      if (!startTime.isValid() || !endTime.isValid()) return null;

      const category = actCategoryMaster.value.find(
        (cat) => cat.id === item.data.act_category_id
      );

      return {
        start_time: startTime.format("HH:mm"),
        end_time: endTime.format("HH:mm"),
        activity: category?.activity || t("scheduleManage.unCategorized"),
        color: category?.hex_color_code || "#9E9E9E",
      };
    })
    .filter(Boolean);

  chartData.value = {
    ...formatSchedulesForChart(
      filtered,
      dayjs(selectedDate.value).format("YYYY-MM-DD"),
      t
    ),
  };
};

/**
 * レコードの削除ボタン処理
 */
const removeRow = (key) => {
  schedules.value = schedules.value.filter((row) => row.key !== key);
};

/**
 * 指定した日付の予定データ取得処理
 */
const getData = async (targetDate, isInit) => {
  try {
    mySpinner.value.setData(true, t("mySpinnerMsg.loading"));
    const payload = {
      targetDate: targetDate,
      isInit,
    };

    const response = await axios.post("/api/schedules/init", payload);
    if (response.data.status == "ok") {
      if (isInit) {
        actCategoryMaster.value = response.data.actCategories;
      }

      if (
        Array.isArray(response.data.msgArray) &&
        response.data.msgArray.length > 0
      ) {
        for (const key in response.data.msgArray) {
          myToast.value?.show(
            response.data.msgArray[key].severity,
            response.data.msgArray[key].summary,
            response.data.msgArray[key].detail,
            response.data.msgArray[key].life
          );
        }
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

    let msgArray = response.data.msgArray;

    if (Array.isArray(msgArray) && msgArray.length > 0) {
      for (const key in response.data.msgArray) {
        myToast.value?.show(
          response.data.msgArray[key].severity,
          response.data.msgArray[key].summary,
          response.data.msgArray[key].detail,
          response.data.msgArray[key].life
        );
      }
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
  getData(targetDate, false);
}

/**
 * マウント時の処理
 */
onMounted(async () => {
  mySpinner.value.setData(true, t("mySpinnerMsg.loading"));
  let targetDate = dayjs().format("YYYY-MM-DD");
  getData(targetDate, true);
});

/**
 * 色スウォッチの変更
 */
const getHexColor = (act_category_id) => {
  const match = actCategoryMaster.value.find(
    (item) => item.id === act_category_id
  );
  return match ? match.hex_color_code : "#000"; // デフォルト色
};

/**
 * ActIDから行動内容を取得する
 */
const getActDetailById = (id) => {
  const match = actCategoryMaster.value.find((item) => item.id === id);
  return match ? match.activity : "";
};

/**
 * バリデーション
 */
function validateForm() {
  errors.value = {}; // 初期化
  let isValid = true;

  const seen = new Map();
  schedules.value.forEach((row, index) => {
    const rowErrors = {};

    if (typeof row.data.start_time == "string") {
      if (row.data.start_time.trim() === "") {
        rowErrors.start_time = t("scheduleManage.validateForm.startCheck");
        isValid = false;
      }
    } else if (typeof row.data.start_time == "object") {
      if (row.data.start_time == null) {
        rowErrors.start_time = t("scheduleManage.validateForm.startCheck");
        isValid = false;
      }
    }

    if (typeof row.data.end_time == "string") {
      if (row.data.end_time.trim() === "") {
        rowErrors.end_time = t("scheduleManage.validateForm.endCheck");
        isValid = false;
      }
    } else if (typeof row.data.end_time == "object") {
      if (row.data.end_time == null) {
        rowErrors.end_time = t("scheduleManage.validateForm.endCheck");
        isValid = false;
      }
    }

    if (compareTime(row.data.start_time, row.data.end_time)) {
      rowErrors.end_time = t("scheduleManage.validateForm.compareCheck");
      isValid = false;
    }

    if (row.data.act_category_id == null) {
      rowErrors.activity = t("scheduleManage.validateForm.actCheck");
      isValid = false;
    }

    // 重複チェック
    schedules.value.forEach((checkRow, checkIndex) => {
      if (
        checkIndex !== index && // 自分自身は除外
        isTimeOverlap(
          checkRow.data.start_time,
          checkRow.data.end_time,
          row.data.start_time,
          row.data.end_time
        )
      ) {
        rowErrors.activity = t("scheduleManage.validateForm.duplicationCheck");
        isValid = false;
      }
    });

    if (Object.keys(rowErrors).length > 0) {
      errors.value[row.key] = rowErrors;
    }
  });

  return isValid;
}

/**
 * 開始時間と終了時間を比較する
 */
const compareTime = (start, end) => {
  const startTime =
    typeof start === "string" ? start : dayjs(start).format("HH:mm:ss");
  const endTime = typeof end === "string" ? end : dayjs(end).format("HH:mm:ss");
  return startTime >= endTime;
};

/**
 * 重複チェック
 */
const isTimeOverlap = (start1, end1, start2, end2) => {
  const s1 =
    typeof start1 === "string" ? start1 : dayjs(start1).format("HH:mm:ss");
  const e1 = typeof end1 === "string" ? end1 : dayjs(end1).format("HH:mm:ss");
  const s2 =
    typeof start2 === "string" ? start2 : dayjs(start2).format("HH:mm:ss");
  const e2 = typeof end2 === "string" ? end2 : dayjs(end2).format("HH:mm:ss");

  return s1 < e2 && e1 > s2;
};

/**
 * 日時を時間へ変更する
 */
function extractJustForTime(target) {
  const formatter = new Intl.DateTimeFormat("en-GB", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    timeZone: "Asia/Tokyo",
    hourCycle: "h23", // 24時間表記
  });
  return formatter.format(target); // 例: "00:00:00"
}

/**
 * 登録処理
 */
const saveActs = async () => {
  if (!validateForm()) {
    myToast.value?.show(
      "warn",
      t("scheduleManage.myToast.inputError"),
      t("scheduleManage.myToast.inputErrorMsg"),
      3000
    );
    return;
  }

  // JSTに変換して 'YYYY-MM-DD' 形式で送信
  const jstDateString = dayjs(selectedDate.value).format("YYYY-MM-DD");

  try {
    mySpinner.value.setData(true, t("mySpinnerMsg.registering"));
    const payload = schedules.value.map((r) => ({
      ...r.data,
      start_time:
        typeof r.data.start_time == "string"
          ? r.data.start_time
          : extractJustForTime(r.data.start_time),
      end_time:
        typeof r.data.end_time == "string"
          ? r.data.end_time
          : extractJustForTime(r.data.end_time),
      target_date: jstDateString,
    }));

    const response = await axios.post("/api/schedules/upsert", payload);

    if (response.data.status == "ok") {
      myToast.value?.show(
        "info",
        t("actCategoryMaster.myToast.summarySuccess"),
        t("actCategoryMaster.myToast.successMsg"),
        3000
      );
    }

    if (
      Array.isArray(response.data.msgArray) &&
      response.data.msgArray.length > 0
    ) {
      for (const key in response.data.msgArray) {
        myToast.value?.show(
          response.data.msgArray[key].severity,
          response.data.msgArray[key].summary,
          response.data.msgArray[key].detail,
          response.data.msgArray[key].life
        );
      }
    }

    replay();
  } catch (err) {
    console.error(err);
    myToast.value?.show(
      "error",
      t("actCategoryMaster.myToast.summaryFailure"),
      t("actCategoryMaster.myToast.failureMsg"),
      3000
    );
  } finally {
    mySpinner.value.setData(false, "");
  }
};
</script>

<style scoped>
.schedule-row {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  align-items: center;
}
.calendar {
  margin-bottom: 20px;
}
.grid {
  margin-top: 20px;
}
.preview {
  padding-left: 30px;
  padding-right: 30px;
}
.time {
  width: 120px;
}
.schedule-list {
  max-height: 400px;
  overflow-y: auto;
}
.drag-handle {
  margin-right: 8px;
  font-size: 18px;
}
</style>
