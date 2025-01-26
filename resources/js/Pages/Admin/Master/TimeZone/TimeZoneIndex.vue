<script setup>
import CustomToast from "@/Components/CustomToast.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import { useToast } from "primevue/usetoast";
import moment from "moment";

const props = defineProps({
  timeZone: Object,
});

const toast = useToast();
const timeZoneData = ref([]);
const count = ref(0);
const visibleRemoveDialog = ref(false);

onMounted(() => {
  if (props.timeZone) {
    timeZoneData.value = props.timeZone.data.map((item) => {
      item.created_at = item.created_at
        ? moment(item.created_at).format("yyyy-MM-DD HH:mm:ss")
        : null;
      item.updated_at = item.updated_at
        ? moment(item.updated_at).format("yyyy-MM-DD HH:mm:ss")
        : null;
      return item;
    });
  }
});

const deleteForm = useForm({
  id: null,
});
const deleteRowVisible = (id) => {
  deleteForm.id = id;
  visibleRemoveDialog.value = true;
};
const toastBack = ref();
const removeRow = () => {
  deleteForm.delete(route("admin.master.timezone.destroy"), {
    onSuccess: () => {
      toastBack.value = "bg-green-500/70";
      toast.add({
        severity: "custom",
        summary: "削除成功！",
        life: 2000,
        group: "headless",
      });
      timeZoneData.value = props.timeZone.data.filter(
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
  id: urlParams.get("id"),
  zone: urlParams.get("zone"),
  visible: 1, //detail Filter mode visible
});
const filterAction = () => {
  form.get(route("admin.master.timezone.index"));
};
</script>
<template>
  <CustomToast group="headless" :bgClass="toastBack" />
  <AdminLayout title="時間帯区分">
    <MainContent>
      <template #header>
        <FontAwesomeIcon icon="fa-solid fa-clock" />
        <h3>時間帯区分</h3>
      </template>
      <MasterContentBox
        link="timezone"
        @filter="(e) => (e == 'detail' ? (detailFilter = true) : false)"
      >
        <div
          v-if="detailFilter"
          class="w-full detail__search flex gap-2 flex-wrap mb-2 p-2 border border-sky-400 rounded-md bg-gray-200"
        >
          <div class="id_field">
            <InputText
              v-model="form.id"
              placeholder="IDを入力してください。"
              class="py-1.5"
            />
          </div>
          <div class="timezones_list">
            <InputText
              v-model="form.zone"
              placeholder="時間帯を入力してください。"
              class="py-1.5"
            />
          </div>
          <div class="filter-btn">
            <Button
              severity="danger"
              label="検索"
              icon="pi pi-search"
              class="w-44"
              size="small"
              @click="filterAction"
            />
          </div>
        </div>
        <div class="w-full border datatable center">
          <DataTable
            :value="timeZoneData"
            data-key="id"
            selectionMode="multiple"
            class="p-datatable-sm"
          >
            <Column field="id" header="ID" sortable />
            <Column field="detail_times" header="時間帯" sortable />
            <Column field="created_at" header="作成日時" sortable />
            <Column field="updated_at" header="更新日時" sortable />
            <Column header="操作">
              <template #body="slotProps">
                <div class="flex items-center justify-center gap-3">
                  <Link
                    :href="route('admin.master.timezone.show', { id: slotProps.data.id })"
                  >
                    <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                  </Link>
                  <Link
                    :href="route('admin.master.timezone.edit', { id: slotProps.data.id })"
                  >
                    <FontAwesomeIcon
                      icon="fa-solid fa-pen-to-square"
                      class="text-teal-500"
                    />
                  </Link>
                  <FontAwesomeIcon
                    icon="fa-solid fa-trash-can"
                    class="text-rose-500"
                    @click="deleteRowVisible(slotProps.data.id)"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </div>
      </MasterContentBox>
    </MainContent>
    <div class="flex items-center justify-center px-6 mt-6">
      <LinkPagination :data="timeZone" />
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
            @click="removeRow"
          />
        </div>
      </div>
    </Dialog>
  </AdminLayout>
</template>
