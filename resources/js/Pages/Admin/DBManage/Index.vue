<script setup>
import { ref, reactive, onMounted } from "vue";
import { useToast } from "primevue/usetoast";
import axios from "axios";
import moment from "moment";
import { formatedFileSize } from "@/Utils/action";
import { Inertia } from "@inertiajs/inertia";

const props = defineProps({
  backupList: Object,
});

const backupData = ref([]);
if (props.backupList) {
  backupData.value = props.backupList;
}
const toast = useToast();
const uploadConfirmVisible = ref(false);
const downloadVisible = ref(false);
const migrationVisible = ref(false);
const uploadProgressValue = ref(0);

const fileRef = reactive({
  file: null,
  name: null,
  size: null,
});
// Method
const backupAction = () => {
  downloadVisible.value = true;
  axios
    .post(route("admin.dbmanage.backup"))
    .then((res) => {
      if (res.data?.path) {
        location.href = route("file_download", {
          file_path: res.data?.path,
          undelete: true,
        });
      }
    })
    .catch((error) => {
      toast.add({
        severity: "error",
        summary: "DBバックアップ失敗！",
        detail: "もう一度お試しください。",
        life: 2000,
      });
    })
    .finally(() => {
      downloadVisible.value = false;
      Inertia.reload();
    });
};
const chooseSqlFile = (e) => {
  let file = e.target.files[0];
  if (file.size == 0) {
    toast.add({
      severity: "error",
      summary: "ファイルアップロード失敗！",
      detail: "ファイルサイズは OKbyteです。",
      life: 2000,
    });
    return;
  }
  if (file.name.slice(-3) !== "sql") {
    toast.add({
      severity: "error",
      summary: "ファイルアップロード失敗！",
      detail: "SQLファイルをアップロードしてください。",
      life: 2000,
    });
    return;
  }
  fileRef.file = file;
  fileRef.name = file.name;
  fileRef.size = file.size;
  uploadConfirmVisible.value = true;
  document.getElementById("choose-sql").setAttribute("type", "hidden");
  document.getElementById("choose-sql").setAttribute("type", "file");
};

const uploadAction = () => {
  let formData = new FormData();
  if (fileRef.file) {
    formData.append("file", fileRef.file);
  }
  axios
    .post(route("admin.dbmanage.restore"), formData, {
      onUploadProgress: (progress) => {
        uploadProgressValue.value = Math.round((progress.loaded * 100) / progress.total);
      },
    })
    .then((res) => {
      successMethod();
    })
    .catch((error) => {
      failureMethod();
    })
    .finally(() => {
      uploadProgressValue.value = 0;
      uploadConfirmVisible.value = false;
    });
};

const migration = (path) => {
  migrationVisible.value = true;
  axios
    .post(route("admin.dbmanage.restore"), { path: path })
    .then((res) => {
      successMethod();
    })
    .catch((e) => {
      failureMethod();
    })
    .finally(() => {
      migrationVisible.value = false;
    });
};

const successMethod = () => {
  toast.add({
    severity: "success",
    summary: "作業完了！",
    life: 1000,
  });
  setTimeout(() => {
    Inertia.reload();
  }, 1000);
};
const failureMethod = () => {
  toast.add({
    severity: "error",
    summary: "操作失敗！",
    detail: "マイグレーション中にエラーが発生しました。",
    life: 2000,
  });
};
</script>
<template>
  <AdminLayout title="DB Management">
    <Toast position="top-center" />
    <Loader v-if="downloadVisible" title="DBバックアップ中" />
    <Loader v-if="migrationVisible" title="DBマイグレーション中" />
    <div class="database">
      <div class="w-full p-6">
        <div class="flex items-center gap-8">
          <div class="database-backup">
            <Button icon="pi pi-cloud-download" label="Backup" @click="backupAction" />
          </div>
          <div class="database-backup">
            <Button class="p-0" severity="danger">
              <label for="choose-sql" class="flex items-center gap-2 p-2">
                <input
                  type="file"
                  id="choose-sql"
                  class="hidden"
                  accept=".sql"
                  @change="chooseSqlFile"
                />
                <i class="pi pi-cloud-upload"></i>
                <span>Restore</span>
              </label>
            </Button>
          </div>
        </div>
        <div class="list mt-8">
          <div
            v-if="backupData.length > 0"
            class="backup-list max-h-[calc(100vh-210px)] border overflow-auto rounded-lg bg-gray-100 shadow-lg p-3"
          >
            <div v-for="(item, index) in backupList" :key="index" class="border-b py-1">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <p>{{ index + 1 }}.</p>
                  <p>
                    <span>{{ item.name }}</span>
                    <span class="pl-4">
                      {{ moment(item.date).format("yyyy-MM-DD HH:mm:ss") }}
                    </span>
                    <span class="pl-4">
                      {{ formatedFileSize(item?.size) }}
                    </span>
                  </p>
                </div>
                <div class="action-group flex items-center gap-2">
                  <a
                    :href="$page.props.assetUrl + 'storage/backup/' + item.name"
                    download
                    v-tooltip.left="'ダウンロード'"
                  >
                    <Button icon="pi pi-download" severity="info" />
                  </a>
                  <Button
                    v-tooltip.left="'このファイルに直接移行する'"
                    icon="pi pi-database"
                    severity="success"
                    @click="migration(item.name)"
                  />
                </div>
              </div>
            </div>
          </div>
          <div v-else>
            <p class="">SQLファイルが存在しません。</p>
          </div>
        </div>
      </div>
    </div>
    <Dialog
      v-model:visible="uploadConfirmVisible"
      :show-header="false"
      modal
      :dragable="false"
      class="w-full max-w-lg overflow-hidden"
      content-class="rounded-lg"
    >
      <div class="w-full text-center">
        <i class="pi pi-database text-5xl text-sky-600"></i>
        <div v-if="uploadProgressValue > 0" class="uploading-process mt-3 text-center">
          <div v-if="uploadProgressValue < 100">
            <p>
              <span>{{ uploadProgressValue }}</span>
              <span>%</span>
            </p>
            <p>Uploading...</p>
            <ProgressBar :value="uploadProgressValue" style="height: 14px" />
          </div>
          <div v-else class="text-center flex flex-col items-center">
            <Spinner />
            <p>処理中...</p>
            <p>進行中のタスクを終了しないでください。</p>
          </div>
        </div>
        <div class="mt-4 border-y p-3 text-start">
          <p>
            <span>ファイル名：</span>
            <span>{{ fileRef.name }}</span>
          </p>
          <p>
            <span>ファイル名：</span>
            <span>{{ formatedFileSize(fileRef.size) }}</span>
          </p>
        </div>
        <div class="mt-4 flex items-center justify-end gap-4">
          <Button
            label="Cancel"
            :disabled="uploadProgressValue"
            severity="secondary"
            class="w-20"
            @click="uploadConfirmVisible = false"
          />
          <Button
            label="OK"
            :disabled="uploadProgressValue"
            severity="success"
            class="w-20"
            @click="uploadAction"
          />
        </div>
      </div>
    </Dialog>
  </AdminLayout>
</template>
