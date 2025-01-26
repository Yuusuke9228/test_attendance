<script setup>
import CustomToast from "@/Components/CustomToast.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import { useToast } from "primevue/usetoast";
import moment from "moment";
import Tag from "primevue/tag";

const props = defineProps({
  holidays: Object,
});

const toast = useToast();
const holidaysData = ref([]);
const count = ref(0);
const visibleRemoveDialog = ref(false);

onMounted(() => {
  if (props.holidays) {
    holidaysData.value = props.holidays.data.map((item, key) => {
      (item.no = props.holidays.per_page * (props.holidays.current_page - 1) + key + 1),
        (item.created_at = moment(item.created_at).format("yyyy-MM-DD HH:mm:ss"));
      item.updated_at = moment(item.updated_at).format("yyyy-MM-DD HH:mm:ss");
      return item;
    });
  }
});

const deleteForm = useForm({
  id: null,
});
const deleteConfirmVisible = (id) => {
  deleteForm.id = id;
  visibleRemoveDialog.value = true;
};
const toastBack = ref();
const removeData = () => {
  deleteForm.delete(route("admin.master.holiday.destroy"), {
    onSuccess: () => {
      toastBack.value = "bg-green-500/70";
      toast.add({
        severity: "custom",
        summary: "削除成功！",
        life: 2000,
        group: "headless",
      });
      holidaysData.value = props.holidays.data.filter(
        (filter) => filter.id !== deleteForm.id
      );
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
// Detail Filter
const urlParams = new URLSearchParams(window.location.search);
const detailFilter = ref(urlParams.get("visible") == 1 ? true : false);
const form = useForm({
  id: urlParams.get("id") ? parseInt(urlParams.get("id")) : null,
  sdate: urlParams.get("sdate"),
  cdate: urlParams.get("cdate"),
  paid_holiday: urlParams.get("paid_holiday") == "true" ? true : false,
  visible: 1,
});
const filterAction = () => {
  form.get(route("admin.master.holiday.index"));
};
</script>
<template>
  <CustomToast group="headless" :bgClass="toastBack" />
  <AdminLayout title="休日管理">
    <MainContent>
      <template #header>
        <FontAwesomeIcon icon="fa-solid fa-cake-candles" />
        <h3>
          休日管理
          <small>休日を管理します。</small>
        </h3>
      </template>
      <MasterContentBox
        link="holiday"
        @filter="(e) => (e == 'detail' ? (detailFilter = true) : (detailFilter = false))"
      >
        <div
          v-if="detailFilter"
          class="w-full detail__search grid items-center grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 2xl:grid-cols-6 gap-y-2 gap-x-4 mb-2 p-2 border border-sky-400 rounded-md bg-gray-200"
        >
          <div class="schedule-id">
            <InputNumber
              v-model="form.id"
              :use-grouping="false"
              input-class="py-1.5 w-full"
              class="w-full"
              placeholder="IDで検索"
            />
          </div>
          <div class="holiday-sdate">
            <VueDatePicker
              v-model="form.sdate"
              locale="ja"
              format="yyyy/M/d"
              modelType="yyyy/MM/dd"
              :enable-time-picker="false"
              placeholder="休日開始日"
              autoApply
              class="!w-full"
              position="left"
            />
          </div>
          <div class="holiday-cdate">
            <VueDatePicker
              v-model="form.cdate"
              locale="ja"
              format="yyyy/M/d"
              modelType="yyyy/MM/dd"
              :enable-time-picker="false"
              placeholder="休日終了日"
              autoApply
              class="!w-full"
              position="left"
            />
          </div>
          <div class="paid_holiday_check">
            <div class="flex items-center">
              <CheckBox inputId="paid_check" v-model="form.paid_holiday" binary />
              <InputLabel htmlFor="paid_check" value="有給休日" class="pl-2" />
            </div>
          </div>
          <div class="filter-btn">
            <Button
              severity="danger"
              label="検索"
              icon="pi pi-search"
              class="w-full"
              size="small"
              @click="filterAction"
            />
          </div>
        </div>
        <div class="w-full border datatable center">
          <DataTable
            :value="holidaysData"
            data-key="id"
            selectionMode="multiple"
            class="p-datatable-sm"
          >
            <Column field="no" header="ID" sortable />
            <Column field="holiday_date" header="休日" sortable />
            <Column field="paid_holiday" header="有給休日" sortable class="max-w-[30px]">
              <template #body="slotProps">
                <Tag
                  v-if="slotProps.data.paid_holiday"
                  value="有給休日"
                  severity="warning"
                />
              </template>
            </Column>
            <Column field="created_at" header="作成日時" sortable />
            <Column field="updated_at" header="更新日時" sortable />
            <Column header="操作">
              <template #body="slotProps">
                <div class="flex items-center justify-center gap-3">
                  <Link
                    :href="route('admin.master.holiday.show', { id: slotProps.data.id })"
                  >
                    <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                  </Link>
                  <Link
                    :href="route('admin.master.holiday.edit', { id: slotProps.data.id })"
                  >
                    <FontAwesomeIcon
                      icon="fa-solid fa-pen-to-square"
                      class="text-teal-500"
                    />
                  </Link>
                  <FontAwesomeIcon
                    icon="fa-solid fa-trash-can"
                    class="text-rose-500"
                    @click="deleteConfirmVisible(slotProps.data.id)"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </div>
      </MasterContentBox>
    </MainContent>
    <div class="flex items-center justify-center px-6 mt-6">
      <LinkPagination :data="holidays" />
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
            @click="removeData"
          />
        </div>
      </div>
    </Dialog>
  </AdminLayout>
</template>
