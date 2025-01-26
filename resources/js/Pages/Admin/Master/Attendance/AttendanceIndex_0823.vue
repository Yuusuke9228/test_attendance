<script setup>
import CustomToast from "@/Components/CustomToast.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import { useToast } from "primevue/usetoast";
import moment from "moment";
import { driveRideOptions, supportCompanyFlag, dpType } from "@/Utils/field";
import axios from "axios";
import Swal from "sweetalert2";

const props = defineProps({
  attendance: Object,
  idArray: Object,
  users: Object,
  attend_type: Object,
  attend_status: Object,
  support_company: Object,
  supported_company: Object,
  occupation: Object,
  work_contents: Object,
  locations: Object,
  timezones: Object,
  organization: Object,
  close_status: Number,
  close_month: String,
});

const toast = useToast();
const attendanceData = ref([]);
const visibleRemoveDialog = ref(false);
const visibleCloseStatus = ref(false);

onMounted(() => {
  if (props.attendance) {
    attendanceData.value = props.attendance.data.map((item) => {
      item.created_at = moment(item.created_at).format("yyyy-MM-DD HH:mm:ss");
      item.updated_at = moment(item.updated_at).format("yyyy-MM-DD HH:mm:ss");
      item.other_flag = item.attend_status?.attend_name;
      return item;
    });
  }
});

const deleteForm = useForm({
  id: null,
});
const deleteConfirmVsible = (id) => {
  deleteForm.id = id;
  visibleRemoveDialog.value = true;
};
const toastBack = ref();
const removeData = () => {
  deleteForm.delete(route("admin.master.attendance.destroy"), {
    onSuccess: () => {
      toastBack.value = "bg-green-500/70";
      toast.add({
        severity: "custom",
        summary: "削除成功！",
        life: 2000,
        group: "headless",
      });
      attendanceData.value = props.attendance.data.filter(
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

const urlParams = new URLSearchParams(window.location.search);
const detailFilter = ref(urlParams.get("visible") == 1 ? true : false);
const form = useForm({
  sdate: urlParams.get("sdate"),
  cdate: urlParams.get("cdate"),
  user: urlParams.get("user") ? parseInt(urlParams.get("user")) : null,
  type: urlParams.get("type") ? parseInt(urlParams.get("type")) : null,
  start: urlParams.get("start"),
  close: urlParams.get("close"),
  drive: urlParams.get("drive"),
  overtime: urlParams.get("overtime") ? parseInt(urlParams.get("overtime")) : null,
  memo: urlParams.get("memo"),
  sc: urlParams.get("sc") ? parseInt(urlParams.get("sc")) : null,
  sec: urlParams.get("sec") ? parseInt(urlParams.get("sec")) : null,
  occp: urlParams.get("occp") ? parseInt(urlParams.get("occp")) : null,
  wcon: urlParams.get("wcon") ? parseInt(urlParams.get("wcon")) : null,
  loc: urlParams.get("loc") ? parseInt(urlParams.get("loc")) : null,
  tz: urlParams.get("tz") ? parseInt(urlParams.get("tz")) : null,
  org: urlParams.get("org") ? parseInt(urlParams.get("org")) : null,
  visible: 1, //detail Filter mode visible
});
const workContents = ref();
if (props.work_contents) {
  workContents.value = props.work_contents;
}
const chooseOccupation = () => {
  if (form.occp) {
    workContents.value = props.work_contents.filter(
      (f) => f.work_content_occp_id == form.occp
    );
  }
};

const filterAction = () => {
  form.get(route("admin.master.attendance.index"));
};

/**
 * editDisable -> return T or F
 */
const editDisable = (target_date) => {
  if (props.close_status) {
    if (
      moment(target_date).format("yyyy/MM") <= moment(props.close_month).format("yyyy/MM")
    ) {
      return true;
    } else {
      return false;
    }
  } else {
    return false;
  }
};

const getSupportFlag = (e) => {
  if (e == null) {
    return "";
  }
  return supportCompanyFlag.filter(f => f.value == e)[0].label;
}

const editRowVisible = ref(false)
const editRowIndex = ref()
const editRowData = ref(null)
const editRow = (index) => {
  editRowIndex.value = index
  editRowVisible.value = true
  editRowData.value = attendanceData.value[index]
}

const numsOfPeopleList = computed(() => {
  return Array.from({ length: 20 }, (_, index) => (index + 1) * 0.5);
});

const saveRow = () => {
  axios.post(route('admin.master.attendance.update.row'), { data: editRowData.value }).then((res) => {
    Swal.fire({
      toast: true,
      html: `<span style='color:white;'>変更しました。</span>`,
      icon: "success",
      showConfirmButton: false,
      timer: 3000,
      background: "#0284c7",
      position: "bottom-right",
    });
  }).catch((error) => {
    Swal.fire({
      toast: true,
      html: `<span style='color:white;'>操作失敗！</span>`,
      icon: "info",
      showConfirmButton: false,
      timer: 3000,
      background: "#002030",
      position: "bottom-right",
    });
  }).finally(() => {
    editRowData.value = null
    editRowVisible.value = false
  })
}
</script>
<template>
  <CustomToast group="headless" :bgClass="toastBack" />
  <AdminLayout title="出勤管理（打刻データ） ">
    <MainContent>
      <template #header>
        <FontAwesomeIcon icon="fa-solid fa-clock-rotate-left" />
        <h3>
          出勤管理（打刻データ）
          <small>出勤管理情報を管理します。</small>
        </h3>
      </template>
      <MasterContentBox link="attendance" :ids="props.idArray"
        @filter="(e) => (e == 'detail' ? (detailFilter = true) : (detailFilter = false))">
        <div v-if="detailFilter"
          class="w-full detail__search grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-y-2 gap-x-4 mb-2 p-2 border border-sky-400 rounded-md bg-gray-200">
          <div class="target-sdate">
            <VueDatePicker v-model="form.sdate" locale="ja" format="yyyy/M/d" modelType="yyyy/MM/dd"
              :enable-time-picker="false" placeholder="打刻開始日" autoApply class="!w-full" position="left" />
          </div>
          <div class="target-cdate">
            <VueDatePicker v-model="form.cdate" locale="ja" format="yyyy/M/d" modelType="yyyy/MM/dd"
              :enable-time-picker="false" placeholder="打刻終了日" autoApply class="!w-full" position="left" />
          </div>
          <div class="syukkin_time">
            <VueDatePicker v-model="form.start" locale="ja" format="HH:mm" modelType="HH:mm:ss" timePicker
              placeholder="出勤時間以降に検索" autoApply class="!w-full">
              <template #input-icon>
                <i class="pi pi-clock pl-2 pt-1"></i>
              </template>
            </VueDatePicker>
          </div>
          <div class="taikin_time">
            <VueDatePicker v-model="form.close" locale="ja" format="HH:mm" modelType="HH:mm:ss" timePicker
              placeholder="退勤時間以前に検索" autoApply class="!w-full">
              <template #input-icon>
                <i class="pi pi-clock pl-2 pt-1"></i>
              </template>
            </VueDatePicker>
          </div>
          <div class="user">
            <Dropdown v-model="form.user" :options="users" optionLabel="name" optionValue="id" input-class="py-1.5"
              scroll-height="600px" placeholder="ユーザーを選択" class="w-full" filter show-clear />
          </div>
          <div class="dakoku_type">
            <Dropdown v-model="form.type" :options="attend_type" optionLabel="attend_type_name" optionValue="id"
              input-class="py-1.5" placeholder="打刻区分を選択" class="w-full" show-clear />
          </div>
          <div class="drive_option">
            <Dropdown v-model="form.drive" :options="driveRideOptions" optionLabel="label" optionValue="label"
              input-class="py-1.5" placeholder="運転・同乗を選択" class="w-full" show-clear />
          </div>
          <div class="overtime">
            <Dropdown v-model="form.overtime" :options="attend_status" optionLabel="attend_name" optionValue="id"
              scroll-height="600px" input-class="py-1.5" placeholder="残業などを選択" class="w-full" filter show-clear />
          </div>
          <div class="occupation">
            <Dropdown v-model="form.occp" :options="occupation" optionLabel="occupation_name" optionValue="id"
              scroll-height="600px" input-class="py-1.5" placeholder="職種を選択" class="w-full" show-clear
              @update:model-value="chooseOccupation" />
          </div>
          <div class="work_contents">
            <Dropdown v-model="form.wcon" :options="workContents" optionLabel="work_content_name" optionValue="id"
              scroll-height="600px" input-class="py-1.5" placeholder="作業内容を選択" class="w-full" filter show-clear />
          </div>
          <div class="locations">
            <Dropdown v-model="form.loc" :options="locations" optionLabel="location_name" optionValue="id"
              scroll-height="600px" input-class="py-1.5" placeholder="現場を選択" class="w-full" show-clear filter />
          </div>
          <div class="support_company">
            <Dropdown v-model="form.sc" :options="support_company" optionLabel="support_company_name" optionValue="id"
              scroll-height="600px" input-class="py-1.5" placeholder="応援に行った先を選択" class="w-full" show-clear filter />
          </div>
          <div class="supported_company">
            <Dropdown v-model="form.sec" :options="supported_company" optionLabel="supported_company_name"
              optionValue="id" scroll-height="600px" input-class="py-1.5" placeholder="応援来てもらった先を選択" class="w-full"
              show-clear filter />
          </div>
          <div class="timezones">
            <Dropdown v-model="form.tz" :options="timezones" optionLabel="detail_times" optionValue="id"
              scroll-height="600px" input-class="py-1.5" placeholder="時間帯を選択" class="w-full" show-clear />
          </div>
          <div class="organization">
            <Dropdown v-model="form.org" :options="organization" optionLabel="organization_name" optionValue="id"
              scroll-height="600px" input-class="py-1.5" placeholder="組織を選択" class="w-full" filter show-clear />
          </div>
          <div class="memo col-span-1 2xl:col-span-3">
            <InputText v-model="form.memo" placeholder="備考" class="w-full" />
          </div>
          <div class="filter-btn lg:col-span-4 xl:col-span-2 2xl:col-span-6">
            <Button severity="danger" label="検索" icon="pi pi-search" class="w-full" size="small"
              @click="filterAction" />
          </div>
        </div>
        <div class="w-full border custom-table center">
          <table class="table-auto w-full">
            <thead>
              <tr>
                <th>ID</th>
                <th>日付</th>
                <th>ユーザー</th>
                <th>区分</th>
                <th>１日<br>半日</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>運転・同乗</th>
                <th>残業・遅刻<br>研修など</th>
                <th>備考</th>
                <th>時間帯</th>
                <th>職種</th>
                <th>作業内容</th>
                <th>現場</th>
                <th>応援区分</th>
                <th>応援会社</th>
                <th>応援人数</th>
                <th class="sticky right-0">操作</th>
              </tr>
            </thead>
            <tbody v-for="(parent, pIndex) in attendanceData" :key="pIndex">
              <tr :class="{ 'bg-red-100': pIndex == editRowIndex }">
                <td :rowspan="parent.dakoku_children?.length">
                  <!-- {{ attendance.per_page * (attendance.current_page - 1) + pIndex + 1 }} -->
                  {{ parent.id }}
                </td :rowspan="parent.dakoku_children?.length">
                <td :rowspan="parent.dakoku_children?.length">{{ parent.target_date }}</td>
                <td :rowspan="parent.dakoku_children?.length"
                  v-tooltip.top="parent.user?.user_data?.break_times?.organization?.organization_name">
                  {{ parent.user.name }}
                </td>
                <td :rowspan="parent.dakoku_children?.length">
                  <div v-if="editRowVisible && editRowIndex == pIndex" class="input-field">
                    <Dropdown v-model="attendanceData[pIndex].attend_type" :options="attend_type"
                      optionLabel="attend_type_name" class="w-full" placeholder="打刻区分">
                    </Dropdown>
                  </div>
                  <span v-else>{{ parent.attend_type.attend_type_name }}</span>
                </td>
                <td :rowspan="parent.dakoku_children?.length">
                  <div v-if="editRowVisible && editRowIndex == pIndex" class="input-field">
                    <Dropdown v-model="attendanceData[pIndex].dp_type" :options="dpType" class="w-full"
                      placeholder="日区分">
                    </Dropdown>
                  </div>
                  <span v-else>{{ parent.dp_type }}</span>
                </td>
                <td :rowspan="parent.dakoku_children?.length">
                  <div v-if="editRowVisible && editRowIndex == pIndex" class="input-field w-28">
                    <VueDatePicker v-model="attendanceData[pIndex].dp_syukkin_time" locale="ja" model-type="HH:mm:ss"
                      format="HH:mm" time-picker autoApply placeholder="出勤時間" class="w-full">
                      <template #input-icon>
                        <i class="pl-3 mt-1 pi pi-clock"></i>
                      </template>
                    </VueDatePicker>
                  </div>
                  <span v-else>{{ parent.dp_syukkin_time }}</span>
                </td>
                <td :rowspan="parent.dakoku_children?.length">
                  <div v-if="editRowVisible && editRowIndex == pIndex" class="input-field w-28">
                    <VueDatePicker v-model="attendanceData[pIndex].dp_taikin_time" locale="ja" model-type="HH:mm:ss"
                      format="HH:mm" time-picker autoApply placeholder="退勤時間" class="w-full">
                      <template #input-icon>
                        <i class="pl-3 mt-1 pi pi-clock"></i>
                      </template>
                    </VueDatePicker>
                  </div>
                  <span v-else>{{ parent.dp_taikin_time }}</span>
                </td>
                <td :rowspan="parent.dakoku_children?.length">
                  <div v-if="editRowVisible && editRowIndex == pIndex" class="input-field w-28">
                    <Dropdown v-model="attendanceData[pIndex].dp_ride_flg" :options="driveRideOptions"
                      optionLabel="label" optionValue="label" class="w-full" placeholder="運転・同乗" showClear />
                  </div>
                  <span v-else>{{ parent.dp_ride_flg }}</span>
                </td>
                <td :rowspan="parent.dakoku_children?.length">
                  <div v-if="editRowVisible && editRowIndex == pIndex" class="input-field w-full">
                    <Dropdown v-model="attendanceData[pIndex].attend_status" :options="attend_status"
                      optionLabel="attend_name" class="w-full" placeholder="残業・遅刻研修" show-clear />
                  </div>
                  <span v-else>{{ parent.attend_status?.attend_name }}</span>
                </td>
                <td :rowspan="parent.dakoku_children?.length">
                  <InputText v-if="editRowVisible && editRowIndex == pIndex" v-model="attendanceData[pIndex].dp_memo"
                    size="small" />
                  <span v-else>{{ parent.dp_memo }}</span>
                </td>
                <td>
                  <div v-if="editRowVisible && editRowIndex == pIndex" class="input-field w-full">
                    <Dropdown v-model="attendanceData[pIndex].dakoku_children[0].timezone" :options="timezones"
                      optionLabel="detail_times" showClear class="w-full" placeholder="時間帯" />
                  </div>
                  <span v-else>{{ parent.dakoku_children[0]?.timezone?.detail_times }}</span>
                </td>
                <td>
                  <div v-if="editRowVisible && editRowIndex == pIndex" class="input-field w-full">
                    <Dropdown v-model="attendanceData[pIndex].dakoku_children[0].occupation" :options="occupation"
                      optionLabel="occupation_name" class="w-full" placeholder="職種" showClear />
                  </div>
                  <span v-else>{{ parent.dakoku_children[0]?.occupation?.occupation_name }}</span>
                </td>
                <td>
                  <div v-if="editRowVisible && editRowIndex == pIndex" class="input-field w-full">
                    <Dropdown v-model="attendanceData[pIndex].dakoku_children[0].work_content" :options="work_contents"
                      optionLabel="work_content_name" class="w-full" showClear placeholder="作業" />
                  </div>
                  <span v-else>{{ parent.dakoku_children[0]?.work_content?.work_content_name }}</span>
                </td>
                <td>
                  <div v-if="editRowVisible && editRowIndex == pIndex" class="input-field w-full">
                    <Dropdown v-model="attendanceData[pIndex].dakoku_children[0].work_location" :options="locations"
                      optionLabel="location_name" class="w-full" showClear placeholder="現場" />
                  </div>
                  <span v-else>{{ parent.dakoku_children[0]?.work_location?.location_name }}</span>
                </td>
                <td>
                  {{ getSupportFlag(parent.dakoku_children[0]?.dp_support_flg) }}
                </td>
                <td>{{ parent.dakoku_children[0]?.dp_support_flg == 1 ?
                  parent.dakoku_children[0]?.supported_company?.supported_company_name :
                  parent.dakoku_children[0]?.support_company?.support_company_name }}</td>
                <td>
                  <div v-if="editRowVisible && editRowIndex == pIndex" class="input-field w-full">
                    <Dropdown v-model="attendanceData[pIndex].dakoku_children[0].dp_nums_of_people"
                      :options="numsOfPeopleList" class="w-full" placeholder="応援人数" />
                  </div>
                  <span v-else>{{ parent.dakoku_children[0]?.dp_nums_of_people }}</span>
                </td>
                <td :rowspan="parent.dakoku_children?.length" class="sticky right-0 bg-gray-100 z-50">
                  <div class="flex items-center justify-center gap-3">
                    <Link :href="route('admin.master.attendance.show', { id: parent.id })">
                    <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                    </Link>
                    <div class="direct-edit">
                      <p v-if="!editDisable(parent.target_date)">
                        <FontAwesomeIcon v-if="editRowVisible && editRowIndex == pIndex" icon='fa-solid fa-save'
                          class="text-teal-500" @click="saveRow" />
                        <FontAwesomeIcon v-else icon='fa-solid fa-pen' class="text-teal-500" @click="editRow(pIndex)" />
                      </p>
                      <p v-else>
                        <FontAwesomeIcon icon="fa-solid fa-pen" class="text-gray-500 opacity-50"
                          @click="visibleCloseStatus = true" />
                      </p>
                    </div>
                    <div class="edit-icon">
                      <p v-if="editDisable(parent.target_date)">
                        <FontAwesomeIcon icon="fa-solid fa-pen-to-square" class="text-gray-500 opacity-50"
                          @click="visibleCloseStatus = true" />
                      </p>
                      <p v-else>
                        <Link :href="route('admin.master.attendance.edit', {
                          id: parent.id,
                        })
                          ">
                        <FontAwesomeIcon icon="fa-solid fa-pen-to-square" class="text-teal-500" />
                        </Link>
                      </p>
                    </div>
                    <div class="remove-icon">
                      <FontAwesomeIcon v-if="editDisable(parent.target_date)" icon="fa-solid fa-trash"
                        class="text-gray-500 opacity-50" @click="visibleCloseStatus = true" />
                      <FontAwesomeIcon v-else icon="fa-solid fa-trash-can" class="text-rose-500"
                        @click="deleteConfirmVsible(parent.id)" />
                    </div>
                  </div>
                </td>
              </tr>
              <tr v-for="ckey in parent.dakoku_children?.length > 0 ? parent.dakoku_children?.length - 1 : 0"
                :key="ckey" :class="{ 'bg-red-100': pIndex == editRowIndex }">
                <td>
                  <div v-if="editRowVisible && editRowIndex == pIndex" class="input-field w-full">
                    <Dropdown v-model="attendanceData[pIndex].dakoku_children[ckey].timezone" :options="timezones"
                      optionLabel="detail_times" showClear class="w-full" placeholder="時間帯" />
                  </div>
                  <span v-else>{{ parent.dakoku_children[ckey]?.timezone?.detail_times }}</span>
                </td>
                <td>
                  <div v-if="editRowVisible && editRowIndex == pIndex" class="input-field w-full">
                    <Dropdown v-model="attendanceData[pIndex].dakoku_children[ckey].occupation" :options="occupation"
                      optionLabel="occupation_name" class="w-full" placeholder="職種" showClear />
                  </div>
                  <span v-else>{{ parent.dakoku_children[ckey]?.occupation?.occupation_name }}</span>
                </td>
                <td>
                  <div v-if="editRowVisible && editRowIndex == pIndex" class="input-field w-full">
                    <Dropdown v-model="attendanceData[pIndex].dakoku_children[ckey].work_content"
                      :options="work_contents" optionLabel="work_content_name" class="w-full" showClear
                      placeholder="作業" />
                  </div>
                  <span v-else>{{ parent.dakoku_children[ckey]?.work_content?.work_content_name }}</span>
                </td>
                <td>
                  <div v-if="editRowVisible && editRowIndex == pIndex" class="input-field w-full">
                    <Dropdown v-model="attendanceData[pIndex].dakoku_children[ckey].work_location" :options="locations"
                      optionLabel="location_name" class="w-full" showClear placeholder="現場" />
                  </div>
                  <span v-else>{{ parent.dakoku_children[ckey]?.work_location?.location_name }}</span>
                </td>
                <td>{{ getSupportFlag(parent.dakoku_children[ckey]?.dp_support_flg) }}</td>
                <td>{{ parent.dakoku_children[ckey]?.dp_support_flg == 1 ?
                  parent.dakoku_children[ckey]?.supported_company?.supported_company_name :
                  parent.dakoku_children[ckey]?.support_company?.support_company_name }}</td>
                <td>
                  <div v-if="editRowVisible && editRowIndex == pIndex" class="input-field w-full">
                    <Dropdown v-model="attendanceData[pIndex].dakoku_children[ckey].dp_nums_of_people"
                      :options="numsOfPeopleList" class="w-full" placeholder="応援人数" />
                  </div>
                  <span v-else>{{ parent.dakoku_children[ckey]?.dp_nums_of_people }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </MasterContentBox>
    </MainContent>
    <div class="flex items-center justify-center px-6 mt-6">
      <LinkPagination :data="attendance" />
    </div>

    <!-- Dialog to confirmation for removing user -->
    <Dialog v-model:visible="visibleRemoveDialog" modal dismissable-mask :draggable="false" class="w-full max-w-lg">
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
    <Dialog v-model:visible="visibleCloseStatus" modal dismissable-mask :draggable="false" header="編集、削除不可能！"
      class="w-full max-w-lg">
      <div>
        <p class="text-center text-lg">
          月締め処理されたので追加、編集、削除できません。
        </p>
      </div>
    </Dialog>
  </AdminLayout>
</template>
<style lang="scss" scoped>
.detail__search {
  &>div {
    width: 100%;
  }
}
</style>
