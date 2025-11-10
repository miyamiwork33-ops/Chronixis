<template>
  <div>
    <h2 class="text-xl font-bold mb-4">
      {{ t("menu.label.habitItems.setGoals") }}
    </h2>
    <div class="grid">
      <!-- 追加ボタン -->
      <div class="col-6">
        <Button
          :label="t('setGoals.addButton')"
          icon="pi pi-plus"
          class="p-button-sm p-button-success"
          @click="addRow"
        />
      </div>
      <!-- 登録ボタン -->
      <div class="col-6 text-right">
        <Button
          :label="t('setGoals.registrationButton')"
          icon="pi pi-save"
          class="p-button-primary"
          @click="saveHabits"
        />
      </div>
    </div>

    <!-- テーブル -->
    <TreeTable
      :value="rows"
      dataKey="key"
      class="mt-4"
      scrollable
      scrollHeight="350px"
      tableStyle="min-width: 50rem"
    >
      <!-- 行動との紐づけ -->
      <Column :header="t('setGoals.headers.linkToAction')" style="width: 6rem">
        <template #body="{ node }">
          <!-- is_linked=true なら行動カテゴリと紐づける -->
          <ToggleSwitch
            v-model="node.data.is_linked"
            :disabled="node.data.id ? true : false"
          />
        </template>
      </Column>

      <!-- 行動/色とタイトル -->
      <Column
        :header="t('setGoals.headers.actionColorTitle')"
        style="width: 24rem"
      >
        <template #body="{ node }">
          <!-- 行動と紐づける場合：セレクトボックス -->
          <div v-if="node.data.is_linked" class="flex flex-col grid">
            <div class="flex col-12">
              <span
                :style="{
                  backgroundColor: node.data.hex_color_code,
                  width: '30px',
                  height: '30px',
                  borderRadius: '2px',
                  border: '1px solid #000',
                  display: 'inline-block',
                  flexShrink: 0,
                  margin: 'auto',
                }"
              />
              <!-- 既存データなら disabled のテキスト -->
              <span v-if="node.data.id != null">
                <InputText
                  :value="
                    actCategoryMaster.find(
                      (item) => item.id === node.data.act_category_id
                    )?.color_name
                  "
                  style="width: 14rem"
                  disabled
                />
              </span>
              <!-- 新規作成中なら Select でカテゴリ選択 -->
              <span v-else>
                <Select
                  v-model="node.data.act_category_id"
                  :options="actCategoryMaster"
                  optionLabel="activity"
                  optionValue="id"
                  style="width: 14rem"
                  @change="updateHexColor(node)"
                />
              </span>
              <!-- バリデーションエラー表示 -->
              <span
                v-if="errors[node.key]?.act_category_id"
                class="text-red-500 text-sm"
              >
                {{ errors[node.key].act_category_id }}
              </span>
            </div>
            <span v-if="node.data.id != null" class="col-12">
              <InputText
                :value="
                  actCategoryMaster.find(
                    (item) => item.id === node.data.act_category_id
                  )?.activity
                "
                class="col-12"
                disabled
              />
            </span>
            <span v-else class="col-12">
              <InputText
                v-model="node.data.title"
                :placeholder="t('setGoals.titlePH')"
                class="col-12"
                :disabled="true"
              />
            </span>
          </div>
          <div v-else class="flex flex-col grid">
            <!-- 上段：色スウォッチと色名 -->
            <div class="flex col-12">
              <span
                :style="{
                  backgroundColor: node.data.hex_color_code,
                  width: '30px',
                  height: '30px',
                  borderRadius: '2px',
                  border: '1px solid #000',
                  display: 'inline-block',
                  flexShrink: 0,
                  margin: 'auto',
                }"
              />
              <Select
                v-model="node.data.color_name"
                :options="auraColors"
                optionLabel="color_name"
                optionValue="color_name"
                style="width: 14rem"
                @change="updateHexColor(node)"
              />
              <span
                v-if="errors[node.key]?.color_name"
                class="text-red-500 text-sm"
              >
                {{ errors[node.key].color_name }}
              </span>
            </div>

            <!-- 下段：タイトル入力 -->
            <span class="col-12">
              <InputText
                v-model="node.data.title"
                :placeholder="t('setGoals.titlePH')"
                class="col-12"
              />
            </span>
            <span v-if="errors[node.key]?.title" class="text-red-500 text-sm">
              {{ errors[node.key].title }}
            </span>
          </div>
        </template>
      </Column>

      <!-- 所要時間 -->
      <Column
        :header="t('setGoals.headers.durationMinutes')"
        style="width: 6rem"
      >
        <template #body="{ node }">
          <InputText
            v-model="node.data.duration_minutes"
            type="number"
            :placeholder="t('setGoals.durationMinutesPH')"
            style="width: 6rem"
          />
        </template>
      </Column>

      <!-- 詳細内容 -->
      <Column :header="t('setGoals.headers.details')">
        <template #body="{ node }">
          <InputText
            v-model="node.data.detail"
            :placeholder="t('setGoals.detailPH')"
            class="w-full"
          />
        </template>
      </Column>

      <!-- 削除列 -->
      <Column :header="t('setGoals.headers.delete')" style="width: 80px">
        <template #body="{ node }">
          <Button
            icon="pi pi-trash"
            class="p-button-rounded p-button-danger p-button-sm"
            @click="removeRow(node.key)"
            :disabled="!node.data.can_delete"
          />
        </template>
      </Column>
    </TreeTable>
  </div>

  <MySpinner ref="mySpinner" />
  <MyToast ref="myToast" />
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "@/plugins/axios";
import Button from "primevue/button";
import ToggleSwitch from "primevue/toggleswitch";
import Select from "primevue/select";
import InputText from "primevue/inputtext";
import TreeTable from "primevue/treetable";
import Column from "primevue/column";
import MyToast from "@/components/MyToast.vue";
import { auraColors } from "@/data/auraColors.js";
import { useI18n } from "vue-i18n";
import MySpinner from "@/components/MySpinner.vue";

const myToast = ref(null);
const mySpinner = ref(null);
const rows = ref([]);
const errors = ref({});
const { t } = useI18n();
const actCategoryMaster = ref([]);

/**
 * マウント時の処理
 */
onMounted(async () => {
  mySpinner.value.setData(true, t("mySpinnerMsg.loading"));
  getData("/api/habit/goal/init", true);
});

/**
 * 行追加
 */
const addRow = () => {
  if (!auraColors || auraColors.length === 0) {
    console.error(t("setGoals.myToast.noColorDataMsg"));
    myToast.value?.show(
      "error",
      t("setGoals.myToast.summaryFailure"),
      t("setGoals.myToast.noColorDataMsg"),
      3000
    );
    return;
  }
  const key = Date.now();
  rows.value.push({
    key: String(key),
    data: {
      id: null,
      is_linked: false,
      act_category_id: null,
      color_name: auraColors[0].color_name,
      hex_color_code: auraColors[0].hex_color_code,
      title: "",
      detail: "",
      duration_minutes: 0,
      deleted_at: false,
      can_delete: true,
    },
  });
};

/**
 * 行削除
 */
const removeRow = (key) => {
  rows.value = rows.value.filter((row) => row.key !== key);
};

/**
 * 色スウォッチの変更
 */
const updateHexColor = (node) => {
  if (node.data.is_linked) {
    // 行動マスターから色コードを取得
    const selected = actCategoryMaster.value.find(
      (item) => item.id === node.data.act_category_id
    );
    if (selected) {
      node.data.hex_color_code = selected.hex_color_code;
      node.data.title = selected.activity;
      node.data.color_name = selected.color_name;
    }
  } else {
    // 通常のオーラカラー選択時
    const selected = auraColors.find(
      (c) => c.color_name === node.data.color_name
    );
    if (selected) {
      node.data.hex_color_code = selected.hex_color_code;
    }
  }
};

/**
 * バリデーション
 */
function validateForm() {
  errors.value = {}; // 初期化
  let isValid = true;

  const seenCode = new Map();
  const seenId = new Map();
  rows.value
    .filter((row) => !row.data.deleted_at)
    .forEach((row, index) => {
      const rowErrors = {};

      if (row.data.is_linked == true) {
        if (!row.data.act_category_id) {
          rowErrors.act_category_id = t(
            "setGoals.validateForm.actRequiredCheck"
          );
          isValid = false;
        }
      } else {
        if (!row.data.title || row.data.title.trim() === "") {
          rowErrors.title = t("setGoals.validateForm.titleRequiredCheck");
          isValid = false;
        }
      }

      // 重複チェック
      const code = row.data.hex_color_code;
      if (!seenCode.has(code)) {
        seenCode.set(code, 1);
      } else {
        seenCode.set(code, seenCode.get(code) + 1);
        if (seenCode.get(code) > 1) {
          rowErrors.color_name = t("setGoals.validateForm.colorDuplicateCheck");
          isValid = false;
        }
      }

      // 重複チェック
      const id = row.data.act_category_id;
      if (id) {
        if (!seenId.has(id)) {
          seenId.set(id, 1);
        } else {
          seenId.set(id, seenId.get(id) + 1);
          if (seenId.get(id) > 1) {
            rowErrors.act_category_id = t(
              "setGoals.validateForm.actDuplicateCheck"
            );
            isValid = false;
          }
        }
      }

      if (Object.keys(rowErrors).length > 0) {
        errors.value[row.key] = rowErrors;
      }
    });

  return isValid;
}

/**
 * 登録処理
 */
const saveHabits = async () => {
  if (!validateForm()) {
    myToast.value?.show(
      "warn",
      t("setGoals.myToast.inputError"),
      t("setGoals.myToast.requiredErrorMsg"),
      3000
    );
    return;
  }

  try {
    mySpinner.value.setData(true, t("mySpinnerMsg.loading"));
    const payload = rows.value.map((r) => ({
      ...r.data,
    }));

    const response = await axios.post("/api/habit/goal/upsert", payload);
    if (response.data.status == "ok") {
      const fetchedData = response.data.habitGoals;
      if (Array.isArray(fetchedData) && fetchedData.length > 0) {
        setHabitGoals(fetchedData);
      }
      myToast.value?.show(
        "info",
        t("setGoals.myToast.summarySuccess"),
        t("setGoals.myToast.successMsg"),
        3000
      );
    }
  } catch (err) {
    console.error(err);
    myToast.value?.show(
      "error",
      t("setGoals.myToast.summaryFailure"),
      t("setGoals.myToast.failureMsg"),
      3000
    );
  } finally {
    mySpinner.value.setData(false, "");
  }
};

/**
 * 習慣化目標データ取得
 */
const getData = async (url, isInit) => {
  try {
    mySpinner.value.setData(true, t("mySpinnerMsg.loading"));

    const response = await axios.get(url);
    if (response.data.status == "ok") {
      if (isInit) {
        actCategoryMaster.value = response.data.actCategories;
      }
      const fetchedData = response.data.habitGoals;
      if (Array.isArray(fetchedData) && fetchedData.length > 0) {
        setHabitGoals(fetchedData);
      }
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
  } catch (error) {
    console.error(t("setGoals.myToast.dataGetFailure") + ":", error);
    myToast.value?.show(
      "error",
      t("setGoals.myToast.summaryFailure"),
      t("setGoals.myToast.dataGetFailure"),
      3000
    );
  } finally {
    if (!Array.isArray(rows.value) || rows.value.length === 0) {
      addRow();
    }
    mySpinner.value.setData(false, "");
  }
};

/**
 * 取得した目標データをテーブルデータにセットする
 */
const setHabitGoals = (fetchedData) => {
  rows.value = fetchedData.map((item) => ({
    key: item.id,
    data: {
      id: item.id,
      is_linked: item.is_linked,
      act_category_id: item.act_category_id ?? null,
      color_name: item.is_linked
        ? setFromActCategory(
            "color_name",
            actCategoryMaster,
            item.act_category_id
          )
        : item.color_name,
      hex_color_code: item.is_linked
        ? setFromActCategory(
            "hex_color_code",
            actCategoryMaster,
            item.act_category_id
          )
        : item.hex_color_code,
      title: item.is_linked
        ? setFromActCategory(
            "activity",
            actCategoryMaster,
            item.act_category_id
          )
        : item.title,
      detail: item.detail ?? null, // 詳細内容
      duration_minutes: item.duration_minutes ?? 0, // 所要時間
      deleted_at: false,
      can_delete: item.can_delete,
    },
  }));
};
/**
 * ActCategoryの情報を設定する
 */
const setFromActCategory = (key, data, actID) => {
  const matchData = data.value.find((item) => item.id === actID);
  return matchData ? matchData[key] : null;
};
</script>

<style scoped></style>
