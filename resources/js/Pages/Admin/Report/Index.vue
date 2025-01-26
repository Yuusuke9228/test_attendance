<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import { useToast } from "primevue/usetoast";
import axios from "axios";
import moment from "moment";

const props = defineProps({
  users: Object,
  locations: Object,
  organizations: Object,
});
const toast = useToast();

const loading = ref(false);
const visibleMonthPicker = ref(false);
const visibleDailyPicker = ref(false);
const visibleAttendPerLocationDialog = ref(false);
const visibleManPowerDialog = ref(false);
const visibleSupportFlgExport = ref(false);

const month = ref(moment().subtract(1, "month").format("yyyy/M/D"));
const targetDate = ref(moment().format("yyyy/M/D"));
const targetUser = ref();
const locationSelected = ref();
const organizationSelected = ref();
const allOrganizationVisible = ref(false);

// Method
const attendanceExportType = ref();
// 給与集計 出勤簿帳票
const openMonthPicker = (e) => {
  visibleMonthPicker.value = true;
  attendanceExportType.value = e;
};
// type 1 => 給与集計, type 2 => 出勤簿帳票, type 3 => 運転手・同乗者<br />月別一覧表
const exportAttendData = async (type) => {
  // if type is 1 給与集計, else type is 2 出勤簿帳票
  loading.value = true;
  visibleMonthPicker.value = false;
  let url;
  if (type == 1) {
    url = route("admin.report.monthly.data");
  } else if (type == 2) {
    url = route("admin.report.attend.book");
  } else {
    url = route("admin.report.driver");
  }
  await axios
    .post(url, { month: month.value })
    .then((res) => {
      if (res.data?.success) {
        if (res.data.path) {
          toast.add({
            severity: "success",
            summary: "出力成功！",
            life: 2000,
          });
          location.href = route("file_download", { file_path: res.data.path });
        }
      } else {
        toast.add({
          severity: "error",
          summary: "出力失敗！",
          detail: "データはありません。",
          life: 2000,
        });
      }
    })
    .catch((error) => {
      toast.add({
        severity: "error",
        summary: "出力失敗！",
        detail: error.response?.data.message,
        life: 2000,
      });
    })
    .finally(() => {
      loading.value = false;
    });
};

// 日報出力
const exportDailyReport = async () => {
  loading.value = true;
  visibleDailyPicker.value = false;
  await axios
    .post(
      route("admin.report.daily.data", { date: targetDate.value, user: targetUser.value })
    )
    .then((res) => {
      if (res.data?.success && res.data?.path) {
        toast.add({
          severity: "success",
          summary: "出力成功！",
          life: 2000,
        });
        location.href = route("file_download", { file_path: res.data.path });
      } else {
        toast.add({
          severity: "error",
          summary: "出力失敗！11",
          detail: "データはありません。",
          life: 2000,
        });
      }
    })
    .catch((e) => {
      let eMsg = "";
      if (e.response.status == 422) {
        eMsg = e.response.data?.message;
      }
      toast.add({
        severity: "error",
        summary: "出力失敗！",
        detail: eMsg,
        life: 2000,
      });
    })
    .finally(() => {
      loading.value = false;
    });
};

const exportAttendPerLocationData = async () => {
  loading.value = true;
  visibleAttendPerLocationDialog.value = false;
  await axios
    .post(
      route("admin.report.attend.per.location.data", {
        month: month.value,
        location: locationSelected.value,
      })
    )
    .then((res) => {
      if (res.data?.success) {
        toast.add({
          severity: "success",
          summary: "出力成功！",
          life: 2000,
        });
        if (res.data?.path) {
          location.href = route("file_download", { file_path: res.data.path });
        }
      } else {
        toast.add({
          severity: "error",
          summary: "出力失敗！",
          detail: "データはありません。",
          life: 2000,
        });
      }
    })
    .catch((e) => {
      let eMsg = "";
      if (e.response.status == 422) {
        eMsg = e.response.data?.message;
      }
      toast.add({
        severity: "error",
        summary: "出力失敗！",
        detail: eMsg,
        life: 2000,
      });
    })
    .finally(() => {
      loading.value = false;
    });
};

const exportManPowerData = async () => {
  loading.value = true;
  visibleManPowerDialog.value = false;
  await axios
    .post(
      route("admin.report.manpower.data", {
        month: month.value,
        organization: organizationSelected.value,
      })
    )
    .then((res) => {
      if (res.data?.success) {
        toast.add({
          severity: "success",
          summary: "出力成功！",
          life: 2000,
        });
        if (res.data?.path) {
          location.href = route("file_download", { file_path: res.data.path });
        }
      } else {
        toast.add({
          severity: "error",
          summary: "出力失敗！",
          detail: "データはありません。",
          life: 2000,
        });
      }
    })
    .catch((e) => {
      let eMsg = "";
      console.log(e);
      if (e.response.status == 422 || e.response.status == 500) {
        eMsg = e.response.data?.message;
      }
      toast.add({
        severity: "error",
        summary: "出力失敗！",
        detail: eMsg,
        life: 2000,
      });
    })
    .finally(() => {
      loading.value = false;
    });
};

const supportTypeFlg = ref(1);
const openSupportExportDialog = (e) => {
  visibleSupportFlgExport.value = true;
  supportTypeFlg.value = e;
};
const exportSupportData = async () => {
  loading.value = true;
  visibleSupportFlgExport.value = false;
  await axios
    .post(
      route("admin.report.supportFlg.data", {
        month: month.value,
        organization: organizationSelected.value,
        type: supportTypeFlg.value,
        allVisible: allOrganizationVisible.value,
      })
    )
    .then((res) => {
      if (res.data?.success) {
        toast.add({
          severity: "success",
          summary: "出力成功！",
          life: 2000,
        });
        if (res.data?.path) {
          location.href = route("file_download", { file_path: res.data.path });
        }
      } else {
        toast.add({
          severity: "error",
          summary: "出力失敗！",
          detail: "データはありません。",
          life: 2000,
        });
      }
    })
    .catch((e) => {
      let eMsg = "";
      if (e.response.status == 422 || e.response.status == 500) {
        eMsg = e.response.data?.message;
      }
      toast.add({
        severity: "error",
        summary: "出力失敗！",
        detail: eMsg,
        life: 2000,
      });
    })
    .finally(() => {
      loading.value = false;
    });
};
</script>
<template>
  <AdminLayout title="帳票管理">
    <Loader v-if="loading" title="処理中！" />
    <Toast position="bottom-center" />
    <MainContent title="帳票管理" icon="pi-table">
      <div class="w-full">
        <div class="p-4 mt-4 overflow-hidden bg-white rounded-md shadow-lg">
          <div class="grid w-full lg:grid-cols-3 2xl:grid-cols-6 gap-4">
            <div
              class="p-1 overflow-hidden rounded-lg btn-item group bg-gradient-to-tr from-teal-700 to-teal-400"
              @click="openMonthPicker(1)"
            >
              <div class="gap-4 p-2 btn-item__inner">
                <span class="item-label">給与集計</span>
              </div>
            </div>
            <div
              class="p-1 overflow-hidden rounded-lg btn-item group bg-gradient-to-tr from-teal-700 to-teal-400"
              @click="visibleDailyPicker = true"
            >
              <div class="gap-4 p-2 btn-item__inner">
                <span class="item-label">日報</span>
              </div>
            </div>
            <div
              class="p-1 overflow-hidden rounded-lg btn-item group bg-gradient-to-tr from-teal-700 to-teal-400"
            >
              <div class="gap-4 p-2 btn-item__inner" @click="openMonthPicker(2)">
                <span class="item-label">出勤簿帳票</span>
              </div>
            </div>
            <div
              class="p-1 overflow-hidden rounded-lg btn-item group bg-gradient-to-tr from-teal-700 to-teal-400"
              @click="openMonthPicker(3)"
            >
              <div class="gap-4 p-2 btn-item__inner">
                <span class="item-label">運転手・同乗者<br />月別一覧表</span>
              </div>
            </div>
            <div
              class="p-1 overflow-hidden rounded-lg btn-item group bg-gradient-to-tr from-teal-700 to-teal-400"
              @click="visibleAttendPerLocationDialog = true"
            >
              <div class="gap-4 p-2 btn-item__inner">
                <span class="item-label">現場別出勤表</span>
              </div>
            </div>
            <div
              class="p-1 overflow-hidden rounded-lg btn-item group bg-gradient-to-tr from-teal-700 to-teal-400"
              @click="visibleManPowerDialog = true"
            >
              <div class="gap-4 p-2 btn-item__inner">
                <span class="item-label">組織別人工表</span>
              </div>
            </div>
            <div
              class="p-1 overflow-hidden rounded-lg btn-item group bg-gradient-to-tr from-teal-700 to-teal-400"
              @click="openSupportExportDialog(1)"
            >
              <div class="gap-4 p-2 btn-item__inner" aria-label="supported_company">
                <span class="item-label">
                  応援情報出力
                  <br />
                  <small>（応援に行った）</small>
                </span>
              </div>
            </div>
            <div
              class="p-1 overflow-hidden rounded-lg btn-item group bg-gradient-to-tr from-teal-700 to-teal-400"
              @click="openSupportExportDialog(2)"
            >
              <div class="gap-4 p-2 btn-item__inner" aria-label="support_company">
                <span class="item-label">
                  応援情報出力
                  <br />
                  <small>（応援に来てもらった）</small>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </MainContent>
    <!-- 1. 給与集計モーダル -->
    <Dialog
      v-model:visible="visibleMonthPicker"
      header="対象月を選択してください。"
      modal
      dismissable-mask
      :draggable="false"
      class="w-full max-w-lg"
      contentClass="overflow-visible"
    >
      <div class="w-full p-4">
        <div class="month-picker">
          <VueDatePicker
            v-model="month"
            locale="ja"
            format="yyyy年M月"
            modelType="yyyy/M/d"
            month-picker
            autoApply
          />
        </div>
        <div class="mt-4 export-btn">
          <Button
            label="出力"
            size="small"
            class="w-full"
            severity="success"
            @click="exportAttendData(attendanceExportType)"
          />
        </div>
      </div>
    </Dialog>

    <!-- 2. 日報出力のためのモーダル（2日分の日付選択、ユーザー選択） -->
    <Dialog
      v-model:visible="visibleDailyPicker"
      header="対象日とユーザー名を選択してください。"
      modal
      dismissable-mask
      :draggable="false"
      class="w-full max-w-lg"
      contentClass="overflow-visible"
    >
      <div class="w-full p-4">
        <div class="month-picker">
          <VueDatePicker
            v-model="targetDate"
            locale="ja"
            format="yyyy年M月d日"
            modelType="yyyy/M/d"
            :enable-time-picker="false"
            autoApply
          />
          <p v-if="targetDate" class="text-sm text-gray-500">
            {{ moment(new Date(targetDate).toISOString()).format("M/D") }}～{{
              moment(new Date(targetDate).toISOString()).add(1, "d").format("M/D日")
            }}までの日報を出力します。
          </p>
        </div>
        <div class="mt-4">
          <Dropdown
            v-model="targetUser"
            :options="users"
            optionLabel="name"
            optionValue="id"
            class="w-full"
            scroll-height="480px"
            placeholder="ユーザー名を選択します。"
            filter
            show-clear
            empty-filter-message="結果なし"
            empty-message="結果なし"
          />
        </div>
        <div class="mt-4 export-btn">
          <Button
            label="出力"
            size="small"
            class="w-full"
            severity="success"
            @click="exportDailyReport"
          />
        </div>
      </div>
    </Dialog>

    <!-- 現場別出勤表出力（対象月、現場名指定） -->
    <Dialog
      v-model:visible="visibleAttendPerLocationDialog"
      header="対象月と現場を選択してください。"
      modal
      dismissable-mask
      :draggable="false"
      class="w-full max-w-lg"
      contentClass="overflow-visible"
    >
      <div class="w-full p-4">
        <div class="month-picker">
          <VueDatePicker
            v-model="month"
            locale="ja"
            format="yyyy年M月"
            modelType="yyyy/M/d"
            month-picker
            autoApply
          />
        </div>
        <div class="mt-4">
          <Dropdown
            v-model="locationSelected"
            :options="locations"
            optionLabel="location_name"
            optionValue="id"
            scroll-height="480px"
            class="w-full"
            placeholder="現場を選択してください"
            show-clear
          />
        </div>
        <div class="mt-4 export-btn">
          <Button
            label="出力"
            size="small"
            class="w-full"
            severity="success"
            @click="exportAttendPerLocationData"
          />
        </div>
      </div>
    </Dialog>

    <!-- 組織別人工表出力 （対象月、組織名指定）-->
    <Dialog
      v-model:visible="visibleManPowerDialog"
      header="対象月と組織を選択してください。"
      modal
      dismissable-mask
      :draggable="false"
      class="w-full max-w-lg"
      contentClass="overflow-visible"
    >
      <div class="w-full p-4">
        <div class="month-picker">
          <VueDatePicker
            v-model="month"
            locale="ja"
            format="yyyy年M月"
            modelType="yyyy/M/d"
            month-picker
            autoApply
          />
        </div>
        <div class="mt-4">
          <Dropdown
            v-model="organizationSelected"
            :options="organizations"
            optionLabel="organization_name"
            class="w-full"
            placeholder="組織を選択してください"
            scroll-height="480px"
            show-clear
          />
        </div>
        <div class="mt-4 export-btn">
          <Button
            label="出力"
            size="small"
            class="w-full"
            severity="success"
            @click="exportManPowerData"
          />
        </div>
      </div>
    </Dialog>

    <!-- 応援情報出力 -->
    <Dialog
      v-model:visible="visibleSupportFlgExport"
      header="対象月と組織を選択してください。"
      modal
      dismissable-mask
      :draggable="false"
      class="w-full max-w-lg"
      contentClass="overflow-visible"
    >
      <div class="w-full p-4">
        <div class="month-picker">
          <VueDatePicker
            v-model="month"
            locale="ja"
            format="yyyy年M月"
            modelType="yyyy/M/d"
            month-picker
            autoApply
          />
        </div>
        <div v-if="!allOrganizationVisible" class="mt-4">
          <Dropdown
            v-model="organizationSelected"
            :options="organizations"
            optionLabel="organization_name"
            class="w-full"
            placeholder="組織を選択してください"
            scroll-height="480px"
            show-clear
          />
        </div>
        <div class="all-export mt-4">
          <div class="flex items-center">
            <CheckBox inputId="all_org" v-model="allOrganizationVisible" binary />
            <label for="all_org" class="pl-2">すべての組織で集計</label>
          </div>
        </div>
        <div class="mt-4 export-btn">
          <Button
            label="出力"
            size="small"
            class="w-full"
            severity="success"
            @click="exportSupportData"
          />
        </div>
      </div>
    </Dialog>
  </AdminLayout>
</template>
<style lang="scss" scoped>
.btn-item {
  cursor: pointer;
  box-shadow: 3px 3px 5px #33333380;
  transition: 0.3s ease;
  height: 100px;

  &:hover {
    box-shadow: 1px 1px 3px #33333320;
    transform: translateY(2px);
    transition: 0.3s ease;
  }

  &__inner {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    .item-label {
      font-size: 1.25rem;
      color: white;
      font-weight: 500;
      text-align: center;
    }
  }
}
</style>
