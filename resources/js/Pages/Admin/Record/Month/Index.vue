<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, reactive, onMounted, watch } from "vue";
import { useToast } from "primevue/usetoast";
import moment from "moment";
import axios from "axios";
import { weekdays } from "@/Utils/field";

const props = defineProps({
  users: Object,
  user_id: Number,
  search_date: String,
  search_date: String,
});

const toast = useToast();
const searchCondition = ref(true);
const collectCondition = ref(true);
const dakokuPageData = ref([]);
const dakokuData = ref([]);
const attendedUsers = ref(0);
const workingDaysList = ref([]);
const workingHoursList = ref([]);
const otherFlgCountList = ref([]);
const workContentCollect = ref([]);
const occupationsCollect = ref([]);
const supportCompanyCollect = ref([]);
const supportedCompanyCollect = ref([]);
const driverRideCollect = ref([]);
const exportingStatus = ref(false);
const orgInfo = ref();
const parentOrgInfo = ref();

const filter = reactive({
  date: props.search_date ?? moment().format("yyyy/MM/DD"),
  user: null,
  type: "monthly",
});

const increaseMonth = () => {
  filter.date = moment(new Date(filter.date)).add(1, "M").format("yyyy/MM/DD");
};
const decreaseMonth = () => {
  filter.date = moment(new Date(filter.date)).subtract(1, "M").format("yyyy/MM/DD");
};

const getMonth = () => {
  return moment(new Date(filter.date)).format("yyyy年MM月");
};
const getUserName = () => {
  if (props.users) {
    return props.users.filter((f) => f.id == filter.user)[0]?.name;
  }
};

onMounted(async () => {
  if (props.user_id) {
    filter.user = parseInt(props.user_id);
  }
  await search();
});

const search = async () => {
  if (filter.date) {
    await axios
      .post(route("admin.record.search", { filter: filter }))
      .then((result) => {
        attendedUsers.value = result.data.attended_users;
        if (result.data.dakoku) {
          dakokuPageData.value = result.data.dakoku;
          workingDaysList.value = result.data.collect_days;
          workingHoursList.value = result.data.collect_times;
          otherFlgCountList.value = result.data.collect_other_flag_count;
          workContentCollect.value = result.data.work_content;
          occupationsCollect.value = result.data.occupations_collect;
          supportCompanyCollect.value = result.data.support_company_collect;
          supportedCompanyCollect.value = result.data.supported_company_collect;
          driverRideCollect.value = result.data.driver_ride_collect;
          dakokuData.value = result.data.dakoku;
          orgInfo.value = result.data.org;
          parentOrgInfo.value = result.data.parent_org;
        }
      })
      .catch((error) => {
        console.log(error);
      });
  }
};
watch(filter, async () => {
  await search();
});

const handleExportExcel = (type) => {
  if (!filter.user && type == "single") {
    toast.add({
      severity: "info",
      detail: "ユーザーを選択してください。",
      life: 2000,
    });
    return;
  }
  exportingStatus.value = true;
  axios
    .post(route("admin.record.monthly.export"), {
      filter: filter,
      type: type,
    })
    .then((res) => {
      if (res.data) {
        toast.add({
          severity: "success",
          summary: "出力成功！",
          life: 2000,
        });
        if (res.data?.path) {
          location.href = route("file_download", { file_path: res.data.path });
        }
        console.log(res.data);
      }
    })
    .finally(() => {
      exportingStatus.value = false;
    });
};

const rowStyle = (status, holiday, isBefore) => {
  if (holiday == "休日") {
    return "bg-orange-100";
  }
  if (!status && holiday != "休日") {
    return "bg-pink-200/20";
  }
  if (isBefore == "lt" && holiday != "休日") {
    return "bg-sky-200/30";
  }
};

const getHolidayStyle = (day, holiday) => {
  if (day == "日") {
    return "text-red-500 font-bold";
  } else if (day == "土") {
    return "text-sky-600 font-bold";
  }
  if (holiday == "休日") {
    return "text-red-500 font-bold";
  } else if (holiday == "有給") {
    return "bg-orange-100 text-orange-500 font-bold";
  }
};
</script>
<template>
  <Toast position="top-center" class="z-50" />
  <AdminLayout title="月次出勤簿">
    <MainContent>
      <template #header>
        <div class="flex items-center justify-between w-full">
          <div class="flex items-center gap-2">
            <FontAwesomeIcon icon="fa-solid fa-book" />
            <h3 class="flex items-center">
              月次出勤簿 -&nbsp;
              <Link
                :href="route('admin.users.show', { id: props.user_id })"
                class="underline"
                >{{ getUserName() }}
              </Link>
              ／{{ getMonth() }}度
            </h3>
            <!-- <small>指定された日・拒定されたグループの出動簿を表示します。</small> -->
          </div>
        </div>
      </template>
      <div class="w-full">
        <!-- 検索条件パネル -->
        <div class="filter-condition">
          <!-- 検索パネル -->
          <div class="flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center flex-wrap gap-2 toggle-filter-btn">
              <Button
                size="small"
                class="flex items-center gap-2 py-1"
                severity="info"
                @click="searchCondition = !searchCondition"
              >
                <i class="pi pi-search"></i>
                <span>検索条件</span>
                <i
                  class="pi"
                  :class="searchCondition ? 'pi-angle-up' : 'pi-angle-down'"
                ></i>
              </Button>
              <Button
                size="small"
                class="flex items-center gap-2 py-1"
                severity="info"
                @click="collectCondition = !collectCondition"
              >
                <i class="pi" :class="collectCondition ? 'pi-eye-slash' : 'pi-eye'"></i>
                <span v-if="collectCondition">集計データ非表示</span>
                <span v-else>集計データ表示</span>
                <i
                  class="pi"
                  :class="collectCondition ? 'pi-angle-up' : 'pi-angle-down'"
                ></i>
              </Button>
            </div>
            <div class="flex items-center flex-wrap gap-2 setting-print">
              <div class="excel-export">
                <Button
                  :label="`全員分Excel出力 ${attendedUsers}人`"
                  icon="pi pi-file-excel"
                  size="small"
                  severity="success"
                  class="py-1"
                  :disabled="exportingStatus"
                  :loading="exportingStatus"
                  @click="handleExportExcel('all')"
                />
              </div>
              <div class="excel-export">
                <Button
                  label="個人別Excel出力"
                  icon="pi pi-file-excel"
                  size="small"
                  severity="help"
                  class="py-1"
                  :disabled="exportingStatus"
                  :loading="exportingStatus"
                  @click="handleExportExcel('single')"
                />
              </div>
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
            </div>
          </div>
          <div v-if="searchCondition" class="p-4 mt-1 bg-white rounded-md shadow-lg">
            <div class="w-full">
              <div class="flex items-end gap-4">
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
                      @click="decreaseMonth"
                    />
                    <VueDatePicker
                      v-model="filter.date"
                      locale="ja"
                      modelType="yyyy/MM/dd"
                      format="yyyy年MM月"
                      month-picker
                      autoApply
                    >
                      <template #clear-icon="{ clear }"></template>
                    </VueDatePicker>
                    <Button
                      icon="pi pi-angle-right"
                      class="py-1"
                      size="small"
                      outlined
                      @click="increaseMonth"
                    />
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
                      optionValue="id"
                      filter
                      show-clear
                      class="w-64"
                      input-class="py-1.5"
                      scroll-height="480px"
                      placeholder="スタッフ選択"
                      empty-filter-message="検索結果なし"
                      empty-message="検索結果なし"
                    />
                  </div>
                </div>
                <!-- search btn -->
                <div class="flex justify-center mt-8">
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
              </div>
            </div>
          </div>
        </div>
        <!-- 集計データ -->
        <div
          v-if="collectCondition"
          class="w-full p-3 mt-4 overflow-hidden bg-white rounded-md shadow-lg all-data-collection"
        >
          <div
            class="grid grid-cols-1 gap-2 collection-items-pannel xl:grid-cols-3 md:gap-4"
          >
            <div class="first-col">
              <!-- working days -->
              <div class="w-full shadow-md collect-item working-days__item">
                <div class="item">
                  <div class="item-header">
                    <p>労働日数</p>
                  </div>
                  <div
                    v-if="workingDaysList && Object.keys(workingDaysList).length > 0"
                    class="item-content"
                  >
                    <div>
                      <div class="label-field">所定労働日数</div>
                      <div class="value-field">{{ workingDaysList?.rodo }}</div>
                    </div>
                    <div>
                      <div class="label-field">実動日数</div>
                      <div class="value-field">{{ workingDaysList?.jitsudo }}</div>
                    </div>
                    <div>
                      <div class="label-field">平日出勤日数</div>
                      <div class="value-field">{{ workingDaysList?.syukkin }}</div>
                    </div>
                    <div>
                      <div class="label-field">休日出勤日数</div>
                      <div class="value-field">
                        {{ workingDaysList?.holiday_syukkin }}
                      </div>
                    </div>
                    <div>
                      <div class="label-field">有給出勤日数</div>
                      <div class="value-field">
                        {{ workingDaysList?.paid_holiday_syukkin }}
                      </div>
                    </div>
                    <div>
                      <div class="label-field">日曜出勤日数</div>
                      <div class="value-field">
                        {{ workingDaysList?.sunday_syukkin_data }}
                      </div>
                    </div>
                    <div>
                      <div class="label-field">欠勤日数</div>
                      <div class="value-field">{{ workingDaysList?.kekkin }}</div>
                    </div>
                    <div>
                      <div class="label-field">遅刻日数</div>
                      <div class="value-field">{{ workingDaysList?.chikoku }}</div>
                    </div>
                    <div>
                      <div class="label-field">早退日数</div>
                      <div class="value-field">{{ workingDaysList?.sotai }}</div>
                    </div>
                  </div>
                  <div v-else class="empty-msg text-center">
                    <p>データがありません</p>
                  </div>
                </div>
              </div>

              <div class="w-full shadow-md collect-item occupations">
                <div class="item">
                  <div class="item-header">
                    <p>職種</p>
                  </div>
                  <div v-if="occupationsCollect?.length > 0" class="item-content">
                    <div v-for="(item, index) in occupationsCollect" :key="index">
                      <div class="label-field">{{ item.name }}</div>
                      <div class="value-field">{{ item.count }}回</div>
                    </div>
                  </div>
                  <div v-else class="empty-msg text-center">
                    <p>データがありません</p>
                  </div>
                </div>
              </div>
              <div class="w-full shadow-md collect-item work_content">
                <div class="item">
                  <div class="item-header">
                    <p>作業内容</p>
                  </div>
                  <div v-if="workContentCollect?.length > 0" class="item-content">
                    <div v-for="(item, index) in workContentCollect" :key="index">
                      <div class="label-field">{{ item.name }}</div>
                      <div class="value-field">{{ item.count }}回</div>
                    </div>
                  </div>
                  <div v-else class="empty-msg text-center">
                    <p>データがありません</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="second-col">
              <!-- working hours -->
              <div class="w-full shadow-md collect-item working-hours__item">
                <div class="item">
                  <div class="item-header">
                    <p>労働時間</p>
                  </div>
                  <div
                    v-if="workingHoursList && Object.keys(workingHoursList).length > 0"
                    class="item-content"
                  >
                    <div>
                      <div class="label-field">実労働時間</div>
                      <div class="value-field">
                        {{ workingHoursList?.real_working_times }}
                      </div>
                    </div>
                    <div>
                      <div class="label-field">平日労働時間</div>
                      <div class="value-field">{{ workingHoursList?.working_times }}</div>
                    </div>
                    <div>
                      <div class="label-field">休日労働時間</div>
                      <div class="value-field">
                        {{ workingHoursList?.holiday_working_times }}
                      </div>
                    </div>
                    <div>
                      <div class="label-field">実深夜時間</div>
                      <div class="value-field">{{ workingHoursList?.midight_times }}</div>
                    </div>
                    <div>
                      <div class="label-field">平日深夜時間</div>
                      <div class="value-field">{{ workingHoursList?.midight_times }}</div>
                    </div>

                    <div>
                      <div class="label-field">休日深夜時間</div>
                      <div class="value-field">
                        {{ workingHoursList?.holiday_midnight_times }}
                      </div>
                    </div>
                    <div>
                      <div class="label-field">実残業時間</div>
                      <div class="value-field">
                        {{ workingHoursList?.real_over_times }}
                      </div>
                    </div>
                    <div>
                      <div class="label-field">平日残業時間</div>
                      <div class="value-field">{{ workingHoursList?.over_times }}</div>
                    </div>

                    <div>
                      <div class="label-field">休日残業時間</div>
                      <div class="value-field">
                        {{ workingHoursList?.holiday_over_times }}
                      </div>
                    </div>

                    <div>
                      <div class="label-field"></div>
                      <div class="value-field"></div>
                    </div>
                  </div>
                  <div v-else class="empty-msg text-center">
                    <p>データがありません</p>
                  </div>
                </div>
              </div>
              <div class="w-full shadow-md collect-item organization-info">
                <!-- Org Infoo -->
                <div class="item">
                  <div class="item-header">
                    <p>組織情報</p>
                  </div>
                  <div v-if="orgInfo" class="item-content">
                    <div>
                      <div class="label-field">親組織名</div>
                      <div class="value-field">
                        {{ parentOrgInfo?.organization_name }}
                      </div>
                    </div>
                    <div>
                      <div class="label-field">組織名</div>
                      <div class="value-field">{{ orgInfo?.organization_name }}</div>
                    </div>
                    <div>
                      <div class="label-field">組織コード</div>
                      <div class="value-field">{{ orgInfo?.organization_code }}</div>
                    </div>
                    <div>
                      <div class="label-field">郵便番号</div>
                      <div class="value-field">
                        {{
                          parentOrgInfo?.organization_zipcode
                            ? `(親)${parentOrgInfo?.organization_zipcode ?? ""}`
                            : `(子)${orgInfo?.organization_zipcode ?? ""}`
                        }}
                      </div>
                    </div>
                    <div>
                      <div class="label-field">住所</div>
                      <div class="value-field">
                        {{
                          parentOrgInfo?.organization_address
                            ? `(親)${parentOrgInfo?.organization_address ?? ""}`
                            : `(子)${orgInfo?.organization_address ?? ""}`
                        }}
                      </div>
                    </div>
                    <div>
                      <div class="label-field">代表者名</div>
                      <div class="value-field">
                        {{
                          parentOrgInfo?.organization_master_name
                            ? `(親)${parentOrgInfo?.organization_master_name ?? ""}`
                            : `(子)${orgInfo?.organization_master_name ?? ""}`
                        }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="third-col">
              <div class="w-full shadow-md collect-item supportcompany">
                <div class="item">
                  <div class="item-header">
                    <p>応援に来てもらった</p>
                  </div>
                  <div v-if="supportCompanyCollect?.length > 0" class="item-content">
                    <div v-for="(item, index) in supportCompanyCollect" :key="index">
                      <div class="label-field">{{ item.name }}</div>
                      <div class="value-field">{{ item.count }}回</div>
                    </div>
                  </div>
                  <div v-else class="empty-msg text-center">
                    <p>データがありません</p>
                  </div>
                </div>
              </div>
              <div class="w-full shadow-md collect-item supportcompany">
                <div class="item">
                  <div class="item-header">
                    <p>応援に行った</p>
                  </div>
                  <div v-if="supportedCompanyCollect?.length > 0" class="item-content">
                    <div v-for="(item, index) in supportedCompanyCollect" :key="index">
                      <div class="label-field">{{ item.name }}</div>
                      <div class="value-field">{{ item.count }}回</div>
                    </div>
                  </div>
                  <div v-else class="empty-msg text-center">
                    <p>データがありません</p>
                  </div>
                </div>
              </div>
              <!-- other attend status -->
              <div class="w-full shadow-md collect-item other__item">
                <div class="item">
                  <div class="item-header">
                    <p>残業、遅刻、早退、有給、研修など</p>
                  </div>
                  <div v-if="otherFlgCountList?.length > 0" class="item-content">
                    <div v-for="(item, index) in otherFlgCountList" :key="index">
                      <div class="label-field">{{ item.name }}</div>
                      <div class="value-field">{{ item.count }}回</div>
                    </div>
                  </div>
                  <div v-else class="empty-msg text-center">
                    <p>データがありません</p>
                  </div>
                </div>
              </div>
              <div class="w-full shadow-md collect-item ride-option">
                <div class="item">
                  <div class="item-header">
                    <p>運転・同乗</p>
                  </div>
                  <div v-if="driverRideCollect?.length > 0" class="item-content">
                    <div v-for="(item, index) in driverRideCollect" :key="index">
                      <div class="label-field">{{ item.name }}</div>
                      <div class="value-field">{{ item.count }}回</div>
                    </div>
                  </div>
                  <div v-else class="empty-msg text-center">
                    <p>データがありません</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- 月次出退勤データ -->
        <div
          class="w-full p-3 mt-4 overflow-auto bg-white rounded-md shadow-lg dakou-data-list"
          :class="
            searchCondition ? 'min-h-[calc(100vh-620px)]' : 'min-h-[calc(100vh-180px)]'
          "
        >
          <div class="w-full custom-table center overflow-auto text-center">
            <table class="w-full table-auto">
              <thead>
                <tr>
                  <th>No</th>
                  <th>日付</th>
                  <th>曜日</th>
                  <th>休日区分</th>
                  <th>勤怠状況</th>
                  <th>１日•半日</th>
                  <th>シフト</th>
                  <th>出勤時刻</th>
                  <th>退勤時刻</th>
                  <th>労働時間</th>
                  <th>実労働時間</th>
                  <th>休憩時間</th>
                  <th>残業</th>
                  <th>出入詳細</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(item, index) in dakokuData"
                  :key="index"
                  :class="rowStyle(item.status, item.holiday, item.is_before)"
                >
                  <td class="no">{{ index + 1 }}</td>
                  <td class="dakoku_date">
                    {{ moment(item.date).format("M/D") }}
                  </td>
                  <td class="day" :class="getHolidayStyle(item.day, item.holiday)">
                    <span>
                      {{ item.day }}
                    </span>
                  </td>
                  <td>{{ item.holiday }}</td>
                  <td>{{ item.type }}</td>
                  <td>{{ item.dtype }}</td>
                  <td>
                    <span v-if="item.status">{{ item.shift }}</span>
                  </td>
                  <td>{{ item.syukin }}</td>
                  <td>{{ item.taikin }}</td>
                  <td>
                    <span v-if="item.status">{{ item.shift_times }}</span>
                  </td>
                  <td>{{ item.work_time }}</td>
                  <td>
                    <span v-if="item.status">{{ item.rest_time }}</span>
                  </td>
                  <td>{{ item.other_flg }}</td>
                  <td>
                    <Link
                      v-if="item.id"
                      :href="route('admin.master.attendance.show', { id: item.id })"
                    >
                      <Button
                        label="詳細"
                        class="p-button-sm py-0.5"
                        severity="secondary"
                      />
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </MainContent>
  </AdminLayout>
  <Dialog
    v-model:visible="exportingStatus"
    modal
    :draggable="false"
    header="エクセル出力中です。"
    :closable="false"
    class="w-full max-w-lg"
  >
    <div class="p-3">
      <img
        :src="`${$page.props.assetUrl}/assets/icon/scanner.gif`"
        alt="scanner"
        class="mx-auto"
      />
      <div class="txt text-center text-md mt-4 font-bold">
        処理に時間がかかる場合が<br />ありますのでお待ちください。
      </div>
    </div>
  </Dialog>
</template>
<style lang="scss" scoped>
.collection-items-pannel {
  .collect-item {
    border: 1px solid #0d9488;
    border-radius: 5px;
    overflow: hidden;
    height: fit-content;
    margin-bottom: 1rem;

    .item {
      .item-header {
        p {
          text-align: left;
          padding: 0.4rem;
          background-color: #0d9488;
          color: white;
        }
      }

      .item-content {
        display: grid;
        grid-template-columns: 1fr 1fr;

        & > div {
          width: 100%;
          display: grid;
          grid-template-columns: 70% 30%;
          margin-bottom: -1px;
          font-size: 14px;

          .label-field {
            display: flex;
            align-items: center;
            background-color: #cffafe70;
            padding: 3px 12px;
            border-bottom: 1px dashed #14b8a6;
          }

          .value-field {
            display: flex;
            align-items: center;
            padding: 3px 12px;
            border-bottom: 1px dashed #14b8a6;
          }
        }
      }
    }
    &.organization-info {
      .item {
        .item-content {
          display: block;
          & > div {
            grid-template-columns: 100px 1fr;
          }
        }
      }
    }
  }
}

.dakou-data-list {
  table {
    border: 1px solid #d5d5d5;

    th {
      background-color: #e6e6e6;
      padding: 0.5rem;
    }

    td {
      padding: 0.25rem;
    }

    tr {
      &:not(:first-child) {
        border-top: 1px solid #d5d5d5;
      }

      td {
        &:not(:first-child) {
          border-left: 1px solid #d5d5d5;
        }
      }

      &.none_data {
        background-color: #d69ef0;
      }

      &.not_full_time {
        background-color: #21b0f070;
      }
    }
  }
}
</style>
