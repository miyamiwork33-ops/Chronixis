<template>
  <div>
    <h2 class="text-xl font-bold mb-4">
      <!-- 行動カテゴリマスタ管理 -->
      {{ t("menu.label.scheduleItems.actCategoryMaster") }}
    </h2>
    <div class="grid">
      <!-- 追加ボタン -->
      <div class="col-6">
        <Button
          :label="$t('actCategoryMaster.addButton')"
          icon="pi pi-plus"
          class="p-button-sm p-button-success"
          @click="addRow"
        />
      </div>
      <!-- 登録ボタン -->
      <div class="col-6 text-right">
        <Button
          :label="$t('actCategoryMaster.registrationButton')"
          icon="pi pi-save"
          class="p-button-primary"
          @click="saveCategories"
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
      <!-- 色列 -->
      <Column
        field="color"
        :header="t('actCategoryMaster.tableHeader.color')"
        style="width: 24rem"
      >
        <template #body="{ node }">
          <div class="flex items-center gap-2">
            <!-- 色スウォッチ（選択中の色を左に表示） -->
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

            <!-- 色選択セレクト -->
            <Select
              v-model="node.data.color_name"
              :options="auraColors"
              optionLabel="color_name"
              optionValue="color_name"
              style="width: 14rem"
              @change="updateHexColor(node)"
            >
              <!-- ドロップダウンのオプション -->
              <template #option="{ option }">
                <div class="flex items-center gap-2">
                  <span>{{ option.color_name }}</span>
                </div>
              </template>
            </Select>
          </div>
          <span
            v-if="errors[node.key]?.color_name"
            class="text-red-500 text-sm"
          >
            {{ errors[node.key].color_name }}
          </span>
        </template>
      </Column>

      <!-- 行動内容列 -->
      <Column
        field="activity"
        :header="t('actCategoryMaster.tableHeader.activity')"
      >
        <template #body="{ node }">
          <InputText
            v-model="node.data.activity"
            :placeholder="t('actCategoryMaster.activityExample')"
            class="w-full"
            maxlength="30"
          />
          <span v-if="errors[node.key]?.activity" class="text-red-500 text-sm">
            {{ errors[node.key].activity }}
          </span>
        </template>
      </Column>

      <!-- 削除列 -->
      <Column
        :header="t('actCategoryMaster.tableHeader.deletion')"
        style="width: 80px"
      >
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

  <!-- モーダル -->
  <Dialog
    v-model:visible="showModal"
    modal
    :header="$t('actCategoryMaster.dialog.title')"
    :style="{ width: '30rem' }"
  >
    <p>
      {{ $t("actCategoryMaster.dialog.syncFailureMessage") }}
      <br />
      {{ $t("actCategoryMaster.dialog.updateConfirmationMessage") }}
    </p>
    <template #footer>
      <Button
        :label="$t('actCategoryMaster.dialog.button.close')"
        severity="secondary"
        @click="showModal = false"
      />
      <Button
        :label="$t('actCategoryMaster.dialog.button.reload')"
        severity="warn"
        variant="outlined"
        @click="reload"
      />
    </template>
  </Dialog>

  <MySpinner ref="mySpinner" />
  <MyToast ref="myToast" />
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "@/plugins/axios";
import Button from "primevue/button";
import Select from "primevue/select";
import InputText from "primevue/inputtext";
import TreeTable from "primevue/treetable";
import Column from "primevue/column";
import { auraColors } from "@/data/auraColors.js";
import { getTextColor } from "@/utils/colorUtils.js";
import { useI18n } from "vue-i18n";
import Dialog from "primevue/dialog";
import MyToast from "@/components/MyToast.vue";
import MySpinner from "@/components/MySpinner.vue";

const myToast = ref(null);
const mySpinner = ref(null);
const rows = ref([]);
const errors = ref({});
const { t } = useI18n();
const showModal = ref(false);
let keyCounter = 0;

/**
 * 初期表示にデータ取得処理
 *
 */
onMounted(() => {
  mySpinner.value.setData(true, t("mySpinnerMsg.loading"));
  getData();
});

/**
 * 行動カテゴリマスタデータ取得処理
 */
const getData = async () => {
  try {
    const response = await axios.get("/api/categories/getAllData");
    const fetchedData = response.data.categories;

    if (response.data.status == "ok") {
      rows.value = fetchedData.map((item) => ({
        key: item.id,
        data: {
          id: item.id,
          color_name: item.color_name,
          hex_color_code: item.hex_color_code,
          activity: item.activity,
          can_delete: item.can_delete,
        },
      }));
    } else {
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
    }
    if (!Array.isArray(rows.value) || rows.value.length === 0) {
      rows.value = [
        {
          key: generateUniqueKey(),
          data: {
            id: null,
            color_name: auraColors[0].color_name,
            hex_color_code: auraColors[0].hex_color_code,
            activity: "",
            can_delete: true,
          },
        },
      ];
    }
  } catch (error) {
    console.error(
      t("actCategoryMaster.myToast.dataGetFailureMsg") + ":",
      error
    );
    myToast.value?.show(
      "error",
      t("actCategoryMaster.myToast.summaryFailure"),
      t("actCategoryMaster.myToast.dataGetFailureMsg"),
      5000
    );
  } finally {
    mySpinner.value.setData(false, "");
  }
};

/**
 * バリデーション
 *
 */
function validateForm() {
  errors.value = {}; // 初期化
  let isValid = true;

  // hex_color_codeの重複チェック
  const colorCounts = new Map();
  rows.value.forEach((row) => {
    const code = row.data.hex_color_code;
    colorCounts.set(code, (colorCounts.get(code) || 0) + 1);
  });

  rows.value.forEach((row, index) => {
    const rowErrors = {};

    // 行動内容の必須チェック
    if (!row.data.activity || row.data.activity.trim() === "") {
      rowErrors.activity = t("actCategoryMaster.validateForm.actRequiredCheck");
      isValid = false;
    }

    // 色の選択の必須チェック
    if (!row.data.hex_color_code || row.data.hex_color_code.trim() === "") {
      rowErrors.hex_color_code = t(
        "actCategoryMaster.validateForm.colorRequiredCheck"
      );
      isValid = false;
    }

    // 重複している場合は全ての行にエラーを設定
    const code = row.data.hex_color_code;
    if (colorCounts.get(code) > 1) {
      rowErrors.color_name = t(
        "actCategoryMaster.validateForm.colorDuplicateCheck"
      );
      isValid = false;
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
const saveCategories = async () => {
  if (!validateForm()) {
    myToast.value?.show(
      "warn",
      t("actCategoryMaster.myToast.inputError"),
      t("actCategoryMaster.myToast.requiredErrorMsg"),
      3000
    );
    return;
  }

  try {
    mySpinner.value.setData(true, t("mySpinnerMsg.loading"));
    const payload = rows.value.map((r) => ({
      ...r.data,
      text_color_code: getTextColor(r.data.hex_color_code),
    }));

    const response = await axios.post("/api/categories/upsert", payload);

    if (response.data.status == "ok") {
      myToast.value?.show(
        "success",
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
  } catch (err) {
    console.error(err);
    myToast.value?.show(
      "error",
      t("actCategoryMaster.myToast.summaryFailure"),
      t("actCategoryMaster.myToast.failureMsg"),
      5000
    );
    showModal.value = true;
  } finally {
    mySpinner.value.setData(false, "");
  }
};

/**
 * リロード処理
 */
const reload = async () => {
  showModal.value = false;
  mySpinner.value.setData(true, t("actCategoryMaster.reloadSpinner"));
  getData();
};

/**
 * 行追加
 */
const addRow = () => {
  if (!auraColors || auraColors.length === 0) {
    console.error(t("actCategoryMaster.myToast.noColorDataMsg"));
    myToast.value?.show(
      "error",
      t("actCategoryMaster.myToast.summaryFailure"),
      t("actCategoryMaster.myToast.noColorDataMsg"),
      5000
    );
    return;
  }

  rows.value.push({
    key: generateUniqueKey(),
    data: {
      id: null,
      color_name: auraColors[0].color_name,
      hex_color_code: auraColors[0].hex_color_code,
      activity: "",
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
  const selected = auraColors.find(
    (c) => c.color_name === node.data.color_name
  );
  if (selected) {
    node.data.hex_color_code = selected.hex_color_code;
  }
};

/**
 * ユニークキーを生成する
 */
const generateUniqueKey = () => {
  return `${Date.now()}_${keyCounter++}`;
};
</script>

<style scoped></style>
