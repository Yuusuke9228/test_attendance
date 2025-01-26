<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import { ref, onMounted, computed } from "vue";
import { driveRideOptions, supportCompanyFlag } from "@/Utils/field";

const props = defineProps({
  users: Object,
  attendanceDetail: Object,
});

const detail = computed(() => {
  return props.attendanceDetail;
});

const getWorkers = (json) => {
  if (json) {
    let userArr = JSON.parse(json);
    let users = "";
    userArr.forEach((userId) => {
      users += props.users.filter((filter) => filter.id == userId)[0].name + "、";
    });
    users = users.slice(0, -1);
    return users;
  }
};
</script>
<template>
  <AdminLayout title="出勤管理（打刻データ）">
    <MainContent>
      <template #header>
        <FontAwesomeIcon icon="fa-solid fa-clock-rotate-left" />
        <h3>
          出勤管理（打刻データ）
          <small>出勤管理情報を管理します。</small>
        </h3>
      </template>
      <MasterDetailBox link="attendance" :data="detail">
        <div class="w-full center">
          <div class="relative flex flex-col m-auto max-w-7xl main-info">
            <div class="pb-4 text-lg input-form">
              <div class="mt-4 form-inner center">
                <div class="label-field label-right">
                  <InputLabel value="打刻区分" />
                </div>
                <div class="input-field">
                  <p>{{ detail.attend_type?.attend_type_name }}</p>
                </div>
              </div>

              <div class="mt-4 form-inner center">
                <div class="label-field label-right">
                  <InputLabel value="１日•半日" />
                </div>
                <div class="input-field">
                  <p>{{ detail.dp_type }}</p>
                </div>
              </div>

              <div class="mt-4 form-inner center">
                <div class="label-field label-right">
                  <InputLabel value="ユーザー名" />
                </div>
                <div class="input-field">
                  <Link
                    :href="route('admin.users.show', { id: detail.user.id })"
                    class="font-bold underline text-sky-600"
                    >{{ detail.user?.name }}</Link
                  >
                </div>
              </div>

              <div class="mt-4 form-inner center">
                <div class="label-field label-right">
                  <InputLabel value="日付" />
                </div>
                <div class="input-field">
                  <p>{{ detail.target_date }}</p>
                </div>
              </div>

              <div class="mt-4 form-inner center">
                <div class="label-field label-right">
                  <InputLabel value="出勤時間" />
                </div>
                <div class="input-field">
                  <p>{{ detail.dp_syukkin_time }}</p>
                </div>
              </div>

              <div class="mt-4 form-inner center">
                <div class="label-field label-right">
                  <InputLabel value="退勤時刻" />
                </div>
                <div class="input-field">
                  <p>{{ detail.dp_taikin_time }}</p>
                </div>
              </div>

              <div class="mt-4 form-inner center">
                <div class="label-field label-right">
                  <InputLabel value="運転・同乗" />
                </div>
                <div class="input-field">
                  {{ detail.dp_ride_flg }}
                </div>
              </div>

              <div class="mt-4 form-inner center">
                <div class="label-field label-right">
                  <InputLabel value="残業、遅刻、早退、有給、研修など" />
                </div>
                <div class="input-field">
                  <p>{{ detail.attend_status?.attend_name }}</p>
                </div>
              </div>

              <div class="mt-4 form-inner center">
                <div class="label-field label-right">
                  <InputLabel value="備考" />
                </div>
                <div class="input-field">
                  <p>{{ detail.dp_memo }}</p>
                </div>
              </div>

              <div class="mt-4 form-inner center">
                <div class="label-field label-right">
                  <InputLabel value="打刻場所" />
                </div>
                <div class="input-field">
                  <p>{{ detail.dp_dakou_address }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </MasterDetailBox>
      <MasterDetailBox contentClass="mt-4 mb-8" title="打刻詳細">
        <div class="w-full datatable center">
          <DataTable
            :value="detail.dakoku_children"
            data-key="id"
            selectionMode="multiple"
            class="p-datatable-sm"
          >
            <Column field="id" header="ID" sortable />
            <Column header="応援区分" sortable>
              <template #body="slotProps">
                {{
                  supportCompanyFlag.filter(
                    (filter) => filter.value == slotProps.data?.dp_support_flg
                  )[0]?.label
                }}
              </template>
            </Column>
            <Column
              field="support_company.support_company_name"
              header="応援来てもらった先"
              sortable
            />
            <Column
              field="supported_company.supported_company_name"
              header="応援に行った先"
              sortable
            />
            <Column field="dp_nums_of_people" header="応援人数入力" sortable />
            <Column field="occupation.occupation_name" header="職種" sortable>
              <template #body="slotProps">
                <div>
                  <span>
                    {{ slotProps.data.occupation?.occupation_name }}
                  </span>
                  <span v-if="slotProps.data?.dp_unique_counter">
                    ({{ slotProps.data?.dp_unique_counter }}回)
                  </span>
                </div>
              </template>
            </Column>
            <Column field="work_content.work_content_name" header="作業内容" sortable />
            <Column field="work_location.location_name" header="現場" sortable />
            <Column field="timezone.detail_times" header="時間帯" sortable />
          </DataTable>
        </div>
      </MasterDetailBox>
    </MainContent>
  </AdminLayout>
</template>
