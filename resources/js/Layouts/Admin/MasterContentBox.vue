<script setup>
import { useSlots, ref, reactive } from "vue";
import { useToast } from "primevue/usetoast";
import { Link, useForm } from "@inertiajs/vue3";
import axios from "axios";
import { formatedFileSize } from "@/Utils/action";
import Loader from "@/Components/Loader.vue";
import { Inertia } from "@inertiajs/inertia";

const props = defineProps({
  title: String,
  link: String,
  ids: Object,
});

const emit = defineEmits(["filter"]);
const toast = useToast();
const slots = useSlots();
const items = [
  {
    label: "一般検索",
    icon: "pi pi-search",
    command: () => {
      filterMode("global");
      filter.get(route(`admin.master.${props.link}.index`));
    },
  },
  {
    label: "詳細検索",
    icon: "pi pi-filter",
    command: () => {
      filter.key = null;
      filterMode("detail");
    },
  },
];

const urlParams = new URLSearchParams(window.location.search);
const filterModeVal = ref(urlParams.get("visible") == 1 ? "detail" : "global");
const filterMode = (mode) => {
  filterModeVal.value = mode;
  emit("filter", mode);
};
// filter action
const urlKeys = urlParams.get("key");
const filter = useForm({
  key: urlKeys,
  visible: 0,
});

const globalFilter = () => {
  filter.get(route(`admin.master.${props.link}.index`));
};

const excelActionVisible = ref(false);
const downloading = ref(false);
const exportFile = (type) => {
  downloading.value = true;
  axios
    .post(
      route(`admin.master.${props.link}.export`),
      { type: type, ids: props.ids },
      {
        onDownloadProgress: (progress) => {},
      }
    )
    .then((res) => {
      if (res.data?.path) {
        location.href = route("file_download", { file_path: res.data.path });
      }
      excelActionVisible.value = false;
    })
    .catch((error) => {
      toast.add({
        severity: "error",
        summary: "操作失敗！",
        detail: error.message,
        life: 2000,
      });
    })
    .finally(() => {
      downloading.value = false;
    });
};

const uploadConfirmVisible = ref(false);
const csvForm = useForm({
  file: null,
  name: null,
  size: null,
});
const choosingCsvFile = (e) => {
  let file = e.target.files[0];
  if (file.type != "text/csv") {
    toast.add({
      severity: "error",
      summary: "ファイル形式エラー！",
      detail: "CSVファイル形式のみサポートします。",
      life: 2000,
    });
    return;
  } else if (file.size == 0) {
    toast.add({
      severity: "error",
      summary: "破損したファイルです。",
      detail: "ファイルサイズを確認してください。",
      life: 2000,
    });
    return;
  }
  csvForm.file = file;
  csvForm.name = file.name;
  csvForm.size = file.size;
  excelActionVisible.value = false;
  uploadConfirmVisible.value = true;
  // reset
  document.getElementById("upload-file").setAttribute("type", "text");
  document.getElementById("upload-file").setAttribute("type", "file");
};

const importAction = () => {
  csvForm.post(route(`admin.master.${props.link}.import`), {
    onSuccess: () => {
      uploadConfirmVisible.value = false;
      toast.add({
        severity: "success",
        summary: "操作成功！",
        detail: "アップロードされました。",
        life: 2000,
      });
      setTimeout(() => {
        Inertia.reload();
      }, 500);
    },
    onError: (e) => {
      toast.add({
        severity: "error",
        summary: "操作失敗！",
        detail: e.message,
        life: 2000,
      });
    },
  });
};
</script>
<template>
  <Toast position="bottom-right" />
  <Loader v-if="downloading" title="ダウンロード中..." />
  <div class="field-box">
    <div class="w-full box-header relative">
      <div
        v-if="!slots.header"
        class="flex items-center flex-col gap-2 xl:flex-row justify-between box-title"
      >
        <div class="flex gap-2 p-buttonset w-full">
          <SplitButton
            :label="filterModeVal == 'detail' ? '詳細検索' : '一般検索'"
            :icon="filterModeVal == 'detail' ? 'pi pi-filter' : 'pi pi-search'"
            :model="items"
            size="small"
          />
          <div class="relative flex items-center overflow-hidden border rounded-md">
            <InputText
              v-model="filter.key"
              class="py-1 p-inputtext-sm w-72"
              @keyup.enter="globalFilter"
            />
            <Button
              icon="pi pi-search"
              class="absolute top-0 right-0 h-full"
              @click="globalFilter"
            />
          </div>
        </div>
        <div class="flex gap-2 w-full justify-end">
          <Link
            v-if="route().current() == 'admin.master.holiday.index'"
            :href="route('admin.master.holiday.calendar')"
          >
            <Button
              label="カレンダーから登録"
              icon="pi pi-calendar"
              size="small"
              class="shrink-0"
              severity="danger"
            />
          </Link>
          <Link :href="props.link ? route(`admin.master.${link}.create`) : '#'">
            <Button
              label="作成"
              icon="pi pi-plus"
              class="shrink-0"
              size="small"
              severity="success"
            />
          </Link>
          <Link :href="props.link ? route(`admin.master.${link}.index`) : '#'">
            <Button
              label="リセット"
              icon="pi pi-refresh"
              class="shrink-0"
              size="small"
              severity="secondary"
            />
          </Link>
          <Button
            label="インポート・エクスポート"
            icon="pi pi-download"
            size="small"
            severity="info"
            @click="excelActionVisible = true"
            class="shrink-0"
          />
        </div>
      </div>
      <slot name="header" />
    </div>
    <div class="box-content">
      <slot name="default" />
    </div>
  </div>
  <Dialog
    v-model:visible="excelActionVisible"
    header="インポート・エクスポート"
    modal
    dismissable-mask
    :draggable="false"
    position="top"
    class="w-full max-w-3xl mt-12"
  >
    <div class="w-full p-6">
      <div class="grid w-full grid-cols-2 gap-8">
        <div class="export">
          <div class="flex flex-col justify-between h-full">
            <div>
              <p>
                <i class="pi pi-check"></i>

                <span> エクスポート</span>
              </p>
              <div class="flex gap-4 mt-4">
                <FontAwesomeIcon
                  icon="fa-solid fa-table"
                  class="text-5xl text-green-600"
                />
                <div class="text-sm">
                  <p>
                    現在表示しているビューの形式で、すべてのデータをエクスポートします。
                  </p>
                </div>
              </div>
            </div>
            <div class="flex items-center justify-end gap-2 mt-4">
              <Button
                label="CSV"
                icon="pi pi-download"
                size="small"
                severity="success"
                @click="exportFile('csv')"
              />
              <Button
                label="EXCEL"
                icon="pi pi-download"
                size="small"
                severity="info"
                @click="exportFile('xlsx')"
              />
            </div>
          </div>
        </div>
        <div class="import">
          <div class="flex flex-col justify-between">
            <div>
              <p>
                <i class="pi pi-check"></i>
                <span>インポート</span>
              </p>
              <div class="flex gap-4 mt-4">
                <FontAwesomeIcon
                  icon="fa-solid fa-table"
                  class="text-5xl text-rose-500"
                />
                <div class="text-sm">
                  <p>
                    アップロードするファイルは、ダウンロードしたCSVファイル形式でなければなりません。
                  </p>
                </div>
              </div>
            </div>
            <div class="mt-4 flex justify-end">
              <Button severity="danger" class="p-0" size="small">
                <input
                  type="file"
                  id="upload-file"
                  class="hidden"
                  accept=".csv"
                  @change="choosingCsvFile"
                />
                <label
                  for="upload-file"
                  class="h-full w-full flex items-center gap-2 p-2"
                >
                  <i class="pi pi-upload" />
                  <span>インポート</span>
                </label>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Dialog>
  <Dialog
    v-model:visible="uploadConfirmVisible"
    modal
    dismissable-mask
    :draggable="false"
    class="w-full max-w-lg"
  >
    <template #header>
      <div class="flex items-center gap-2 text-xl text-teal-500 font-bold">
        <i class="pi pi-file-excel text-2xl"></i>
        <span>CSVインポート</span>
      </div>
    </template>
    <div class="text-center">
      <div class="file__details text-center font-bold">
        <p>{{ csvForm.name }}</p>
        <p>{{ formatedFileSize(csvForm.size) }}</p>
      </div>
      <div>
        <div
          v-if="csvForm.processing"
          class="uploading-progress flex flex-col items-center gap-2 pt-2"
        >
          <Spinner />
          <p>アップロード中</p>
        </div>
        <div v-else class="before-upload">
          <div class="notification__message">
            <p>上記のファイルをDBにアップロードしますか？</p>
          </div>
          <div class="upload_btn__action mt-2 flex items-center justify-center gap-4">
            <Button
              label="いいえ"
              icon="pi pi-times"
              severity="secondary"
              class="w-40"
              @click="uploadConfirmVisible = false"
            />
            <Button
              label="はい"
              icon="pi pi-upload"
              severity="success"
              class="w-40"
              @click="importAction"
            />
          </div>
        </div>
      </div>
    </div>
  </Dialog>
</template>
