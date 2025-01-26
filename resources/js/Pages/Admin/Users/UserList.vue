<script setup>
import CustomToast from "@/Components/CustomToast.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ref, onMounted, reactive, computed } from "vue";
import { useToast } from "primevue/usetoast";
import { master } from "@/Utils/action";
import moment from "moment";
import axios from "axios";
import { formatedFileSize } from "@/Utils/action";
import Loader from "@/Components/Loader.vue";

const props = defineProps({
  users: Object,
  filter: Object,
});

const toast = useToast();
const userList = ref([]);
const count = ref(0);
const visibleRemoveDialog = ref(false);
const organizationList = ref([]);
const excelActionVisible = ref(false);

onMounted(async () => {
  let breakTimes = await master("breakTime");
  if (breakTimes?.data) {
    organizationList.value = breakTimes.data.map((item) => {
      item.updated_at = moment(item.updated_at).format("yyyy/MM/DD HH:mm:ss");
      return item;
    });
  }
  if (props.users) {
    userList.value = props.users.data;
    count.value = props.users.total;
  }
  if (props.filter?.visible == 1) {
    // keeping the search pannel after search
    searchSidebarVisible.value = true;
  }
});

const searchForm = useForm({
  id: props.filter?.id,
  code: props.filter?.code,
  name: props.filter?.name,
  email: props.filter?.email,
  status: props.filter?.status ? parseInt(props.filter?.status) : null,
  organization: props.filter?.organization,
  startDate: props.filter?.startDate,
  endDate: props.filter?.endDate,
  visible: props.filter?.visible ?? 1,
  manager: props.filter?.manager ? parseInt(props.filter?.manager) : null,
});
const deleteForm = useForm({
  id: null,
});
const deleteConfirmUser = (id) => {
  deleteForm.id = id;
  visibleRemoveDialog.value = true;
};
const toastBack = ref();
const removeUser = () => {
  deleteForm.delete(route("admin.users.destroy"), {
    onSuccess: () => {
      toastBack.value = "bg-green-500/70";
      toast.add({
        severity: "custom",
        summary: "削除成功！",
        life: 2000,
        group: "headless",
      });
      userList.value = props.users.data.filter((filter) => filter.id !== deleteForm.id);
    },
    onErrorr: () => {
      toastBack.value = "bg-red-500/70";
      toast.add({
        severity: "custom",
        summary: "削除失敗！",
        life: 2000,
        group: "headless",
      });
    },
  });
  visibleRemoveDialog.value = false;
};

// search action
const searchSidebarVisible = ref(false);
const searchUsers = () => {
  searchForm.get(route("admin.users.index"));
};

const exportFile = (type) => {
  axios
    .post(route(`admin.users.export`), { type: type })
    .then((res) => {
      console.log(res);
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
    });
};

const uploadConfirmVisible = ref(false);
const csvFormData = ref();
const csvFile = useForm({
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
  csvFormData.value = new FormData();
  csvFormData.value.append("file", file);
  csvFile.name = file.name;
  csvFile.size = file.size;
  excelActionVisible.value = false;
  uploadConfirmVisible.value = true;
  // reset
  document.getElementById("upload-file").setAttribute("type", "text");
  document.getElementById("upload-file").setAttribute("type", "file");
};

const progressPer = ref(0);
const importAction = async () => {
  await axios
    .post(route("admin.users.import"), csvFormData.value, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
      onUploadProgress: (progress) => {
        if (progress.total > 0) {
          progressPer.value = (progress.loaded * 100) / progress.total;
        }
      },
    })
    .then((res) => {
      toast.add({
        severity: "success",
        summary: "操作成功！",
        detail: "アップロードされました。",
        life: 2000,
      });
      location.reload();
    })
    .catch((error) => {
      toast.add({
        severity: "error",
        summary: "操作失敗！",
        detail: error.message,
        life: 2000,
      });
    });
};

const completedAction = () => {
  uploadConfirmVisible.value = false;
  progressPer.value = 0;
};
</script>
<template>
  <CustomToast group="headless" :bgClass="toastBack" />
  <AdminLayout title="ユーザー管理">
    <MainContent>
      <template #header>
        <FontAwesomeIcon icon="fa-solid fa-users" />
        <h3>ユーザー管理</h3>
      </template>
      <ContentBox title="ユーザー一覧">
        <template #header>
          <div class="flex items-center justify-between w-full">
            <h3>
              <FontAwesomeIcon icon="fa-solid fa-user-group" />
              ユーザー一覧
            </h3>
            <div class="flex items-center gap-2 btn_group">
              <Link :href="route('admin.users.create')" class="">
                <Button
                  label="新規作成"
                  icon="pi pi-plus"
                  size="small"
                  class="py-1"
                  severity="danger"
                />
              </Link>
              <Button
                label="サーチ"
                icon="pi pi-search"
                size="small"
                class="py-1"
                severity="info"
                @click="searchSidebarVisible = !searchSidebarVisible"
              />
              <Link :href="route('admin.users.index')">
                <Button
                  label="リセット"
                  icon="pi pi-refresh"
                  size="small"
                  class="py-1"
                  severity="secondary"
                />
              </Link>
              <Button
                label="インポート・エクスポート"
                icon="pi pi-download"
                size="small"
                severity="info"
                @click="excelActionVisible = true"
                class="shrink-0 py-1"
              />
            </div>
          </div>
        </template>
        <div v-if="searchSidebarVisible" class="search-pannel">
          <form @submit.prevent="searchUsers" class="w-full p-8">
            <div class="grid grid-cols-4 gap-4">
              <div class="mt-2 id-field">
                <InputLabel value="ID" />
                <div class="w-full p-input-icon-left">
                  <i class="pi pi-hashtag"></i>
                  <InputText
                    v-model="searchForm.id"
                    class="w-full p-inputtext-sm"
                    placeholder="IDで検索"
                  />
                </div>
              </div>

              <div class="mt-2 code-field">
                <InputLabel value="ユーザーコード" />
                <div class="w-full p-input-icon-left">
                  <i class="pi pi-id-card"></i>
                  <InputText
                    v-model="searchForm.code"
                    class="w-full p-inputtext-sm"
                    placeholder="ユーザーコードで検索"
                  />
                </div>
              </div>

              <div class="mt-2 name-field">
                <InputLabel value="ユーザー名" />
                <div class="w-full p-input-icon-left">
                  <i class="pi pi-user"></i>
                  <InputText
                    v-model="searchForm.name"
                    class="w-full p-inputtext-sm"
                    placeholder="ユーザー名で検索"
                  />
                </div>
              </div>

              <div class="mt-2 email-field">
                <InputLabel value="メールアドレス" />
                <div class="w-full p-input-icon-left">
                  <i class="pi pi-at"></i>
                  <InputText
                    v-model="searchForm.email"
                    class="w-full p-inputtext-sm"
                    placeholder="メールアドレスで検索"
                  />
                </div>
              </div>

              <div class="mt-2 organization-field">
                <InputLabel value="勤務形態コード" />
                <div class="w-full p-input-icon-left">
                  <i class="pi pi-building"></i>
                  <Dropdown
                    v-model="searchForm.organization"
                    :options="organizationList"
                    show-clear
                    filter
                    class="w-full"
                    placeholder="勤務形態コードで検索"
                    empty-message="データなし"
                    empty-filter-message="データなし"
                    filterLocale="ja"
                    dataKey="id"
                    :filterFields="[
                      'break_work_pattern_cd',
                      'break_organization',
                      'break_name',
                    ]"
                  >
                    <template #value="slotProps">
                      <div v-if="slotProps.value" class="flex items-center gap-2">
                        <div class="flex items-center">
                          <i class="pi pi-palette"></i>
                        </div>
                        <div>
                          <span>{{ slotProps.value.break_work_pattern_cd }}</span>
                          <span>{{
                            slotProps.value.organization?.organization_name
                          }}</span>
                          <span>{{ slotProps.value.break_name }}</span>
                        </div>
                      </div>
                      <div v-else class="flex items-center gap-1">
                        <i class="pi pi-palette"></i>
                        {{ slotProps.placeholder }}
                      </div>
                    </template>
                    <template #option="slotProps">
                      <div class="flex items-center gap-1">
                        <span>{{ slotProps.option.break_work_pattern_cd }}</span>
                        <span>{{
                          slotProps.option.organization?.organization_name
                        }}</span>
                        <span>{{ slotProps.option.break_name }}</span>
                      </div>
                    </template>
                  </Dropdown>
                </div>
              </div>

              <div class="mt-4 register-date-field">
                <InputLabel value="登録日～" />
                <div class="w-full">
                  <VueDatePicker
                    v-model="searchForm.startDate"
                    locale="ja"
                    format="yyyy/MM/dd"
                    modelType="yyyy/MM/dd"
                    :enable-time-picker="false"
                    autoApply
                    placeholder="登録日で検索"
                  />
                </div>
              </div>

              <div class="mt-4 register-date-field">
                <InputLabel value="～登録日" />
                <div class="w-full">
                  <VueDatePicker
                    v-model="searchForm.endDate"
                    locale="ja"
                    format="yyyy/MM/dd"
                    modelType="yyyy/MM/dd"
                    :enable-time-picker="false"
                    autoApply
                    placeholder="登録日で検索"
                  />
                </div>
              </div>
            </div>

            <div class="mt-2 status-field flex items-center gap-8">
              <div class="status">
                <InputLabel value="認証状態" />
                <div class="flex items-center w-full gap-4 mt-2 text-sm">
                  <div class="flex items-center">
                    <RadioButton
                      inputId="available"
                      v-model="searchForm.status"
                      name="login_access"
                      :value="1"
                    />
                    <label for="available" class="pl-3">有効</label>
                  </div>

                  <div class="flex items-center">
                    <RadioButton
                      inputId="unavailable"
                      v-model="searchForm.status"
                      name="login_access"
                      :value="0"
                    />
                    <label for="unavailable" class="pl-3">無効</label>
                  </div>
                </div>
              </div>
              <div class="manager-check">
                <InputLabel value="ユーザー区分" />
                <div class="flex items-center w-full gap-4 mt-2 text-sm">
                  <div class="flex items-center">
                    <RadioButton
                      inputId="manager"
                      v-model="searchForm.manager"
                      name="manager_check"
                      :value="1"
                    />
                    <label for="manager" class="pl-3">管理者</label>
                  </div>

                  <div class="flex items-center">
                    <RadioButton
                      inputId="unmanager"
                      v-model="searchForm.manager"
                      name="manager_check"
                      :value="2"
                    />
                    <label for="unmanager" class="pl-3">ユーザー</label>
                  </div>
                </div>
              </div>
            </div>
            <div class="mt-4 btn-field">
              <Button
                type="submit"
                label="検索する"
                icon="pi pi-search"
                size="small"
                class="w-full"
              />
            </div>
          </form>
        </div>
        <div class="w-full border datatable center">
          <DataTable
            :value="userList"
            data-key="id"
            selectionMode="multiple"
            class="p-datatable-sm"
          >
            <Column field="id" header="ID" sortable />
            <Column field="code" header="ユーザーコード" sortable />
            <Column field="name" header="ユーザー名" sortable />
            <Column field="email" header="メールアドレス" sortable />
            <Column header="ログインユーザー設定" sortable>
              <template #body="slotProps">
                {{ slotProps.data.available ? "YES" : "NO" }}
              </template>
            </Column>
            <Column header="管理者" sortable>
              <template #body="slotProps">
                {{ slotProps.data.role == 1 ? "管理者" : "ユーザー" }}
              </template>
            </Column>
            <Column header="勤務形態コード" sortable>
              <template #body="slotProps">
                {{ slotProps.data?.user_data?.break_times?.break_work_pattern_cd }}
                {{
                  slotProps.data?.user_data?.break_times?.organization?.organization_name
                }}
                {{ slotProps.data?.user_data?.break_times?.break_name }}
              </template>
            </Column>
            <Column header="操作">
              <template #body="slotProps">
                <div class="flex items-center justify-center gap-3">
                  <Link :href="route('admin.users.show', { id: slotProps.data.id })">
                    <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                  </Link>
                  <Link :href="route('admin.users.edit', { id: slotProps.data.id })">
                    <FontAwesomeIcon
                      icon="fa-solid fa-pen-to-square"
                      class="text-teal-500"
                    />
                  </Link>
                  <FontAwesomeIcon
                    icon="fa-solid fa-trash-can"
                    class="text-rose-500"
                    @click="deleteConfirmUser(slotProps.data.id)"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </div>
      </ContentBox>
    </MainContent>
    <div class="flex items-center justify-center px-6 mt-6">
      <LinkPagination :data="users" />
    </div>

    <!-- Dialog to confirmation for removing user -->
    <Dialog
      v-model:visible="visibleRemoveDialog"
      modal
      dismissable-mask
      :draggable="false"
      class="w-96"
    >
      <template #header>
        <span class="text-lg font-bold text-red-600">削除しますか？</span>
      </template>
      <div class="w-full text-center">
        <i class="text-5xl text-red-500 pi pi-info-circle"></i>
        <p class="text-xl font-bold text-red-500">本当に削除しますか？</p>
        <div class="flex items-center justify-center w-full gap-4 mt-4">
          <Button
            label="いいえ"
            class="w-24 shrink-0"
            severity="secondary"
            @click="visibleRemoveDialog = false"
          />
          <Button
            label="はい"
            class="w-24 shrink-0"
            severity="success"
            @click="removeUser"
          />
        </div>
      </div>
    </Dialog>
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
            <p>
              <i class="pi pi-check"></i>

              <span> エクスポート</span>
            </p>
            <div class="flex gap-4 mt-4">
              <FontAwesomeIcon icon="fa-solid fa-table" class="text-5xl text-green-600" />
              <div class="text-sm flex flex-col justify-between">
                <p>
                  現在表示しているビューの形式で、すべてのデータをエクスポートします。
                </p>
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
          </div>
          <div class="import">
            <p>
              <i class="pi pi-check"></i>
              <span>インポート</span>
            </p>
            <div class="flex gap-4 mt-4">
              <FontAwesomeIcon icon="fa-solid fa-table" class="text-5xl text-rose-500" />
              <div class="text-sm">
                <p>
                  アップロードするファイルは、ダウンロードしたCSVファイル形式でなければなりません。
                </p>
                <div class="mt-4 flex justify-end">
                  <Button severity="danger" class="p-0" size="small">
                    <input
                      type="file"
                      id="upload-file"
                      class="hidden"
                      accept=".csv"
                      @change="choosingCsvFile"
                    />
                    <label for="upload-file" class="flex items-center gap-2 p-2">
                      <i class="pi pi-upload" />
                      <span>インポート</span>
                    </label>
                  </Button>
                </div>
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
      @hide="completedAction"
    >
      <template #header>
        <div class="flex items-center gap-2 text-xl text-teal-500 font-bold">
          <i class="pi pi-file-excel text-2xl"></i>
          <span>CSVインポート</span>
        </div>
      </template>
      <div class="text-center">
        <div class="file__details text-center font-bold">
          <p>{{ csvFile.name }}</p>
          <p>{{ formatedFileSize(csvFile.size) }}</p>
        </div>
        <div>
          <div v-if="progressPer == 100" class="completed">
            <div class="progress-bar my-2">
              <p class="py-1 font-bold">完了</p>
              <ProgressBar :value="progressPer" style="height: 14px" />
            </div>
            <div class="upload_btn__action mt-2 flex items-center justify-center gap-4">
              <Button
                label="閉じる"
                severity="info"
                class="w-40"
                @click="completedAction"
              />
            </div>
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
  </AdminLayout>
</template>
