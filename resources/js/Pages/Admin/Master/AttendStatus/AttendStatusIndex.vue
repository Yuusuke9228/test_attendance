<script setup>
import CustomToast from "@/Components/CustomToast.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import { useToast } from "primevue/usetoast";
import moment from "moment";

const props = defineProps({
  attendStatus: Object,
});

const toast = useToast();
const attendStatusData = ref([]);
const count = ref(0);
const visibleRemoveDialog = ref(false);

onMounted(() => {
  if (props.attendStatus) {
    attendStatusData.value = props.attendStatus.data.map((item) => {
      item.created_at = moment(item.created_at).format("yyyy-MM-DD HH:mm:ss");
      item.updated_at = moment(item.updated_at).format("yyyy-MM-DD HH:mm:ss");
      return item;
    });
  }
});

const deleteForm = useForm({
  id: null,
});
const deletelConfirmVisible = (id) => {
  deleteForm.id = id;
  visibleRemoveDialog.value = true;
};
const toastBack = ref();
const removeData = () => {
  deleteForm.delete(route("admin.master.attend_statuses.destroy"), {
    onSuccess: () => {
      toastBack.value = "bg-green-500/70";
      toast.add({
        severity: "custom",
        summary: "削除成功！",
        life: 2000,
        group: "headless",
      });
      attendStatusData.value = props.attendStatus.data.filter(
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
const detailFilter = ref(urlParams.get('visible') == 1 ? true : false);
const form = useForm({
  id: urlParams.get('id') ? parseInt(urlParams.get('id')) : null,
  name: urlParams.get('name'),
  visible: 1,
})
const filterAction = () => {
  form.get(route('admin.master.attend_statuses.index'));
}
</script>
<template>
  <CustomToast group="headless" :bgClass="toastBack" />
  <AdminLayout title="残業・早退・遅刻">
    <MainContent>
      <template #header>
        <FontAwesomeIcon icon="fa-solid fa-user-clock" />
        <h3>
          残業・早退・遅刻
          <small>各種区分選択項目を管理します。</small>
        </h3>
      </template>
      <MasterContentBox link="attend_statuses"
        @filter="(e) => e == 'detail' ? detailFilter = true : detailFilter = false">
        <div v-if="detailFilter"
          class="w-full detail__search grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 2xl:grid-cols-6 gap-y-2 gap-x-4 mb-2 p-2 border border-sky-400 rounded-md bg-gray-200">
          <div class="id">
            <InputNumber v-model="form.id" :use-grouping="false" input-class="py-1.5 w-full" class="w-full"
              placeholder="IDで検索" @keyup.enter="filterAction" />
          </div>
          <div class="attend_status_name">
            <InputText v-model="form.name" class="py-1.5 w-full" placeholder="表示名で検索" autofocus show-clear @keyup.enter="filterAction" />
          </div>
          <div class="filter-btn ">
            <Button severity="danger" label="検索" icon="pi pi-search" class="w-full" size="small" @click="filterAction" />
          </div>
        </div>
        <div class="w-full border datatable center">
          <DataTable :value="attendStatusData" data-key="id" selectionMode="multiple" class="p-datatable-sm">
            <Column field="id" header="ID" sortable />
            <Column field="attend_name" header="選択肢表示名" sortable />
            <Column field="created_at" header="作成日時" sortable />
            <Column field="updated_at" header="更新日時" sortable />
            <Column header="操作">
              <template #body="slotProps">
                <div class="flex items-center justify-center gap-3">
                  <Link :href="route('admin.master.attend_statuses.show', {
                    id: slotProps.data.id,
                  })
                    ">
                  <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                  </Link>
                  <Link :href="route('admin.master.attend_statuses.edit', {
                    id: slotProps.data.id,
                  })
                    ">
                  <FontAwesomeIcon icon="fa-solid fa-pen-to-square" class="text-teal-500" />
                  </Link>
                  <FontAwesomeIcon icon="fa-solid fa-trash-can" class="text-rose-500"
                    @click="deletelConfirmVisible(slotProps.data.id)" />
                </div>

              </template>
            </Column>
          </DataTable>
        </div>
      </MasterContentBox>
    </MainContent>
    <div class="flex items-center justify-center px-6 mt-6">
      <LinkPagination :data="attendStatus" />
    </div>

    <!-- Dialog to confirmation for removing user -->
    <Dialog v-model:visible="visibleRemoveDialog" modal dismissable-mask :draggable="false" class="w-96">
      <template #header>
        <span class="text-lg font-bold text-red-600">削除しますか？</span>
      </template>
      <div class="w-full text-center">
        <i class="text-5xl text-red-500 pi pi-info-circle"></i>
        <p class="text-xl font-bold text-red-500">本当に削除しますか？</p>
        <div class="flex items-center justify-center w-full gap-4 mt-4">
          <Button label="いいえ" class="w-24 shrink-0" severity="secondary" @click="visibleRemoveDialog = false" />
          <Button label="はい" class="w-24 shrink-0" severity="success" @click="removeData" />
        </div>
      </div>
    </Dialog>
  </AdminLayout>
</template>
