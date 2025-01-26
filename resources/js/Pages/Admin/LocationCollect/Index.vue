<script setup>
import { ref, onMounted, computed } from "vue";
import { useToast } from "primevue/usetoast";
import axios from "axios";
import moment from "moment";
import Swal from "sweetalert2";

const props = defineProps({
  locations: Object,
  organizationList: Object,
});

const loading = ref(false);
const visibleMonthPicker = ref(false);
const startDate = ref(moment().subtract(1, "month").format("yyyy/M/D"));
const closeDate = ref(moment().format("yyyy/M/D"));
const locationId = ref();
const locationName = ref();
const organization = ref();
const visibleOrgField = ref(true);
const visibleResult = ref(false);
const collectData = ref([]);
const allCollectData = ref([]);
const dateRange = ref([]);

const handleChooseLocation = (id, name) => {
  // reset
  collectData.value = [];
  allCollectData.value = [];
  dateRange.value = [];
  locationId.value = id;
  locationName.value = name;
  visibleMonthPicker.value = true;
};

const handleCollect = () => {
  if (!startDate.value) {
    Swal.fire({
      toast: true,
      position: "top-end",
      text: "開始日を選択してください。",
      showConfirmButton: false,
      timer: 3000,
      icon: "error",
      timerProgressBar: true,
    });
    return;
  }
  if (!closeDate.value) {
    Swal.fire({
      toast: true,
      position: "top-end",
      text: "終了日を選択してください。",
      showConfirmButton: false,
      timer: 3000,
      icon: "error",
      timerProgressBar: true,
    });
    return;
  }
  visibleMonthPicker.value = false;
  visibleResult.value = true;
  loading.value = true;
  axios
    .get(route("admin.locationcollect.collect"), {
      params: {
        startDate: startDate.value,
        closeDate: closeDate.value,
        location: locationId.value,
        organization: organization.value,
      },
    })
    .then((res) => {
      if (res.data) {
        collectData.value = res.data.result;
        allCollectData.value = res.data.all_data;
        dateRange.value = res.data.date_arr;
      }
    })
    .finally(() => {
      loading.value = false;
    });
};

const getSumCount = (dateRange) => {
  let sum = 0;
  dateRange.forEach((item) => {
    sum += item.sum_by_date;
  });
  return sum;
};

const getSupportCompanyPeoples = (obj) => {
  return obj.reduce((acc, curr) => {
    return (acc += curr);
  }, 0);
};

const handleCheck = (e) => {
  if (e.target.checked) {
    visibleOrgField.value = false;
    organization.value = null;
  } else {
    visibleOrgField.value = true;
  }
};

const excelExportLoading = ref(false);
const exportExcel = () => {
  // alert("実装中です。");
  // visibleResult.value = false;
  excelExportLoading.value = true;
  axios
    .post(route("admin.report.location.data"), {
      data: collectData.value,
      dateRange: dateRange.value,
      startDate: startDate.value,
      closeDate: closeDate.value,
      loc: locationName.value,
    })
    .then((res) => {
      if (res.data?.success) {
        location.href = route("file_download", { file_path: res.data.path });
      }
    })
    .finally(() => {
      excelExportLoading.value = false;
    });
};
</script>
<template>
  <AdminLayout title="現場別集計">
    <Loader v-if="excelExportLoading" />
    <MainContent title="現場別集計" icon="pi-map-marker">
      <div class="location-collect p-3">
        <div class="header_title">
          <h1 class="">
            選択した年月の現場名に該当する職種、作業内容別集計を表示します。
          </h1>
        </div>
        <div class="location-list mt-4">
          <ul class="flex flex-wrap gap-4">
            <li v-for="(item, index) in locations" class="flex-1">
              <div class="w-full">
                <Button
                  :label="item.location_name"
                  class="w-full min-w-[12rem] h-20 rounded-md whitespace-nowrap"
                  severity="success"
                  @click="handleChooseLocation(item.id, item.location_name)"
                />
              </div>
            </li>
          </ul>
        </div>
      </div>
    </MainContent>
    <Dialog
      v-model:visible="visibleMonthPicker"
      header="日付と組織名を選択してください。"
      modal
      dismissable-mask
      :draggable="false"
      class="w-full max-w-lg"
      contentClass="overflow-visible"
      @hide="visibleOrgField = true"
    >
      <div class="w-full p-4">
        <div class="start-date-picker">
          <InputLabel value="開始日" />
          <VueDatePicker
            v-model="startDate"
            locale="ja"
            format="yyyy年M月d日"
            modelType="yyyy/M/d"
            autoApply
            :enable-time-picker="false"
          />
        </div>
        <div class="close-date-picker mt-4">
          <InputLabel value="終了日" />
          <VueDatePicker
            v-model="closeDate"
            locale="ja"
            format="yyyy年M月d日"
            modelType="yyyy/M/d"
            autoApply
            :enable-time-picker="false"
          />
        </div>
        <div v-if="visibleOrgField" class="mt-4 organization">
          <InputLabel value="組織名" />
          <Dropdown
            v-model="organization"
            :options="organizationList"
            optionLabel="organization_name"
            class="w-full h-10"
            placeholder="組織名を選択してください。"
            showClear
          />
        </div>
        <div class="mt-4 text-md text-gray-600 flex items-center">
          <input
            id="org_all"
            type="checkbox"
            class="checked:bg-teal-500 rounded-sm ring-0 border-teal-500"
            @change="handleCheck"
          />
          <label for="org_all" class="pl-2">すべての組織で集計</label>
        </div>
        <div class="mt-4 export-btn">
          <Button
            label="集計"
            size="small"
            class="w-full"
            severity="success"
            @click="handleCollect"
          />
        </div>
      </div>
    </Dialog>
    <!-- Collect result data -->
    <Dialog
      v-model:visible="visibleResult"
      :header="`${moment(new Date(startDate).toISOString()).format(
        'yyyy年M月D日'
      )}～${moment(new Date(closeDate).toISOString()).format(
        'yyyy年M月D日'
      )}  ${locationName}現場集計データ`"
      modal
      dismissable-mask
      position="center"
      :draggable="false"
      maximizable
      class="w-full max-w-[80vw]"
      contentClass="overflow-auto"
    >
      <div class="w-full">
        <Spinner v-if="loading" title="処理中！" class="m-auto" />
        <div v-else class="">
          <div class="export-excel px-2">
            <Button
              icon="pi pi-file-excel"
              label="エクセル出力"
              size="small"
              :disabled="
                collectData.length == 0 ||
                collectData.every((item) => item.occps.length == 0)
              "
              @click="exportExcel"
            />
          </div>
          <div class="collect-data overflow-auto px-2">
            <!-- all data -->
            <table
              v-if="allCollectData.length > 0"
              class="w-full table-auto mb-8 mt-2 all-data"
            >
              <thead>
                <tr>
                  <th rowspan="2">職種</th>
                  <th rowspan="2">作業内容</th>
                  <th :colspan="dateRange.length + 1">すべての組織の集計データ</th>
                </tr>
                <tr>
                  <th>合計</th>
                  <th v-for="(item, index) in dateRange">
                    {{ moment(new Date(item.date).toISOString()).format("D") }}
                  </th>
                </tr>
              </thead>
              <tbody v-for="(occpItem, occpKey) in allCollectData" :key="occpKey">
                <tr
                  v-for="(contentItem, contentKey) in occpItem.work_contents"
                  :key="contentKey"
                >
                  <td
                    v-if="contentKey == 0"
                    :rowspan="Object.keys(occpItem.work_contents).length + 1"
                  >
                    <span class="occupation-name whitespace-nowrap">
                      {{ occpItem.occp_name }}
                    </span>
                  </td>
                  <td>
                    <span class="work-content-name whitespace-nowrap text-center">
                      {{ contentItem.woc_name }}
                    </span>
                  </td>
                  <td>
                    <span>
                      {{
                        contentItem.sum_by_content > 0 ? contentItem.sum_by_content : ""
                      }}
                    </span>
                  </td>
                  <td
                    v-for="(countItem, dateIndex) in contentItem.row_data"
                    class="count-cell"
                  >
                    <span>
                      {{ countItem > 0 ? countItem : "" }}
                    </span>
                  </td>
                </tr>
                <tr class="bg-yellow-200">
                  <td>応援に来てもらった人数</td>
                  <td>
                    <span class="sum">
                      {{ occpItem.sum_of_people_by_occps }}
                    </span>
                  </td>
                  <td v-for="(count, index) in occpItem.nums_of_people" :key="index">
                    {{ count > 0 ? count : "" }}
                  </td>
                </tr>
              </tbody>
            </table>
            <!-- special org data -->
            <table
              v-for="(table, tIndex) in collectData"
              class="w-full table-auto mb-8 mt-2"
            >
              <thead>
                <tr>
                  <th rowspan="2">職種</th>
                  <th rowspan="2">作業内容</th>
                  <th :colspan="dateRange.length + 1">{{ table.title }}</th>
                </tr>
                <tr>
                  <th>合計</th>
                  <th v-for="(item, index) in dateRange">
                    {{ moment(new Date(item.date).toISOString()).format("D") }}
                  </th>
                </tr>
              </thead>
              <tbody v-for="(occpItem, occpKey) in table.occps" :key="occpKey">
                <tr
                  v-for="(contentItem, contentKey) in occpItem.work_contents"
                  :key="contentKey"
                >
                  <td
                    v-if="contentKey == 0"
                    :rowspan="Object.keys(occpItem.work_contents).length + 1"
                  >
                    <span class="occupation-name whitespace-nowrap">
                      {{ occpItem.occp_name }}
                    </span>
                  </td>
                  <td>
                    <span class="work-content-name whitespace-nowrap text-center">
                      {{ contentItem.woc_name }}
                    </span>
                  </td>
                  <td>
                    <span>
                      {{
                        contentItem.sum_by_content > 0 ? contentItem.sum_by_content : ""
                      }}
                    </span>
                  </td>
                  <td
                    v-for="(countItem, dateIndex) in contentItem.row_data"
                    class="count-cell"
                  >
                    <span>
                      {{ countItem > 0 ? countItem : "" }}
                    </span>
                  </td>
                </tr>
                <tr class="bg-yellow-200">
                  <td>応援に来てもらった人数</td>
                  <td>
                    <span class="sum">
                      {{ occpItem.sum_of_people_by_occps }}
                    </span>
                  </td>
                  <td v-for="(count, index) in occpItem.nums_of_people" :key="index">
                    {{ count > 0 ? count : "" }}
                  </td>
                </tr>
              </tbody>
              <!-- <tbody>
                <tr>
                  <td colspan="2" class="">合 計</td>
                  <td>
                    <span>
                      {{ getSumCount(dateRange) }}
                    </span>
                  </td>
                  <td v-for="(item, index) in dateRange">
                    {{ item.sum_by_date > 0 ? item.sum_by_date : "" }}
                  </td>
                </tr>
              </tbody> -->
            </table>
          </div>
        </div>
      </div>
    </Dialog>
  </AdminLayout>
</template>
<style lang="scss" scoped>
.collect-data {
  table {
    border-collapse: collapse;

    td,
    th {
      border: 1px solid #d7d7d7;
      padding: 0.5rem;
      width: 35px;
      height: 35px;
      text-align: center;
      white-space: nowrap;
      font-family: "Noto Serif JP";
      font-size: 14px;
    }

    th {
      background-color: #f0f0f0;
    }

    td.count-cell {
      position: relative;
      z-index: 0;
    }
  }
}
</style>
