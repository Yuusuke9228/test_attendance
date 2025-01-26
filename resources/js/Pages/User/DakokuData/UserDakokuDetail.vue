<script setup>
import moment from "moment";
import { onMounted, ref } from "vue";
import { supportCompanyFlag } from "@/Utils/field";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  dakokuDetail: Object,
  users: Object,
});

const data = ref([]);
const child = ref([]);

const params = new URLSearchParams(window.location.search);
onMounted(() => {
  data.value = props.dakokuDetail;
  child.value = props.dakokuDetail?.dakoku_children.map((item, index) => {
    item.no = index + 1;
    if (item.dp_workers && item.dp_workers != "null") {
      let workers = JSON.parse(item?.dp_workers);
      let workersNames = "";
      workers.forEach((item) => {
        workersNames += props.users.filter((filter) => filter.id == item)[0]?.name + "、";
      });
      // remove last comma
      workersNames.slice(0, -1);
      item.workers = workersNames;
    }
    return item;
  });
});
</script>
<template>
  <AuthenticatedLayout title="打刻詳細">
    <div class="w-full max-w-md p-0 m-auto mt-4 md:p-6 md:max-w-7xl user-page__dashboard">
      <div class="box-content">
        <div class="p-3 system-values">
          <ul class="flex items-stretch justify-center system-values-list">
            <li>
              <p class="system-values-label">ID</p>
              <p class="system-values-item">{{ data?.id }}</p>
            </li>
            <li>
              <p class="system-values-label">作成日時</p>
              <p class="system-values-item">
                {{
                  data?.created_at
                    ? moment(data?.created_at).format("yyyy/MM/DD HH:mm:ss")
                    : ""
                }}
              </p>
            </li>
            <li>
              <p class="system-values-label">更新日時</p>
              <p class="system-values-item">
                {{
                  data?.updated_at
                    ? moment(data?.updated_at).format("yyyy/MM/DD HH:mm:ss")
                    : ""
                }}
              </p>
            </li>
            <li>
              <p class="system-values-label">変更者</p>
              <p class="system-values-item">
                {{ data?.author?.name }}
              </p>
            </li>
          </ul>
        </div>
      </div>
      <div class="p-4 bg-white rounded-lg shadow-lg md:p-6">
        <div class="detail-inner">
          <div class="label-field label-right">
            <InputLabel value="日付" />
          </div>
          <div class="input-field">
            {{ data.target_date ?? params.get("date") }}
          </div>
        </div>

        <div class="detail-inner">
          <div class="label-field label-right">
            <InputLabel value="１日•半日" />
          </div>
          <div class="input-field">
            {{ data.dp_type }}
          </div>
        </div>

        <div class="detail-inner">
          <div class="label-field label-right">
            <InputLabel value="打刻区分" />
          </div>
          <div class="input-field">
            {{ data.attend_type?.attend_type_name }}
          </div>
        </div>

        <div class="detail-inner">
          <div class="label-field label-right">
            <InputLabel value="出勤時間" />
          </div>
          <div class="input-field">
            {{ data?.dp_syukkin_time }}
          </div>
        </div>

        <div class="detail-inner">
          <div class="label-field label-right">
            <InputLabel value="退勤時刻" />
          </div>
          <div class="input-field">
            {{ data?.dp_taikin_time }}
          </div>
        </div>

        <div class="detail-inner">
          <div class="label-field label-right">
            <InputLabel value="運転・同乗" />
          </div>
          <div class="input-field">
            {{ data?.dp_ride_flg }}
          </div>
        </div>

        <div class="detail-inner">
          <div class="label-field label-right">
            <InputLabel value="残業、遅刻、早退、有給、研修など" />
          </div>
          <div class="input-field">
            {{ data.attend_status?.attend_name }}
          </div>
        </div>

        <div class="detail-inner">
          <div class="label-field label-right">
            <InputLabel value="備考" />
          </div>
          <div class="input-field">
            {{ data?.dp_memo }}
          </div>
        </div>

        <div class="detail-inner">
          <div class="label-field label-right">
            <InputLabel value="現在住所" />
          </div>
          <div class="input-field">
            {{ data?.dp_dakou_address }}
          </div>
        </div>
        <div class="flex justify-end mt-4">
          <Link :href="route('user.attendance.list.index')">
            <Button
              label="一覧"
              icon="pi pi-list"
              class="py-1"
              size="small"
              severity="secondary"
            />
          </Link>
        </div>
      </div>
      <!-- dakoku child table -->
      <div
        v-if="child.length > 0"
        class="p-2 mt-4 overflow-auto bg-white rounded-lg shadow-lg md:p-4 datatable center"
      >
        <DataTable
          :value="child"
          data-key="id"
          selectionMode="single"
          class="p-datatable-sm"
        >
          <Column field="no" header="No" sortable />
          <Column
            field="dp_support_flg"
            header="応援区分"
            sortable
            class="whitespace-nowrap"
          >
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
            class="whitespace-nowrap"
          />
          <Column
            field="supported_company.supported_company_name"
            header="応援に行った先"
            sortable
            class="whitespace-nowrap"
          />
          <Column
            field="dp_nums_of_people"
            header="応援人数"
            sortable
            class="whitespace-nowrap"
          />
          <Column
            field="occupation.occupation_name"
            header="職種"
            sortable
            class="whitespace-nowrap"
          >
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
          <Column
            field="work_content.work_content_name"
            header="作業内容"
            sortable
            class="whitespace-nowrap"
          />
          <Column
            field="work_location.location_name"
            header="現場"
            sortable
            class="whitespace-nowrap"
          />
          <Column
            field="timezone.detail_times"
            header="時間帯"
            sortable
            class="whitespace-nowrap"
          />
        </DataTable>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
<style lang="scss" scoped>
.detail-inner {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 50px;
  margin-top: 0.5rem;

  @media screen and (max-width: 768px) {
    grid-template-columns: 1fr 1fr;
  }

  .label-field {
    display: flex;
    justify-content: flex-end;
    text-align: right;

    label {
      font-weight: 700;
    }
  }
}
</style>
