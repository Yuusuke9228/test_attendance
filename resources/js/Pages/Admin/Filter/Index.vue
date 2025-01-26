<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import moment from "moment";
import { useToast } from "primevue/usetoast";

const props = defineProps({
  keyword: String,
  allUsers: Object,
  dakou_data: Object,
  dakou_child: Object,
  users: Object,
  timezones: Object,
  schedule: Object,
  work_location: Object,
  break_times: Object,
  occupation: Object,
  work_content: Object,
  attend_status: Object,
  support_company: Object,
  supported_company: Object,
});

const toast = useToast();
const dakokuData = ref([]);
const dakokuChild = ref([]);
const userList = ref([]);
const timeZoneData = ref([]);
const scheduleData = ref([]);
const locationData = ref([]);
const breakTimeData = ref([]);
const occupationData = ref([]);
const workContentData = ref([]);
const attendStatusData = ref([]);
const supportCompanyData = ref([]);
const supportedCompanyData = ref([]);
const searchResultNumber = ref(0);

if (props.dakou_data) {
  dakokuData.value = props.dakou_data.map((item) => {
    item.created_at = moment(item.created_at).format("yyyy-MM-DD HH:mm:ss");
    item.updated_at = moment(item.updated_at).format("yyyy-MM-DD HH:mm:ss");
    item.other_flag = item.attend_status?.attend_name;
    return item;
  });
}
if (props.dakou_child) {
  dakokuChild.value = props.dakou_child.map((item) => {
    item.created_at = moment(item.created_at).format("yyyy-MM-DD HH:mm:ss");
    item.updated_at = moment(item.updated_at).format("yyyy-MM-DD HH:mm:ss");
    item.other_flag = item.dakoku?.attend_status?.attend_name;
    return item;
  });
}
if (props.users) {
  userList.value = props.users.map((item) => {
    item.created_at = moment(item.created_at).format("yyyy-MM-DD HH:mm:ss");
    item.updated_at = moment(item.updated_at).format("yyyy-MM-DD HH:mm:ss");
    return item;
  });
}
if (props.timezones) {
  timeZoneData.value = props.timezones.map((item) => {
    item.created_at = item.created_at
      ? moment(item.created_at).format("yyyy-MM-DD HH:mm:ss")
      : null;
    item.updated_at = item.updated_at
      ? moment(item.updated_at).format("yyyy-MM-DD HH:mm:ss")
      : null;
    return item;
  });
}
if (props.schedule) {
  scheduleData.value = props.schedule.map((item) => {
    let users = "";
    JSON.parse(item?.user_id).map((item) => {
      users += props.allUsers.filter((filter) => filter.id == item)[0].name + "、";
    });
    item.created_at = moment(item.created_at).format("yyyy-MM-DD HH:mm:ss");
    item.updated_at = moment(item.updated_at).format("yyyy-MM-DD HH:mm:ss");
    item.users = users !== "" ? users.slice(0, -1) : "";
    return item;
  });
}
if (props.work_location) {
  locationData.value = props.work_location.map((item) => {
    item.location_flag = item.location_flag == 1 ? "YES" : "NO";
    item.created_at = moment(item.created_at).format("yyyy-MM-DD HH:mm:ss");
    item.updated_at = moment(item.updated_at).format("yyyy-MM-DD HH:mm:ss");
    return item;
  });
}
if (props.break_times) {
  breakTimeData.value = props.break_times.map((item) => {
    item.created_at = moment(item.created_at).format("yyyy-MM-DD HH:mm:ss");
    item.updated_at = moment(item.updated_at).format("yyyy-MM-DD HH:mm:ss");
    return item;
  });
}
if (props.occupation) {
  occupationData.value = props.occupation.map((item) => {
    item.created_at = moment(item.created_at).format("yyyy-MM-DD HH:mm:ss");
    item.updated_at = moment(item.updated_at).format("yyyy-MM-DD HH:mm:ss");
    return item;
  });
}
if (props.work_content) {
  workContentData.value = props.work_content.map((item) => {
    item.created_at = moment(item.created_at).format("yyyy-MM-DD HH:mm:ss");
    item.updated_at = moment(item.updated_at).format("yyyy-MM-DD HH:mm:ss");
    return item;
  });
}

if (props.attend_status) {
  attendStatusData.value = props.attend_status.map((item) => {
    item.created_at = moment(item.created_at).format("yyyy-MM-DD HH:mm:ss");
    item.updated_at = moment(item.updated_at).format("yyyy-MM-DD HH:mm:ss");
    return item;
  });
}
if (props.support_company) {
  supportCompanyData.value = props.support_company.map((item) => {
    item.created_at = moment(item.created_at).format("yyyy-MM-DD HH:mm:ss");
    item.updated_at = moment(item.updated_at).format("yyyy-MM-DD HH:mm:ss");
    return item;
  });
}
if (props.supported_company) {
  supportedCompanyData.value = props.supported_company.map((item) => {
    item.created_at = moment(item.created_at).format("yyyy-MM-DD HH:mm:ss");
    item.updated_at = moment(item.updated_at).format("yyyy-MM-DD HH:mm:ss");
    return item;
  });
}

const toastBack = ref();
onMounted(() => {
  searchResultNumber.value =
    dakokuData.value.length +
    dakokuChild.value.length +
    userList.value.length +
    timeZoneData.value.length +
    scheduleData.value.length +
    locationData.value.length +
    breakTimeData.value.length +
    occupationData.value.length +
    workContentData.value.length +
    attendStatusData.value.length +
    supportCompanyData.value.length +
    supportedCompanyData.value.length;
  if (searchResultNumber.value > 0) {
    toastBack.value = "bg-green-500";
    setTimeout(() => {
      toast.add({
        severity: "custom",
        summary: searchResultNumber.value + "件のデータが検索されました。",
        life: 2000,
        group: "headless",
      });
    }, 500);
  } else {
    toastBack.value = "bg-rose-500";
    setTimeout(() => {
      toast.add({
        severity: "custom",
        summary: "一致するデータがありません",
        life: 2000,
        group: "headless",
      });
    }, 500);
  }

  const filterKey = new RegExp(props.keyword, "gi");
  let contents = document.querySelectorAll(".box-content");
  contents.forEach((content) => {
    content.innerHTML = content.innerHTML.replace(
      filterKey,
      (match) => `<span class="bg-green-300">${match}</span>`
    );
  });
});
</script>

<template>
  <CustomToast group="headless" position="top-center" :bg-class="toastBack" />
  <AdminLayout title="統合検索">
    <MainContent title="統合検索" icon="pi-search">
      <div
        v-show="searchResultNumber > 0"
        class="global-filter-page w-full grid grid-cols-1 gap-8"
      >
        <!-- dakou_data -->
        <ContentBox v-if="dakokuData.length > 0" title="打刻データ" icon="pi-building">
          <div class="w-full border datatable center">
            <DataTable :value="dakokuData" data-key="id" class="p-datatable-sm">
              <Column field="id" header="ID" sortable />
              <Column field="target_date" header="日付" sortable />
              <Column field="user.name" header="ユーザー" sortable />
              <Column field="attend_type.attend_type_name" header="区分" sortable />
              <Column field="dp_syukkin_time" header="出勤" sortable />
              <Column field="dp_taikin_time" header="退勤" sortable />
              <Column field="dp_ride_flg" header="運転・同乗" sortable />
              <Column
                field="other_flag"
                header="残業、遅刻、早退、有給、研修など"
                sortable
              />
              <Column field="dp_memo" header="備考" sortable />
              <Column field="updated_at" header="更新" sortable />
              <Column header="操作">
                <template #body="slotProps">
                  <div class="flex items-center justify-center gap-3">
                    <Link
                      :href="
                        route('admin.master.attendance.show', { id: slotProps.data.id })
                      "
                    >
                      <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                    </Link>
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </ContentBox>

        <ContentBox
          v-if="dakokuChild.length > 0"
          title="打刻データ(詳細)"
          icon="pi-directions"
        >
          <div class="w-full border datatable center">
            <DataTable :value="dakokuChild" data-key="id" class="p-datatable-sm">
              <Column field="id" header="ID" sortable />
              <Column field="dakoku.target_date" header="日付" sortable />
              <Column field="dakoku.user.name" header="ユーザー" sortable />
              <Column
                field="dakoku.attend_type.attend_type_name"
                header="区分"
                sortable
              />
              <Column field="dakoku.dp_syukkin_time" header="出勤" sortable />
              <Column field="dakoku.dp_taikin_time" header="退勤" sortable />
              <Column
                field="support_company.support_company_name"
                header="応援会社"
                sortable
              />
              <Column
                field="supported_company.supported_company_name"
                header="応援先会社"
                sortable
              />
              <Column field="occupation.occupation_name" header="職種" sortable />
              <Column field="work_content.work_content_name" header="作業内容" sortable />
              <Column field="work_location.location_name" header="現場" sortable />
              <Column field="dakoku.dp_ride_flg" header="運転・同乗" sortable />
              <Column field="timezone.detail_times" header="時間帯" sortable />
              <Column
                field="other_flag"
                header="残業、遅刻、早退、有給、研修など"
                sortable
              />
              <Column field="dakoku.dp_memo" header="備考" sortable />
              <Column field="updated_at" header="更新" sortable />
              <Column header="操作">
                <template #body="slotProps">
                  <div class="flex items-center justify-center gap-3">
                    <Link
                      :href="
                        route('admin.master.attendance.show', {
                          id: slotProps.data.dakoku.id,
                        })
                      "
                    >
                      <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                    </Link>
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </ContentBox>

        <ContentBox v-if="userList.length > 0" title="ユーザー" icon="pi-users">
          <div class="w-full border datatable center">
            <DataTable
              :value="userList"
              data-key="id"
              selectionMode="multiple"
              class="p-datatable-sm"
            >
              <Column field="id" header="ID" sortable />
              <Column field="code" header="ユーザーコード" sortable />
              <Column field="name" header="ユーザー名" sortable />
              <Column field="email" header="メールアドレス" sortable />
              <Column header="ログインユーザー設定" sortable>
                <template #body="slotProps">
                  {{ slotProps.data.available ? "YES" : "NO" }}
                </template>
              </Column>
              <Column header="勤務形態コード" sortable>
                <template #body="slotProps">
                  {{ slotProps.data?.user_data?.break_times?.break_work_pattern_cd }}
                  {{
                    slotProps.data?.user_data?.break_times?.organization
                      ?.organization_name
                  }}
                  {{ slotProps.data?.user_data?.break_times?.break_name }}
                </template>
              </Column>
              <Column field="created_at" header="登録日" />
              <Column field="updated_at" header="変更日" />
              <Column header="操作">
                <template #body="slotProps">
                  <div class="flex items-center justify-center gap-3">
                    <Link :href="route('admin.users.show', { id: slotProps.data.id })">
                      <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                    </Link>
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </ContentBox>

        <ContentBox v-if="timeZoneData.length > 0" title="時間帯区分" icon="pi-clock">
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
                      :href="
                        route('admin.master.timezone.show', { id: slotProps.data.id })
                      "
                    >
                      <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                    </Link>
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </ContentBox>

        <ContentBox
          v-if="scheduleData.length > 0"
          title="作業予定管理"
          icon="pi-calendar"
        >
          <div class="w-full border datatable center">
            <DataTable :value="scheduleData" data-key="id" class="p-datatable-sm">
              <Column field="id" header="ID" sortable />
              <Column
                field="schedule_date"
                header="日付"
                sortable
                class="whitespace-nowrap"
              />
              <Column
                field="occupations.occupation_name"
                header="職種"
                sortable
                class="whitespace-nowrap"
              />
              <Column
                field="locations.location_name"
                header="現場"
                sortable
                class="whitespace-nowrap"
              />
              <Column field="users" header="従業員" sortable />
              <Column field="schedule_start_time" header="開始予定時刻" sortable />
              <Column field="schedule_end_time" header="終了予定時刻" sortable />
              <Column field="created_at" header="作成日時" sortable />
              <Column field="updated_at" header="更新日時" sortable />
              <Column header="操作">
                <template #body="slotProps">
                  <div class="flex items-center justify-center gap-3">
                    <Link
                      :href="
                        route('admin.master.schedule.show', { id: slotProps.data.id })
                      "
                    >
                      <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                    </Link>
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </ContentBox>

        <ContentBox v-if="locationData.length > 0" title="現場管理" icon="pi-map">
          <div class="w-full border datatable center">
            <DataTable
              :value="locationData"
              data-key="id"
              selectionMode="multiple"
              class="p-datatable-sm"
            >
              <Column field="id" header="ID" sortable />
              <Column field="location_name" header="現場名" sortable />
              <Column field="location_address" header="住所" sortable />
              <Column field="location_flag" header="有効無効" sortable />
              <Column field="created_at" header="作成日時" sortable />
              <Column field="updated_at" header="更新日時" sortable />
              <Column header="操作">
                <template #body="slotProps">
                  <div class="flex items-center justify-center gap-3">
                    <Link
                      :href="
                        route('admin.master.location.show', { id: slotProps.data.id })
                      "
                    >
                      <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                    </Link>
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </ContentBox>

        <ContentBox
          v-if="breakTimeData.length > 0"
          title="休憩時間・勤務形態管理"
          icon="pi-clock"
        >
          <div class="w-full overflow-auto border realtive datatable center">
            <DataTable
              :value="breakTimeData"
              data-key="id"
              selectionMode="multiple"
              class="p-datatable-sm"
            >
              <Column field="id" header="ID" sortable />
              <Column field="break_work_pattern_cd" header="勤務形態コード" sortable />
              <Column
                field="organization.organization_name"
                header="組織"
                sortable
                class="whitespace-nowrap"
              />
              <Column field="break_name" header="管理名" sortable />
              <Column field="break_start_time" header="勤務開始時刻" sortable />
              <Column field="break_end_time" header="勤務終了時刻" sortable />
              <Column field="break_start_time1" header="休憩開始時刻１" sortable />
              <Column field="break_end_time1" header="休憩終了時刻１" sortable />
              <Column field="break_start_time2" header="休憩開始時刻２" sortable />
              <Column field="break_end_time2" header="休憩終了時刻２" sortable />
              <Column field="break_start_time3" header="休憩開始時刻３" sortable />
              <Column field="break_end_time3" header="休憩終了時刻３" sortable />
              <Column
                field="updated_at"
                header="更新日時"
                sortable
                bodyClass="whitespace-nowrap"
              />
              <Column header="操作">
                <template #body="slotProps">
                  <div class="flex items-center justify-center gap-3">
                    <Link
                      :href="
                        route('admin.master.breaktime.show', { id: slotProps.data.id })
                      "
                    >
                      <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                    </Link>
                    <Link
                      :href="
                        route('admin.master.breaktime.edit', { id: slotProps.data.id })
                      "
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
        </ContentBox>

        <ContentBox v-if="occupationData.length > 0" title="職種管理" icon="pi-flag">
          <div class="w-full border datatable center">
            <DataTable
              :value="occupationData"
              data-key="id"
              selectionMode="multiple"
              class="p-datatable-sm"
            >
              <Column field="id" header="ID" sortable />
              <Column field="occupation_name" header="職種名" sortable />
              <Column field="updated_at" header="更新日時" sortable />
              <Column header="操作" class="">
                <template #body="slotProps">
                  <div class="flex items-center justify-center gap-3">
                    <Link
                      :href="
                        route('admin.master.occupation.show', { id: slotProps.data.id })
                      "
                    >
                      <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                    </Link>
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </ContentBox>

        <ContentBox v-if="workContentData.length > 0" title="作業内容" icon="pi-wrench">
          <div class="w-full border datatable center">
            <DataTable
              :value="workContentData"
              data-key="id"
              selectionMode="multiple"
              class="p-datatable-sm"
            >
              <Column field="id" header="ID" sortable />
              <Column field="occupation.occupation_name" header="職種管理" sortable />
              <Column field="work_content_name" header="作業名" sortable />
              <Column field="created_at" header="作成日時" sortable />
              <Column field="updated_at" header="更新日時" sortable />
              <Column header="操作">
                <template #body="slotProps">
                  <div class="flex items-center justify-center gap-3">
                    <Link
                      :href="
                        route('admin.master.work_contents.show', {
                          id: slotProps.data.id,
                        })
                      "
                    >
                      <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                    </Link>
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </ContentBox>

        <ContentBox
          v-if="attendStatusData.length > 0"
          title="残業・早退・遅刻"
          icon="pi-tag"
        >
          <div class="w-full border datatable center">
            <DataTable
              :value="attendStatusData"
              data-key="id"
              selectionMode="multiple"
              class="p-datatable-sm"
            >
              <Column field="id" header="ID" sortable />
              <Column field="attend_name" header="	選択肢表示名" sortable />
              <Column field="created_at" header="作成日時" sortable />
              <Column field="updated_at" header="更新日時" sortable />
              <Column header="操作">
                <template #body="slotProps">
                  <div class="flex items-center justify-center gap-3">
                    <Link
                      :href="
                        route('admin.master.attend_statuses.show', {
                          id: slotProps.data.id,
                        })
                      "
                    >
                      <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                    </Link>
                    <Link
                      :href="
                        route('admin.master.attend_statuses.edit', {
                          id: slotProps.data.id,
                        })
                      "
                    >
                      <FontAwesomeIcon
                        icon="fa-solid fa-pen-to-square"
                        class="text-teal-500"
                      />
                    </Link>
                    <FontAwesomeIcon
                      icon="fa-solid fa-trash-can"
                      class="text-rose-500"
                      @click="deletelConfirmVisible(slotProps.data.id)"
                    />
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </ContentBox>

        <ContentBox
          v-if="supportCompanyData.length > 0"
          title="応援に来てもらう会社 "
          icon="pi-thumbs-up"
        >
          <div class="w-full border datatable center">
            <DataTable
              :value="supportCompanyData"
              data-key="id"
              selectionMode="multiple"
              class="p-datatable-sm"
            >
              <Column field="id" header="ID" sortable />
              <Column field="support_company_name" header="会社名" sortable />
              <Column field="support_company_person" header="担当者" sortable />
              <Column field="support_company_email" header="メール" sortable />
              <Column field="support_company_tel" header="電話" sortable />
              <Column field="support_company_zipcode" header="郵便番号" sortable />
              <Column field="support_company_address" header="住所" sortable />
              <Column field="created_at" header="作成日時" sortable />
              <Column field="updated_at" header="更新日時" sortable />
              <Column header="操作">
                <template #body="slotProps">
                  <div class="flex items-center justify-center gap-3">
                    <Link
                      :href="
                        route('admin.master.support_company.show', {
                          id: slotProps.data.id,
                        })
                      "
                    >
                      <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                    </Link>
                    <Link
                      :href="
                        route('admin.master.support_company.edit', {
                          id: slotProps.data.id,
                        })
                      "
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
        </ContentBox>

        <ContentBox
          v-if="supportedCompanyData.length > 0"
          title="応援に行く先の会社 "
          icon="pi-thumbs-up-fill"
        >
          <div class="w-full border datatable center">
            <DataTable
              :value="supportedCompanyData"
              data-key="id"
              selectionMode="multiple"
              class="p-datatable-sm"
            >
              <Column field="id" header="ID" sortable />
              <Column field="supported_company_name" header="会社名" sortable />
              <Column field="supported_company_person" header="担当者" sortable />
              <Column field="supported_company_email" header="メール" sortable />
              <Column field="supported_company_tel" header="電話" sortable />
              <Column field="supported_company_zipcode" header="郵便番号" sortable />
              <Column field="supported_company_address" header="住所" sortable />
              <Column field="created_at" header="作成日時" sortable />
              <Column field="updated_at" header="更新日時" sortable />
              <Column header="操作">
                <template #body="slotProps">
                  <div class="flex items-center justify-center gap-3">
                    <Link
                      :href="
                        route('admin.master.supported_company.show', {
                          id: slotProps.data.id,
                        })
                      "
                    >
                      <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                    </Link>
                    <Link
                      :href="
                        route('admin.master.supported_company.edit', {
                          id: slotProps.data.id,
                        })
                      "
                    >
                      <FontAwesomeIcon
                        icon="fa-solid fa-pen-to-square"
                        class="text-teal-500"
                      />
                    </Link>
                    <FontAwesomeIcon
                      icon="fa-solid fa-trash-can"
                      class="text-rose-500"
                      @click="deleteConfirmVsible(slotProps.data.id)"
                    />
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </ContentBox>
      </div>
      <div v-show="searchResultNumber == 0">
        <ContentBox>
          <div class="w-full text-center text-lg">
            <p><i class="pi pi-info-circle text-4xl"></i></p>
            <p>一致するデータがありません</p>
          </div>
        </ContentBox>
      </div>
    </MainContent>
  </AdminLayout>
</template>
