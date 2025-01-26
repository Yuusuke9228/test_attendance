<script setup>
import CustomToast from "@/Components/CustomToast.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ref, onMounted, computed } from "vue";
import { useToast } from "primevue/usetoast";
import axios from "axios";
import moment from "moment";
import { driveRideOptions, supportCompanyFlag } from "@/Utils/field";
import { getAddress } from "@/Utils/action";
import { dpType } from "@/Utils/field";

const props = defineProps({
  info: Object,
  attend_type: Object,
  support_company: Object,
  supported_company: Object,
  attend_status: Object,
  occupation: Object,
  work_contents: Object,
  work_locations: Object,
  users: Object,
  timezones: Object,
  close_status: Number,
  close_month: String,
});

const toast = useToast();
const workContents = ref([]);
const minDate = computed(() => {
  // 月の締め処理を作って欲しい
  if (props.close_status) {
    return moment(props.close_month).add(1, "month").format("yyyy/MM/DD");
  }
});

const numsOfPeopleList = computed(() => {
  return Array.from({ length: 20 }, (_, index) => (index + 1) * 0.5);
});
const form = useForm({
  id: props.info.id,
  targetDate: moment(props.info.target_date).format("yyyy/MM/DD"),
  user: props.users.filter((filter) => filter.id == props.info.dp_user)[0],
  attendType: props.info.dp_status,
  dpType: props.info.dp_type,
  syukkin: props.info.dp_syukkin_time,
  taikin: props.info.dp_taikin_time,
  driveRide: props.info.dp_ride_flg,
  otherFlag: props.info.dp_other_flg,
  memo: props.info.dp_memo,
  address: props.info.dp_dakou_address,
  children: [
    {
      id: null,
      supportFlag: null,
      supportCompany: null,
      supportedCompany: null,
      peopleNums: null,
      occupation: null,
      uniqueCounter: null,
      workContent: null,
      location: null,
      timezone: null,
    },
  ],
  redirectOption: null,
});

const inputAddress = async (e) => {
  let val = e.target.value;
  let res = await getAddress(val);
  let addr = "";
  if (res) {
    addr += res.prefecture ?? "";
    addr += res.area ?? "";
    addr += res.address ?? "";
    form.address = addr ?? "";
  }
};
const chooseOccupation = (index, occpid) => {
  if (occpid) {
    workContents.value[index] = props.work_contents.filter(
      (filter) => filter.work_content_occp_id == occpid
    );
    let occp_name = props.occupation.filter((f) => f.id == occpid)[0]?.occupation_name;
    if (occp_name != "ユニック") {
      form.children[index]["uniqueCounter"] = null;
    }
  } else {
    workContents.value[index] = [];
    form.children[index]["workContent"] = null;
    form.children[index]["uniqueCounter"] = null;
  }
};

const addChild = () => {
  form.children.push({
    id: null,
    supportFlag: null,
    supportCompany: null,
    supportedCompany: null,
    peopleNums: null,
    occupation: null,
    uniqueCounter: null,
    workContent: null,
    location: null,
    timezone: null,
  });
};
const removeChild = (index) => {
  if (form.children.length > 0) {
    form.children.splice(index, 1);
  }
};
onMounted(() => {
  if (props.info.dakoku_children) {
    form.children = props.info.dakoku_children.map((item, key) => {
      workContents.value[key] = props.work_contents.filter(
        (filter) => filter.work_content_occp_id == item?.dp_occupation_id
      );
      // if (item?.dp_occupation_id) {
      return {
        id: item?.id,
        supportFlag: item?.dp_support_flg ?? 0,
        supportCompany: item.support_company?.id ?? null,
        supportedCompany: item.supported_company?.id ?? null,
        peopleNums: item.dp_nums_of_people ?? null,
        occupation: item?.dp_occupation_id ?? null,
        uniqueCounter: item?.dp_unique_counter ?? null,
        workContent: item?.dp_work_contens_id ?? null,
        location: item?.dp_work_location_id ?? null,
        timezone: item.timezone?.id ?? null,
      };
      // }
    });
  }
});

const toastBack = ref();
const submit = (e) => {
  form.redirectOption = e;

  form.put(route("admin.master.attendance.update"), {
    onSuccess: () => {
      toastBack.value = "bg-green-500/70";
      toast.add({
        severity: "custom",
        summary: "操作成功！",
        life: 2000,
        group: "headless",
      });
    },
    onErrorr: () => {
      toastBack.value = "bg-red-500/70";
      toast.add({
        severity: "custom",
        summary: "操作失敗！",
        life: 2000,
        group: "headless",
      });
    },
  });
};

const uniqueList = Array.from({ length: 10 }, (_, index) => index + 1);

const isUnique = (e) => {
  // params e = occupation->id
  if (props.occupation.filter((f) => f.id == e)[0]?.occupation_name == "ユニック") {
    return true;
  } else {
    return false;
  }
};
</script>
<template>
  <CustomToast group="headless" :bgClass="toastBack" />
  <AdminLayout title="出勤管理（打刻データ）">
    <MainContent>
      <template #header>
        <FontAwesomeIcon icon="fa-solid fa-clock-rotate-left" />
        <h3>
          出勤管理（打刻データ）
          <small>出勤管理情報を管理します。</small>
        </h3>
      </template>
      <MasterEditBox @emitSubmit="submit" link="attendance" :data="info">
        <div class="w-full pb-4 max-w-7xl input-form">
          <div class="mt-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="ユーザー" essential />
            </div>
            <div class="input-field">
              <Dropdown
                v-model="form.user"
                :options="users"
                optionLabel="name"
                class="w-full"
                placeholder="選択してください"
                empty-message="検索結果なし"
              >
                <template #value="slotProps">
                  <div v-if="slotProps.value">
                    <i class="pi pi-user"></i>
                    <span class="pl-2">{{ slotProps.value.name }}</span>
                  </div>
                  <div v-else>
                    <i class="pi pi-user"></i>
                    <span class="pl-2">{{ slotProps.placeholder }}</span>
                  </div>
                </template>
              </Dropdown>
              <InputError :message="form.errors.user" />
            </div>
          </div>

          <div class="mt-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="打刻区分" essential />
            </div>
            <div class="input-field">
              <Dropdown
                v-model="form.attendType"
                :options="attend_type"
                optionLabel="attend_type_name"
                optionValue="id"
                class="w-full"
                placeholder="打刻区分を選択します。"
              >
              </Dropdown>
            </div>
          </div>

          <div class="mt-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="１日•半日" essential />
            </div>
            <div class="input-field">
              <Dropdown
                v-model="form.dpType"
                :options="dpType"
                class="w-full"
                placeholder="１日•半日を選択します。"
              >
              </Dropdown>
              <InputError :message="form.errors.dpType" />
            </div>
          </div>

          <div class="mt-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="日付" essential />
            </div>
            <div class="input-field">
              <div class="w-full p-input-icon-left">
                <i class="pi pi-calendar-plus"></i>
                <VueDatePicker
                  v-model="form.targetDate"
                  locale="ja"
                  modelType="yyyy/MM/dd"
                  format="yyyy/MM/dd"
                  :enable-time-picker="false"
                  :minDate="minDate"
                  :maxDate="new Date()"
                  autoApply
                />
              </div>
            </div>
          </div>
          <div class="mt-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="出勤時間" essential />
            </div>
            <div class="input-field">
              <VueDatePicker
                v-model="form.syukkin"
                locale="ja"
                model-type="HH:mm:ss"
                format="HH:mm"
                time-picker
                autoApply
                placeholder="出勤時間を選択してください。"
              />
            </div>
          </div>

          <div class="mt-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="退勤時刻" essential />
            </div>
            <div class="input-field">
              <VueDatePicker
                v-model="form.taikin"
                locale="ja"
                model-type="HH:mm:ss"
                format="HH:mm"
                time-picker
                autoApply
                placeholder="退勤時間を選択してください。"
              />
            </div>
          </div>

          <div class="mt-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="運転・同乗" />
            </div>
            <div class="input-field">
              <Dropdown
                v-model="form.driveRide"
                :options="driveRideOptions"
                optionLabel="label"
                optionValue="label"
                class="w-full"
                placeholder="選択してください"
                showClear
              />
            </div>
          </div>

          <div class="mt-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="残業、遅刻、早退、有給、研修など" />
            </div>
            <div class="input-field">
              <Dropdown
                v-model="form.otherFlag"
                :options="attend_status"
                optionLabel="attend_name"
                optionValue="id"
                class="w-full"
                placeholder="選択してください"
                show-clear
              />
            </div>
          </div>

          <div class="mt-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="備考" />
            </div>
            <div class="input-field">
              <Textarea v-model="form.memo" rows="3" class="w-full p-inputtext-sm" />
            </div>
          </div>

          <div class="mt-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="現在住所" />
            </div>
            <div class="input-field">
              <div class="w-full p-input-icon-left">
                <i class="pi pi-map-marker"></i>
                <InputText
                  v-model="form.address"
                  class="w-full p-inputtext-sm"
                  placeholder="現在住所は位置情報から自動入力されます。変更する必要がある場合は、郵便コードを入力してください（例：9290122）"
                  @input="inputAddress"
                />
              </div>
            </div>
          </div>
        </div>

        <hr class="mt-4" />
        <!-- 打刻詳細 -->
        <div class="w-full px-6 m-auto mt-8">
          <div class="w-full form-inner">
            <h2 class="text-lg font-500 text-sky-600">詳細登録</h2>
            <div v-for="(item, index) in form.children" :key="index" class="">
              <div class="flex items-start detail-dakoku">
                <div class="grid items-start grid-cols-2 gap-4">
                  <div class="mt-4 detail-inner">
                    <div class="label-field">
                      <InputLabel value="時間帯" />
                    </div>
                    <div class="input-field">
                      <Dropdown
                        v-model="item.timezone"
                        :options="timezones"
                        optionLabel="detail_times"
                        showClear
                        optionValue="id"
                        class="w-full"
                        placeholder="時間帯を選択してください"
                        empty-filter-message="検索結果なし"
                      />
                      <InputError :message="form.errors[`children.${index}.timezone`]" />
                    </div>
                  </div>
                  <div class="occupation flex items-center gap-4">
                    <div class="mt-4 detail-inner w-full">
                      <div class="label-field">
                        <InputLabel value="職種" essential />
                      </div>
                      <div class="input-field">
                        <Dropdown
                          v-model="item.occupation"
                          :options="occupation"
                          optionLabel="occupation_name"
                          optionValue="id"
                          class="w-full"
                          placeholder="選択してください"
                          @change="chooseOccupation(index, item.occupation)"
                          showClear
                        />
                        <InputError
                          :message="form.errors[`children.${index}.occupation`]"
                        />
                      </div>
                    </div>
                    <div v-if="isUnique(item.occupation)" class="mt-4 detail-inner">
                      <div class="label-field">
                        <InputLabel value="回数" essential />
                      </div>
                      <div class="input-field">
                        <Dropdown
                          v-model="item.uniqueCounter"
                          :options="uniqueList"
                          class="w-28"
                          placeholder="選択してください"
                          showClear
                        />
                        <InputError
                          :message="form.errors[`children.${index}.uniqueCounter`]"
                        />
                      </div>
                    </div>
                  </div>
                  <div class="mt-4 detail-inner">
                    <div class="label-field">
                      <InputLabel value="作業内容" />
                    </div>
                    <div class="input-field">
                      <Dropdown
                        v-model="item.workContent"
                        :options="workContents[index]"
                        optionLabel="work_content_name"
                        optionValue="id"
                        class="w-full"
                        showClear
                        placeholder="選択してください"
                        empty-message="職種を選択してください。"
                      />
                    </div>
                  </div>
                  <div class="mt-4 detail-inner">
                    <div class="label-field">
                      <InputLabel value="現場" />
                    </div>
                    <div class="input-field">
                      <Dropdown
                        v-model="item.location"
                        :options="work_locations"
                        optionLabel="location_name"
                        optionValue="id"
                        class="w-full"
                        showClear
                        placeholder="選択してください"
                      />
                      <InputError :message="form.errors[`children.${index}.location`]" />
                    </div>
                  </div>
                </div>
                <!-- 応援区分 -->
                <div class="grid grid-cols-4 gap-4">
                  <div class="mt-4 detail-inner">
                    <div class="label-field label-right">
                      <InputLabel value="応援区分" />
                    </div>
                    <div class="input-field">
                      <Dropdown
                        v-model="item.supportFlag"
                        :options="supportCompanyFlag"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full"
                        placeholder="選択してください"
                      />
                    </div>
                  </div>
                  <div
                    v-if="item.supportFlag !== 0"
                    class="grid grid-cols-2 col-span-2 gap-4"
                  >
                    <div v-if="item.supportFlag == 2" class="mt-4 detail-inner">
                      <div class="label-field label-right">
                        <InputLabel value="応援来てもらった先" />
                      </div>
                      <div class="input-field">
                        <Dropdown
                          v-model="item.supportCompany"
                          :options="support_company"
                          optionLabel="support_company_name"
                          optionValue="id"
                          scroll-height="420px"
                          class="w-full"
                          placeholder="選択してください"
                        />
                      </div>
                    </div>
                    <div v-if="item.supportFlag == 1" class="mt-4 detail-inner">
                      <div class="label-field label-right">
                        <InputLabel value="応援に行った先" />
                      </div>
                      <div class="input-field">
                        <Dropdown
                          v-model="item.supportedCompany"
                          :options="supported_company"
                          optionLabel="supported_company_name"
                          optionValue="id"
                          scroll-height="420px"
                          class="w-full"
                          placeholder="選択してください"
                        />
                      </div>
                    </div>
                    <div class="mt-4 detail-inner">
                      <div class="label-field label-right">
                        <InputLabel value="応援人数入力" />
                      </div>
                      <div class="input-field">
                        <div class="w-full">
                          <Dropdown
                            v-model="item.peopleNums"
                            :options="numsOfPeopleList"
                            class="w-full"
                            scroll-height="420px"
                            placeholder="応援人数入力してください"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-if="form.children.length > 0 && index > 0" class="w-full">
                <div class="flex justify-end">
                  <button type="button" @click="removeChild(index)" class="text-red-500">
                    削除する
                  </button>
                </div>
                <hr class="my-2" />
              </div>
            </div>
            <div class="flex items-center justify-end p-3 mt-3">
              <Button
                label="追加する"
                icon="pi pi-plus"
                size="small"
                class="px-2 py-1"
                severity="danger"
                @click="addChild"
              />
            </div>
          </div>
        </div>
      </MasterEditBox>
    </MainContent>
  </AdminLayout>
</template>
<style lang="scss" scoped>
.detail-dakoku {
  display: grid;
}
</style>
