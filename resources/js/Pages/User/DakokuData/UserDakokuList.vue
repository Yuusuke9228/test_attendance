<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import moment from "moment";
import axios from "axios";
import { supportCompanyFlag } from "@/Utils/field";
import Swal from "sweetalert2";
import { getWeekdays } from "@/Utils/action";

const props = defineProps({
  dakouData: Object,
  start_date: String,
  end_date: String,
  min_date: String,
});

const list = ref([]);
const visibleRemoveDialog = ref(false);
const visibleCloseStatus = ref(false);

const form = useForm({
  s: props.start_date
    ? moment(new Date(props.start_date)).format("yyyy-MM-DD")
    : moment().subtract(7, "d").format("yyyy-MM-DD"),
  e: props.end_date
    ? moment(new Date(props.end_date)).format("yyyy-MM-DD")
    : moment().format("yyyy-MM-DD"),
});

onMounted(() => {
  if (props.dakouData) {
    let perPage = props.dakouData.per_page;
    let currentPage = props.dakouData.current_page;
    list.value = props.dakouData.data.map((item, index) => {
      item.no = perPage * (currentPage - 1) + index + 1;
      item.created_at = item.created_at ? moment(item.created_at).format("yyyy-MM-DD HH:mm:ss") : null;
      item.updated_at = item.updated_at ? moment(item.updated_at).format("yyyy-MM-DD HH:mm:ss") : null;
      item.other_flag = item?.attend_status?.attend_name ?? null;
      return item;
    });
  }
});

const searchAction = () => {
  form.get(route("user.attendance.list.index"));
};

const removeId = ref();
const visibleRemove = (id) => {
  removeId.value = id;
  visibleRemoveDialog.value = true;
};
const removeRow = async () => {
  axios.post(route("user.attendance.list.destroy", { id: removeId.value })).then(() => {
    Swal.fire({
      toast: true,
      html: `<span style='color:white;'>削除成功！</span>`,
      icon: "success",
      showConfirmButton: false,
      timer: 3000,
      background: "#0284c7",
      position: "bottom-right",
    });
    list.value = list.value.filter((filter) => filter.id !== removeId.value);
    visibleRemoveDialog.value = false;
  });
};

/**
 * editDisable -> return T or F
 */
const editDisable = (target_date) => {
  if (moment(target_date).isBefore(moment(props.min_date))) {
    return true;
  }
};

const getSupportFlag = (e) => {
  let flg = supportCompanyFlag.filter(f => f.value == e);
  if (flg && flg.length > 0) {
    return supportCompanyFlag.filter(f => f.value == e)[0]?.label;
  } else {
    return null;
  }
}

const weekdayMark = (date) => {
  let day = moment(date).format('d');
  let weeks = getWeekdays().filter(f => f.value == day)[0].label
  return weeks
}
</script>

<template>
  <AuthenticatedLayout title="出退勤一覧">
    <div class="w-full max-w-xl p-0 m-auto md:p-6 md:max-w-full user-page__dashboard">
      <div class="flex items-center justify-between px-3">
        <div class="flex items-center gap-2 text-lg">
          <FontAwesomeIcon icon="fa-solid fa-list" />
          <span class="font-bold">出退勤一覧</span>
        </div>
        <div>
          <Link :href="route('user.attendance.list.create')">
          <Button label="新規" icon="pi pi-plus" size="small" class="py-1.5 w-24" severity="success" />
          </Link>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-4 p-2 my-2 bg-white rounded-md search_field">
        <div>
          <VueDatePicker v-model="form.s" locale="ja" format="yyyy-MM-dd" modelType="yyyy-MM-dd"
            :enable-time-picker="false" autoApply class="w-72" />
        </div>
        <div>
          <VueDatePicker v-model="form.e" locale="ja" format="yyyy-MM-dd" modelType="yyyy-MM-dd"
            :enable-time-picker="false" autoApply class="w-72" />
        </div>
        <div>
          <Button label="検索" icon="pi pi-search" size="small" @click="searchAction" />
        </div>
      </div>
      <div class="w-full p-2 overflow-auto bg-white rounded-lg shadow-lg md:p-4 custom-table center">
        <table class="table-auto w-full">
          <thead>
            <tr>
              <th>ID</th>
              <th>日付</th>
              <th>区分</th>
              <th>１日<br>半日</th>
              <th>出勤</th>
              <th>退勤</th>
              <th>運転・同乗</th>
              <th>残業・遅刻<br>研修など</th>
              <th>備考</th>
              <th>時間帯</th>
              <th>職種</th>
              <th>現場</th>
              <th>応援区分</th>
              <th>応援会社</th>
              <th>応援人数</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody v-for="(parent, pIndex) in list" :key="pIndex">
            <tr :class="{ 'bg-red-50': parent.id == null }">
              <td :rowspan="parent.dakoku_children?.length">
                {{ dakouData.per_page * (dakouData.current_page - 1) + pIndex + 1 }}
              </td :rowspan="parent.dakoku_children?.length">
              <td :rowspan="parent.dakoku_children?.length">
                {{ parent.target_date }}
                <small :class="weekdayMark(parent.target_date) == '⽇' ? 'text-red-500' : weekdayMark(parent.target_date) == '⼟' ? 'text-sky-500' : ''">({{ weekdayMark(parent.target_date) }})</small>
              </td>
              <td :rowspan="parent.dakoku_children?.length" >{{ parent?.attend_type?.attend_type_name }}</td>
              <td :rowspan="parent.dakoku_children?.length" :class="{'bg-red-50': !parent?.dp_type}">{{ parent?.dp_type }}</td>
              <td :rowspan="parent.dakoku_children?.length" :class="{'bg-red-50': !parent?.dp_syukkin_time}">{{ parent?.dp_syukkin_time }}</td>
              <td :rowspan="parent.dakoku_children?.length" :class="{'bg-red-50': !parent?.dp_taikin_time}">{{ parent?.dp_taikin_time }}</td>
              <td :rowspan="parent.dakoku_children?.length" :class="{'bg-red-50': !parent?.dp_ride_flg}">{{ parent?.dp_ride_flg }}</td>
              <td :rowspan="parent.dakoku_children?.length" :class="{'bg-red-50': !parent?.other_flag}">{{ parent?.other_flag }}</td>
              <td :rowspan="parent.dakoku_children?.length" :class="{'bg-red-50': !parent?.dp_memo}">{{ parent?.dp_memo }}</td>
              <td :class="{'bg-red-50': !parent.dakoku_children[0]?.timezone?.detail_times}">{{ parent.dakoku_children[0]?.timezone?.detail_times }}</td>
              <td :class="{'bg-red-50': !parent.dakoku_children[0]?.occupation}">{{ parent.dakoku_children[0]?.occupation?.occupation_name }}</td>
              <td :class="{'bg-red-50': !parent.dakoku_children[0]?.work_location}">{{ parent.dakoku_children[0]?.work_location?.location_name }}</td>
              <td :class="{'bg-red-50': !parent.dakoku_children[0]?.dp_support_flg}">{{ getSupportFlag(parent.dakoku_children[0]?.dp_support_flg) }}</td>
              <td :class="{'bg-red-50': !parent.dakoku_children[0]?.supported_company && !parent.dakoku_children[0]?.support_company}">{{ parent.dakoku_children[0]?.dp_support_flg == 1 ?
                parent.dakoku_children[0]?.supported_company?.supported_company_name :
                parent.dakoku_children[0]?.support_company?.support_company_name }}</td>
              <td :class="{'bg-red-50': !parent.dakoku_children[0]?.dp_nums_of_people}">{{ parent.dakoku_children[0]?.dp_nums_of_people }}</td>
              <td :rowspan="parent.dakoku_children?.length">
                <div class="flex items-center pl-2 gap-3">
                  <Link :href="route('user.attendance.list.detail', { id: parent.id, date:parent.target_date })">
                  <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                  </Link>
                  <div class="edit-icon">
                    <p v-if="editDisable(parent.target_date)">
                      <FontAwesomeIcon icon="fa-solid fa-pen-to-square" class="text-gray-500 opacity-50"
                        @click="visibleCloseStatus = true" />
                    </p>
                    <p v-else>
                      <Link :href="route('user.attendance.list.create', {
                        date: parent.target_date,
                        id: parent.id,
                      })
                        ">
                      <FontAwesomeIcon icon="fa-solid fa-pen-to-square" class="text-teal-500" />
                      </Link>
                    </p>
                  </div>
                  <div class="remove-icon">
                    <div v-if="parent.id">
                        <FontAwesomeIcon v-if="editDisable(parent.target_date)" icon="fa-solid fa-trash"
                          class="text-gray-500 opacity-50" @click="visibleCloseStatus = true" />
                        <FontAwesomeIcon v-else icon="fa-solid fa-trash-can" class="text-rose-500"
                          @click="visibleRemove(parent.id)" />
                    </div>
                  </div>
                </div>
              </td>
            </tr>
            <tr v-for="ckey in parent.dakoku_children?.length > 0 ? parent.dakoku_children?.length - 1 : 0" :key="ckey">
              <td :class="{'bg-red-50': !parent.dakoku_children[ckey]?.timezone}">{{ parent.dakoku_children[ckey]?.timezone?.detail_times }}</td>
              <td :class="{'bg-red-50': !parent.dakoku_children[ckey]?.occupation}">{{ parent.dakoku_children[ckey]?.occupation?.occupation_name }}</td>
              <td :class="{'bg-red-50': !parent.dakoku_children[ckey]?.work_location}">{{ parent.dakoku_children[ckey]?.work_location?.location_name }}</td>
              <td :class="{'bg-red-50': !parent.dakoku_children[ckey]?.dp_support_flg}">{{ getSupportFlag(parent.dakoku_children[ckey]?.dp_support_flg) }}</td>
              <td :class="{'bg-red-50': !parent.dakoku_children[ckey]?.supported_company && !parent.dakoku_children[0]?.support_company}">{{ parent.dakoku_children[ckey]?.dp_support_flg == 1 ?
                parent.dakoku_children[ckey]?.supported_company?.supported_company_name :
                parent.dakoku_children[ckey]?.support_company?.support_company_name }}</td>
              <td :class="{'bg-red-50': !parent.dakoku_children[ckey]?.dp_nums_of_people}">{{ parent.dakoku_children[ckey]?.dp_nums_of_people }}</td>
            </tr>
          </tbody>
        </table>
        <div class="flex items-center justify-end mt-6">
          <LinkPagination :data="dakouData" />
        </div>
      </div>
    </div>
    <Dialog v-model:visible="visibleRemoveDialog" modal dismissable-mask :draggable="false" class="w-96">
      <template #header>
        <span class="text-lg font-bold text-red-600">削除しますか？</span>
      </template>
      <div class="w-full text-center">
        <i class="text-5xl text-red-500 pi pi-info-circle"></i>
        <p class="text-xl font-bold text-red-500">本当に削除しますか？</p>
        <div class="flex items-center justify-center w-full gap-4 mt-4">
          <Button label="取り消し" class="w-24 shrink-0" severity="secondary" @click="visibleRemoveDialog = false" />
          <Button label="確認" class="w-24 shrink-0" severity="success" @click="removeRow" />
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
  </AuthenticatedLayout>
</template>
