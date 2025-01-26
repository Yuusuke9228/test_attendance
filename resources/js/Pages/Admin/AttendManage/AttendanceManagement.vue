<script setup>
import { reactive, ref, onMounted } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import axios from "axios";
import moment from "moment";
import LinkPagination from "@/Components/LinkPagination.vue";

const props = defineProps({
  dakoku: Object,
});

const dakokuList = ref([]);
const totalCount = ref(0);
onMounted(() => {
  if (props.dakoku) {
    dakokuList.value = props.dakoku;
    totalCount.value = props.dakoku.total;
  }
});

const urlParams = new URLSearchParams(window.location.search);
const form = useForm({
  startDate: urlParams.has("startDate")
    ? urlParams.get("startDate")
    : moment().subtract(15, "d").format("yyyy/MM/DD"),
  endDate: urlParams.has("endDate")
    ? urlParams.get("endDate")
    : moment().format("yyyy/MM/DD"),
});

const fetchData = () => {
  form.get(route("admin.attendance.index"));
};
</script>
<template>
  <AdminLayout title="勤怠管理">
    <div class="w-full dashboard-page">
      <MainContent>
        <template #header>
          <FontAwesomeIcon icon="fa-solid fa-pencil" />
          <h3>勤怠管理</h3>
        </template>
        <ContentBox title="勤怠データ一覧">
          <div class="box-content !overflow-visible">
            <div class="flex items-center justify-between w-full p-3">
              <div class="flex items-center gap-4">
                <div class="start-date">
                  <VueDatePicker
                    v-model="form.startDate"
                    locale="ja"
                    modelType="yyyy/MM/dd"
                    format="yyyy/MM/dd"
                    :enable-time-picker="false"
                    :max-date="new Date()"
                    autoApply
                  />
                </div>
                <div class="start-date">
                  <VueDatePicker
                    v-model="form.endDate"
                    locale="ja"
                    modelType="yyyy/MM/dd"
                    format="yyyy/MM/dd"
                    :enable-time-picker="false"
                    :max-date="new Date()"
                    autoApply
                  />
                </div>
                <div class="filtering-btn">
                  <Button
                    label="対象期間データ抽出"
                    icon="pi pi-search"
                    size="small"
                    class="py-2 rounded-md"
                    severity="success"
                    @click="fetchData"
                  />
                </div>
              </div>
              <div class="">
                <div class="add-data-btn">
                  <Link :href="route('admin.master.attendance.create')">
                    <Button
                      label="追加"
                      icon="pi pi-plus"
                      size="small"
                      class="py-2 rounded-md"
                      severity="danger"
                    />
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </ContentBox>
        <ContentBox title="検索結果" class="mt-4 datatable center">
          <LinkPagination :data="dakoku" />
          <DataTable
            :value="dakoku?.data"
            data-key="id"
            selectionMode="single"
            ref="dt"
            stripedRows
            responsiveLayout="scroll"
            class="p-datatable-sm"
          >
            <Column field="id" header="ID" bodyClass="text-blue-500" sortable>
              <template #body="slotProps">
                <Link
                  :href="route('admin.master.attendance.show', { id: slotProps.data.id })"
                >
                  {{ slotProps.data.id }}
                </Link>
              </template>
            </Column>
            <Column field="user.name" header="ユーザー" sortable />
            <Column field="attend_type.attend_type_name" header="打刻区分" sortable />
            <Column field="dp_type" header="１日•半日" sortable />
            <Column field="target_date" header="日付" sortable />
            <Column field="dp_syukkin_time" header="出勤時刻" sortable />
            <Column field="dp_taikin_time" header="退勤時刻" sortable />
            <Column field="dp_memo" header="備考" />
          </DataTable>
        </ContentBox>
      </MainContent>
    </div>
  </AdminLayout>
</template>
