<script setup>
import CustomToast from "@/Components/CustomToast.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ref, reactive, onMounted, computed, watch } from "vue";
import { useToast } from "primevue/usetoast";
import moment from "moment";
import { weekdays } from "@/Utils/field";
import axios from "axios";

const props = defineProps({
  users: Object,
  breakTimes: Object,
});

const searchCondition = ref(true);
const loading = ref(false);
const dakokuData = ref([]);

const filter = reactive({
  date: moment().format("yyyy/MM/DD"),
  user: null,
  break: null,
  type: "daily",
});

const increaseDate = async () => {
  filter.date = moment(new Date(filter.date)).add(1, "d").format("yyyy/MM/DD");
};
const decreaseDate = async () => {
  filter.date = moment(new Date(filter.date)).subtract(1, "d").format("yyyy/MM/DD");
};

onMounted(async () => {
  await search();
});

const getDate = () => {
  let date_ = moment(new Date(filter.date)).format("yyyy年MM月DD日");
  let weekday_ = moment(new Date(filter.date)).format("d");
  let weekday = weekdays[weekday_];
  return date_ + " (" + weekday + ")";
};

const search = async () => {
  let result = await axios.post(route("admin.record.search", { filter: filter }));
  if (result.data.dakoku) {
    dakokuData.value = result.data.dakoku.map((item, key) => {
      let syukkinTime = item?.dp_syukkin_time;
      let taikinTime = item?.dp_taikin_time;
      let workingTime = "";
      let realWorkingTime = "";
      let restTime = "";

      // 総休息時間計算
      let shiftStart = item.user?.user_data?.break_times?.break_start_time;
      let shiftEnd = item.user?.user_data?.break_times?.break_end_time;
      let restStart_1 = item.user?.user_data?.break_times?.break_start_time1;
      let restEnd_1 = item.user?.user_data?.break_times?.break_end_time1;
      let restStart_2 = item.user?.user_data?.break_times?.break_start_time2;
      let restEnd_2 = item.user?.user_data?.break_times?.break_end_time2;
      let restStart_3 = item.user?.user_data?.break_times?.break_start_time3;
      let restEnd_3 = item.user?.user_data?.break_times?.break_end_time3;
      let restDiff = 0;
      if (restStart_1 && restEnd_1) {
        restDiff += moment.duration(
          moment(restEnd_1, "HH:mm:ss").diff(moment(restStart_1, "HH:mm:ss"))
        );
      }
      if (restStart_2 && restEnd_2) {
        restDiff += moment.duration(
          moment(restEnd_2, "HH:mm:ss").diff(moment(restStart_2, "HH:mm:ss"))
        );
      }
      if (restStart_3 && restEnd_3) {
        restDiff += moment.duration(
          moment(restEnd_3, "HH:mm:ss").diff(moment(restStart_3, "HH:mm:ss"))
        );
      }
      if (restDiff > 0) {
        restTime = moment.utc(restDiff).format("HH:mm");
      }

      // 労働時間の計算
      if (shiftStart && shiftEnd) {
        let workingDiff = moment(shiftEnd, "HH:mm:ss").diff(
          moment(shiftStart, "HH:mm:ss")
        );
        workingDiff -= restDiff;
        if (workingDiff > 0) {
          workingTime = moment.utc(workingDiff).format("HH:mm");
        } else {
          workingTime = moment.utc(Math.abs(workingDiff)).format("-HH:mm");
        }
      }

      // 実労働時間の計算
      if (syukkinTime && taikinTime) {
        let realWorkingDiff = moment(taikinTime, "HH:mm:ss").diff(
          moment(syukkinTime, "HH:mm:ss")
        );
        realWorkingTime = moment.utc(realWorkingDiff).format("HH:mm");
      }
      return {
        no: key + 1,
        id: item.user.id,
        name: item.user?.name,
        code: item.user.code,
        status: item.attend_type?.attend_type_name,
        dpType: item.dp_type,
        shiftStart: shiftStart,
        shiftEnd: shiftEnd,
        workingDate: moment(item?.target_date).format("M/D"),
        syukkinTime: syukkinTime ? moment(syukkinTime, "HH:mm:ss").format("HH:mm") : "",
        taikinTime: taikinTime ? moment(taikinTime, "HH:mm:ss").format("HH:mm") : "",
        workingTime: workingTime,
        realWorkingTime: realWorkingTime,
        restTime: restTime,
        dakouId: item.id,
      };
    });
  } else {
    dakokuData.value = [];
  }
};

watch(filter, async () => {
  await search();
});
</script>
<template>
  <AdminLayout title="日次出勤簿">
    <MainContent>
      <template #header>
        <div class="flex items-center justify-between w-full">
          <div class="flex items-center gap-2">
            <FontAwesomeIcon icon="fa-solid fa-book" />
            <h3>出勤簿（指定日）- {{ getDate() }}</h3>
            <!-- <small>指定された日・拒定されたグループの出動簿を表示します。</small> -->
          </div>
        </div>
      </template>
      <div class="w-full">
        <div class="filter-condition">
          <!-- 検索パネル -->
          <div class="flex items-center justify-between">
            <div class="toggle-filter-btn">
              <Button
                size="small"
                class="flex items-center gap-2 py-1"
                @click="searchCondition = !searchCondition"
              >
                <i class="pi pi-search"></i>
                <span>検索条件</span>
                <i
                  class="pi"
                  :class="searchCondition ? 'pi-angle-up' : 'pi-angle-down'"
                ></i>
              </Button>
            </div>
            <div class="flex items-center gap-2 setting-print">
              <div class="daily-dakoku-data">
                <Link :href="route('admin.record.special.index')">
                  <Button
                    size="small"
                    label="日次出勤簿"
                    icon="pi pi-calendar-minus"
                    class="py-1"
                    :severity="
                      route().current() == 'admin.record.special.index'
                        ? 'info'
                        : 'secondary'
                    "
                  />
                </Link>
              </div>
              <div class="daily-dakoku-data">
                <Link :href="route('admin.record.monthly.index')">
                  <Button
                    size="small"
                    label="月次出勤簿"
                    class="py-1"
                    icon="pi pi-calendar"
                    :severity="
                      route().current() == 'admin.record.monthly.index'
                        ? 'info'
                        : 'secondary'
                    "
                  />
                </Link>
              </div>
              <div class="hidden export-excel">
                <Button
                  label="Excel出力"
                  icon="pi pi-file-excel"
                  size="small"
                  severity="danger"
                  class="py-1"
                />
              </div>
            </div>
          </div>
          <div v-if="searchCondition" class="p-4 mt-1 bg-white rounded-md shadow-lg">
            <div class="w-full">
              <form @submit.prevent="submit" class="w-full">
                <div class="grid grid-cols-1 gap-2 2xl:grid-cols-3">
                  <!-- 表示日 -->
                  <div class="form-inner">
                    <div class="input-label">
                      <InputLabel value="表示日" />
                    </div>
                    <div class="flex items-center gap-2 input-field">
                      <Button
                        icon="pi pi-angle-left"
                        class="py-1"
                        size="small"
                        outlined
                        @click="decreaseDate"
                      />
                      <VueDatePicker
                        v-model="filter.date"
                        locale="ja"
                        modelType="yyyy/MM/dd"
                        format="yyyy年MM月dd日"
                        :enable-time-picker="false"
                        autoApply
                      >
                        <template #clear-icon="{ clear }"></template>
                      </VueDatePicker>
                      <Button
                        icon="pi pi-angle-right"
                        class="py-1"
                        size="small"
                        outlined
                        @click="increaseDate"
                      />
                    </div>
                  </div>
                  <!-- 所属形態 -->
                  <div class="form-inner">
                    <div class="input-label">
                      <InputLabel value="勤務形態コード" />
                    </div>
                    <div class="flex items-center gap-2 input-field">
                      <Dropdown
                        v-model="filter.break"
                        :options="breakTimes"
                        show-clear
                        filter
                        class="w-full"
                        input-class="py-1.5"
                        placeholder="勤務形態選択してください"
                        empty-message="データなし"
                        empty-filter-message="データなし"
                        filterLocale="ja"
                        dataKey="id"
                        scrollHeight="480px"
                        :filterFields="[
                          'break_work_pattern_cd',
                          'break_organization',
                          'break_name',
                        ]"
                      >
                        <template #value="slotProps">
                          <div v-if="slotProps.value" class="flex items-center gap-2">
                            <div class="flex items-center">
                              <i class="pi pi-building"></i>
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
                            <i class="pi pi-building"></i>
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
                  <!-- スタッフ別 -->
                  <div class="form-inner">
                    <div class="input-label">
                      <InputLabel value="スタッフ別" />
                    </div>
                    <div class="flex items-center gap-2 input-field">
                      <Dropdown
                        v-model="filter.user"
                        :options="users"
                        optionLabel="name"
                        filter
                        show-clear
                        optionValue="id"
                        class="w-full"
                        scrollHeight="480px"
                        input-class="py-1.5"
                        placeholder="スタッフ選択"
                        empty-filter-message="検索結果なし"
                      />
                    </div>
                  </div>
                </div>
                <!-- search btn -->
                <div class="flex justify-center w-full mt-8">
                  <Button
                    type="submit"
                    label="検索"
                    icon="pi pi-search"
                    class="w-44"
                    size="small"
                    severity="success"
                    @click="search"
                  />
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- 検索結果リスト -->
        <div
          class="w-full mt-8 overflow-auto bg-white border-t-2 rounded-md shadow-lg datatable center border-rose-500/30"
          :class="searchCondition ? 'h-[calc(100vh-350px)]' : 'h-[calc(100vh-195px)]'"
        >
          <DataTable
            :value="dakokuData"
            data-key="id"
            :loading="loading"
            selectionMode="multiple"
            class="p-datatable-sm"
          >
            <Column field="id" header="No" sortable />
            <Column header="スタッフ名" sortable>
              <template #body="slotProps">
                <div class="flex items-center justify-between gap-2">
                  <Link
                    :href="route('admin.users.show', { id: slotProps.data.id })"
                    class="underline"
                  >
                    {{ slotProps.data.name }}
                  </Link>
                  <Link
                    :href="
                      route('admin.record.monthly.index', {
                        userId: slotProps.data.id,
                        date: filter.date,
                      })
                    "
                  >
                    <Button label="月次" size="small" class="py-0.5" severity="info" />
                  </Link>
                </div>
              </template>
            </Column>
            <Column field="code" header="スタッフコード" sortable />
            <Column field="workingDate" header="日付" sortable />
            <Column field="status" header="勤怠状況" sortable />
            <Column field="dpType" header="１日•半日" sortable />
            <Column header="シフト" sortable>
              <template #body="slotProps">
                <div
                  v-if="slotProps.data.shiftStart || slotProps.data.shiftEnd"
                  class="flex items-center justify-center"
                >
                  <span>{{
                    slotProps.data.shiftStart
                      ? moment(slotProps.data.shiftStart, "HH:mm:ss").format("HH:mm")
                      : ""
                  }}</span>
                  <span>～</span>
                  <span>{{
                    slotProps.data.shiftEnd
                      ? moment(slotProps.data.shiftEnd, "HH:mm:ss").format("HH:mm")
                      : ""
                  }}</span>
                </div>
                <div v-else>
                  <span>-</span>
                </div>
              </template>
            </Column>
            <Column field="syukkinTime" header="出勤時刻" sortable />
            <Column field="taikinTime" header="退勤時刻" sortable />
            <Column field="workingTime" header="労働時間" sortable />
            <Column field="realWorkingTime" header="実労働時間" sortable />
            <Column field="restTime" header="休憩時間" sortable />
            <Column header="出入詳細">
              <template #body="slotProps">
                <Link
                  v-if="slotProps.data.dakouId"
                  :href="
                    route('admin.master.attendance.show', { id: slotProps.data.dakouId })
                  "
                >
                  <Button label="詳細" size="small" class="py-0.5" severity="secondary" />
                </Link>
              </template>
            </Column>
          </DataTable>
        </div>
      </div>
    </MainContent>
  </AdminLayout>
</template>
<style lang="scss" scoped>
.form-inner {
  display: grid;
  grid-template-columns: 100px 300px;
  align-items: center;
  gap: 10px;
  margin-top: 0.5rem;
}
</style>
