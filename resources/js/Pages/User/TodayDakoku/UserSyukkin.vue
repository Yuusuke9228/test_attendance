<script setup>
import CustomToast from "@/Components/CustomToast.vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { ref, onMounted, watch, computed } from "vue";
import { getAddress, waitForGeolocationPermission } from "@/Utils/action.js";
import { driveRideOptions, supportCompanyFlag } from "@/Utils/field";
import { dpType } from "@/Utils/field";
import moment from "moment";
import { Inertia } from "@inertiajs/inertia";
import Swal from "sweetalert2";

const props = defineProps({
  dakoku_data: Object,
  userOrganization: Object,
  attend_type: Object,
  support_company: Object,
  supported_company: Object,
  attend_status: Object,
  occupation: Object,
  work_contents: Object,
  work_locations: Object,
  timezones: Object,
  users: Object,
  today_attend_status: Boolean,
});

const numsOfPeopleList = computed(() => {
  return Array.from({ length: 20 }, (_, index) => (index + 1) * 0.5);
});

const userOrganization = computed(() => {
  if (props.userOrganization?.user_data) {
    let userData = props.userOrganization.user_data;
    if (userData) {
      if (userData?.break_times) {
        let breakTimes = userData.break_times;
        if (breakTimes?.organization) {
          let workCode = breakTimes?.break_work_pattern_cd;
          let organization = breakTimes?.organization?.organization_name;
          let startTime = breakTimes?.break_start_time
            ? breakTimes?.break_start_time.slice(0, -3)
            : "";
          let endTime = breakTimes?.break_end_time
            ? breakTimes?.break_end_time.slice(0, -3)
            : "";
          let restStartTime1 = breakTimes?.break_start_time1
            ? breakTimes?.break_start_time1.slice(0, -3)
            : "";
          let restEndTime1 = breakTimes?.break_end_time1
            ? breakTimes?.break_end_time1.slice(0, -3)
            : "";
          let restStartTime2 = breakTimes?.break_start_time2
            ? breakTimes?.break_start_time2.slice(0, -3)
            : "";
          let restEndTime2 = breakTimes?.break_end_time2
            ? breakTimes?.break_end_time2.slice(0, -3)
            : "";
          let restStartTime3 = breakTimes?.break_start_time3
            ? breakTimes?.break_start_time3.slice(0, -3)
            : "";
          let restEndTime3 = breakTimes?.break_end_time3
            ? breakTimes?.break_end_time3.slice(0, -3)
            : "";
          return {
            title: workCode + organization,
            startTime: startTime,
            endTime: endTime,
            restStartTime1: restStartTime1,
            restEndTime1: restEndTime1,
            restStartTime2: restStartTime2,
            restEndTime2: restEndTime2,
            restStartTime3: restStartTime3,
            restEndTime3: restEndTime3,
          };
        }
      }
    }
  }
});

const form = useForm({
  id: props.dakoku_data?.id,
  dpUser: props.dakoku_data?.dp_user,
  targetDate: moment().format("yyyy/MM/DD"),
  dpType: props.dakoku_data?.dp_type ?? "1日",
  syukkinTime: props.dakoku_data?.dp_syukkin_time
    ? props.dakoku_data?.dp_syukkin_time
    : moment().format("HH:mm:ss"),
  taikinTime: props.dakoku_data?.dp_taikin_time
    ? props.dakoku_data?.dp_taikin_time
    : moment().format("HH:mm:ss"),
  driveRide: props.dakoku_data?.dp_ride_flg,
  otherFlag: props.dakoku_data?.dp_other_flg,
  memo: props.dakoku_data?.dp_memo,
  address: null,
  children: [
    {
      supportFlag: 0,
      supportCompany: null,
      supportedCompany: null,
      peopleNums: null,
      occupation: null,
      uniqueCounter: null,
      workContent: null,
      location: null,
      timezone: null,
      workers: null,
      workerMaster: null,
    },
  ],
});

const workContents = ref([]);
const progressValue = ref(0);
const breakPosition1 = ref(0);
const breakPosition2 = ref(0);
const breakPosition3 = ref(0);

if (props.dakoku_data?.dakoku_children) {
  let children = props.dakoku_data.dakoku_children.map((item, key) => {
    workContents.value[key] = props.work_contents.filter(
      (filter) => filter.work_content_occp_id == item?.dp_occupation_id
    );
    return {
      id: item.id,
      supportFlag: item.dp_support_flg ?? 0,
      supportCompany: item.dp_support_company_id ?? null,
      supportedCompany: item.dp_supported_company_id ?? null,
      peopleNums: item.dp_nums_of_people ?? null,
      occupation: item.dp_occupation_id ?? null,
      uniqueCounter: item.dp_unique_counter ?? null,
      workContent: item.dp_work_contens_id ?? null,
      location: item.dp_work_location_id ?? null,
      timezone: item.dp_timezone_id ?? null,
    };
  });
  form.children = children;
}

onMounted(() => {
  setInterval(updateProgress, 1000);
  waitForGeolocationPermission((res) => {
    form.address = res;
  });
});

const updateProgress = () => {
  let startTime = userOrganization.value?.startTime
    ? moment(userOrganization.value?.startTime, "HH:mm")
    : null;
  let endTime = userOrganization.value?.endTime
    ? moment(userOrganization.value?.endTime, "HH:mm")
    : null;
  let restTime1 = userOrganization.value?.restStartTime1;
  let restTime2 = userOrganization.value?.restStartTime2;
  let restTime3 = userOrganization.value?.restStartTime3;
  if (startTime && endTime) {
    let timeDiff = endTime.diff(startTime, "minutes");
    let currentTimeDiff = moment().diff(startTime, "minutes");
    let percentage = (currentTimeDiff * 100) / timeDiff;
    if (percentage < 0) {
      percentage = 0;
    } else if (percentage >= 100) {
      percentage = 100;
    }
    progressValue.value = percentage;
    // adjusting the rest time position
    if (restTime1) {
      let diff1 = moment(restTime1, "HH:mm").diff(moment(startTime, "HH:mm"), "minutes");
      breakPosition1.value = (diff1 * 100) / timeDiff;
    }
    if (restTime2) {
      let diff2 = moment(restTime2, "HH:mm").diff(moment(startTime, "HH:mm"), "minutes");
      breakPosition2.value = (diff2 * 100) / timeDiff;
    }
    if (restTime3) {
      let diff3 = moment(restTime3, "HH:mm").diff(moment(startTime, "HH:mm"), "minutes");
      breakPosition3.value = (diff3 * 100) / timeDiff;
    }
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
    supportFlag: 0,
    supportCompany: null,
    supportedCompany: null,
    peopleNums: null,
    occupation: null,
    uniqueCounter: null,
    workContent: null,
    location: null,
    timezone: null,
    workers: null,
    workerMaster: null,
  });
};
const removeChild = (index) => {
  if (form.children.length > 1) {
    form.children.splice(index, 1);
  }
};

const toastBack = ref();
const handleSubmit = async (attendType) => {
  let duplicate = [];
  form.children.forEach((item) => {
    let elKey =
      item.timezone +
      "_" +
      item.occupation +
      "_" +
      item.location +
      "_" +
      item.workContent;
    if (!duplicate.includes(elKey)) {
      duplicate.push(elKey);
    }
  });
  if (form.children.length != duplicate.length) {
    Swal.fire({
      toast: true,
      html: `<span style='color:white;'>操作失敗！<br />同じ時間帯、職種、現場名、応援区分、応援会社の重複入力は不可能です。</span>`,
      icon: "error",
      showConfirmButton: false,
      timer: 3000,
      background: "#002030",
      position: "bottom-right",
    });
    return;
  }

  if (form.children.some((item) => item.occupation == null)) {
    const { value: accept } = await Swal.fire({
      html: `<span style='color:white;'>職種を選択してください。`,
      icon: "info",
      showCancelButton: true,
      cancelButtonText: "職種なしで登録",
      confirmButtonText: "職種を選択",
      background: "#002030",
    });
    if (accept) {
      return;
    }
  }

  let storeRoute =
    attendType == 1
      ? route("user.attendance.today.syukkin.store")
      : route("user.attendance.today.taikin.store");
  form.post(storeRoute, {
    onSuccess: () => {
      toastBack.value = "bg-green-500/70";
      form.reset();
      Swal.fire({
        toast: true,
        html: `<span style='color:white;'>操作成功！</span>`,
        icon: "success",
        showConfirmButton: false,
        timer: 3000,
        background: "#0284c7",
        position: "bottom-right",
      });
      Inertia.reload();
    },
    onError: () => {
      taikinConfirmModal.value = false;
      Swal.fire({
        toast: true,
        html: `<span style='color:white;'>操作失敗！<br />必須項目を入力してください。</span>`,
        icon: "error",
        showConfirmButton: false,
        timer: 3000,
        background: "#002030",
        position: "bottom-right",
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

const taikinConfirmModal = ref(false);

const handleSupportFlg = (e, index) => {
  if (e == 0) {
    form.children[index].supportCompany = null;
    form.children[index].supportedCompany = null;
    form.children[index].peopleNums = null;
  } else if (e == 1) {
    form.children[index].supportCompany = null;
  } else {
    form.children[index].supportedCompany = null;
  }
};
</script>
<template>
  <AuthenticatedLayout title="利用者打刻">
    <div class="w-full max-w-md p-0 m-auto md:p-6 md:max-w-7xl user-page__dashboard">
      <div class="p-4 bg-white rounded-lg shadow-lg md:p-12">
        <div class="organization-information">
          <div class="text-center">
            <h1 class="text-xl font-bold">休憩時間・勤務形態</h1>
            <p class="mt-2 text-lg">組織：{{ userOrganization?.title ?? "未登録" }}</p>
          </div>
          <div
            v-if="userOrganization"
            class="relative mt-12 text-xs times-bar md:text-md"
          >
            <div class="absolute left-0 -top-8 -md:top-10 syukin-tab">
              <div
                class="p-1 font-bold text-white rounded-md bg-sky-500 status-tab syukin"
              >
                出勤{{ userOrganization.startTime }}
              </div>
            </div>
            <div class="absolute right-0 -top-8 -md:top-10 taikin-tab">
              <div
                class="p-1 font-bold text-white bg-red-500 rounded-md status-tab taikin"
              >
                退勤{{ userOrganization.endTime }}
              </div>
            </div>
            <div class="">
              <ProgressBar
                :value="progressValue"
                :show-value="false"
                style="height: 8px"
              />
            </div>
            <!-- rest 1 -->
            <div
              class="absolute w-20 -ml-12 text-[10px] -bottom-8 -md:top-10 taikin-tab"
              :style="{ left: `${breakPosition1}%` }"
            >
              <div
                class="p-1 font-bold text-center text-white bg-teal-600 rounded-md rest-status-tab"
              >
                {{ userOrganization.restStartTime1 }}~
                {{ userOrganization.restEndTime1 }}
              </div>
            </div>
            <!-- rest 2 -->
            <div
              class="absolute w-20 -ml-12 text-[10px] -bottom-8 -md:top-10 taikin-tab"
              :style="{ left: `${breakPosition2}%` }"
            >
              <div
                class="p-1 font-bold text-center text-white bg-teal-600 rounded-md rest-status-tab"
              >
                {{ userOrganization.restStartTime2 }}~
                {{ userOrganization.restEndTime2 }}
              </div>
            </div>
            <!-- rest 3 -->
            <div
              class="absolute w-20 -ml-12 text-[10px] -bottom-8 -md:top-10 taikin-tab"
              :style="{ left: `${breakPosition3}%` }"
            >
              <div
                class="p-1 font-bold text-center text-white bg-teal-600 rounded-md rest-status-tab"
              >
                {{ userOrganization.restStartTime3 }}~
                {{ userOrganization.restEndTime3 }}
              </div>
            </div>
          </div>
        </div>
        <form class="mt-12">
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

          <div class="mt-4 form-inner">
            <div class="label-field label-right">
              <InputLabel value="日付" essential />
            </div>
            <div class="input-field">
              <VueDatePicker
                v-model="form.targetDate"
                locale="ja"
                format="yyyy/MM/dd"
                modelType="yyyy/MM/dd"
                :enable-time-picker="false"
                disabled
                auto-apply
              />
              <InputError :message="form.errors.targetDate" />
            </div>
          </div>

          <div class="mt-4 form-inner">
            <div class="label-field label-right">
              <InputLabel v-if="today_attend_status" value="退勤時刻" essential />
              <InputLabel v-else value="出勤時間" essential />
            </div>
            <div class="input-field">
              <VueDatePicker
                v-if="!today_attend_status"
                v-model="form.syukkinTime"
                locale="ja"
                model-type="HH:mm:ss"
                format="HH:mm"
                time-picker
                autoApply
                position="left"
                placeholder="出勤時間を選択してください。"
              >
                <template #input-icon>
                  <i class="mt-1 ml-2 pi pi-clock"></i>
                </template>
              </VueDatePicker>
              <VueDatePicker
                v-else
                v-model="form.taikinTime"
                locale="ja"
                model-type="HH:mm:ss"
                format="HH:mm"
                time-picker
                position="left"
                autoApply
                placeholder="退勤時刻を選択してください。"
              >
                <template #input-icon>
                  <i class="mt-1 ml-2 pi pi-clock"></i>
                </template>
              </VueDatePicker>
              <InputError :message="form.errors.syukkinTime" />
              <InputError :message="form.errors.taikinTime" />
            </div>
          </div>

          <div class="other-info">
            <div class="mt-4 form-inner">
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

            <div class="mt-4 form-inner">
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

            <div class="mt-4 form-inner">
              <div class="label-field label-right">
                <InputLabel value="備考" />
              </div>
              <div class="input-field">
                <Textarea v-model="form.memo" rows="3" class="w-full p-inputtext-sm" />
              </div>
            </div>

            <div class="hidden mt-4 form-inner">
              <div class="label-field label-right">
                <InputLabel value="現在住所" />
              </div>
              <div class="input-field">
                <div class="w-full p-input-icon-left">
                  <i class="pi pi-map-marker"></i>
                  <InputText
                    v-model="form.address"
                    class="w-full p-inputtext-sm"
                    placeholder="現在住所は位置情報から自動入力されます。"
                    disabled
                  />
                </div>
                <p class="hidden text-xs text-gray-500">
                  <FontAwesomeIcon icon="fa-solid fa-circle-info" />
                  変更する必要がある場合は、郵便コードを入力してください（例：9290122）
                </p>
              </div>
            </div>
          </div>

          <hr class="my-4" />

          <!-- add detail dakoku -->
          <div class="child-dakoku-fields">
            <h2 class="text-lg font-500 text-sky-600">詳細登録</h2>
            <div
              v-for="(item, index) in form.children"
              :key="index"
              class="p-2 mb-2 border rounded-lg border-red-500/30"
            >
              <div class="">
                <!-- 時間帯 -->
                <div class="mt-4 form-inner">
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
                <!-- 職種 -->

                <div class="flex items-center gap-4">
                  <div class="mt-4 form-inner w-full">
                    <div class="label-field">
                      <InputLabel value="職種" />
                    </div>
                    <div class="input-field">
                      <Dropdown
                        v-model="item.occupation"
                        :options="occupation"
                        optionLabel="occupation_name"
                        optionValue="id"
                        class="w-full"
                        placeholder="職種を選択してください"
                        @change="chooseOccupation(index, item.occupation)"
                        showClear
                      />
                      <InputError
                        :message="form.errors[`children.${index}.occupation`]"
                      />
                    </div>
                  </div>
                  <div v-if="isUnique(item.occupation)" class="mt-4 form-inner">
                    <div class="label-field">
                      <InputLabel value="回数" essential />
                    </div>
                    <div class="input-field">
                      <Dropdown
                        v-model="item.uniqueCounter"
                        :options="uniqueList"
                        class="w-28"
                        placeholder="回数を選択"
                        showClear
                      />
                      <InputError
                        :message="form.errors[`children.${index}.uniqueCounter`]"
                      />
                    </div>
                  </div>
                </div>
                <!-- 作業内容 -->
                <div class="mt-4 form-inner">
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
                      placeholder="作業内容を選択してください"
                      empty-message="職種を選択してください。"
                    />
                  </div>
                </div>
                <!--現場  -->
                <div class="mt-4 form-inner">
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
                      placeholder="現場を選択してください"
                    />
                  </div>
                </div>
                <!-- 現場人員選択 -->
                <div class="mt-4 form-inner hidden">
                  <div class="label-field">
                    <InputLabel value="現場人員選択" />
                  </div>
                  <div class="input-field">
                    <MultiSelect
                      v-model="item.workers"
                      :options="users"
                      optionLabel="name"
                      filter
                      display="chip"
                      showClear
                      optionValue="id"
                      class="w-full"
                      placeholder="現場人員を選択してください"
                      empty-filter-message="検索結果なし"
                    />
                  </div>
                </div>
                <!-- 作業責任者 -->
                <div class="mt-4 form-inner hidden">
                  <div class="label-field">
                    <InputLabel value="作業責任者" />
                  </div>
                  <div class="input-field">
                    <Dropdown
                      v-model="item.workerMaster"
                      :options="users"
                      optionLabel="name"
                      filter
                      showClear
                      optionValue="id"
                      class="w-full"
                      placeholder="作業責任者を選択してください"
                      empty-message="まず、現場人員を選択してください。"
                      empty-filter-message="検索結果なし"
                    />
                  </div>
                </div>

                <!-- 応援区分 -->
                <div>
                  <div class="mt-4 form-inner">
                    <!-- {{ item.supportedCompany }} -->
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
                        show-clear
                        placeholder="選択してください"
                        @update:model-value="handleSupportFlg($event, index)"
                      />
                    </div>
                  </div>
                  <div v-if="item.supportFlag !== 0">
                    <div v-if="item.supportFlag == 2" class="mt-4 form-inner">
                      <div class="label-field label-right">
                        <InputLabel value="応援来てもらった先" />
                      </div>
                      <div class="input-field">
                        <Dropdown
                          v-model="item.supportCompany"
                          :options="support_company"
                          optionLabel="support_company_name"
                          optionValue="id"
                          class="w-full"
                          placeholder="選択してください"
                        />
                        <InputError
                          :message="form.errors[`children.${index}.supportCompany`]"
                        />
                      </div>
                    </div>
                    <div v-if="item.supportFlag == 1" class="mt-4 form-inner">
                      <div class="label-field label-right">
                        <InputLabel value="応援に行った先" />
                      </div>
                      <div class="input-field">
                        <Dropdown
                          v-model="item.supportedCompany"
                          :options="supported_company"
                          optionLabel="supported_company_name"
                          optionValue="id"
                          class="w-full"
                          placeholder="選択してください"
                        />
                        <InputError
                          :message="form.errors[`children.${index}.supportedCompany`]"
                        />
                      </div>
                    </div>
                    <div class="mt-4 form-inner">
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

              <div v-if="form.children.length > 1 && index > 0" class="w-full">
                <div class="flex justify-center mt-1">
                  <Button
                    icon="pi pi-times"
                    @click="removeChild(index)"
                    rounded
                    severity="secondary"
                    class=""
                  ></Button>
                </div>
              </div>
            </div>
            <div class="flex items-center justify-center">
              <Button
                icon="pi pi-plus"
                rounded
                class="p-2"
                severity="success"
                @click="addChild"
              />
            </div>
            <hr class="my-4" />
          </div>
          <div class="flex flex-col items-center gap-2 attend-action__btn">
            <div class="w-full">
              <Button
                :icon="today_attend_status ? 'pi pi-file-edit' : 'pi pi-sign-in'"
                :label="today_attend_status ? '編集' : '出勤'"
                class="w-full"
                :severity="today_attend_status ? '' : 'success'"
                @click="handleSubmit(1)"
              />
            </div>
            <div v-if="today_attend_status" class="w-full">
              <Button
                icon="pi pi-sign-out"
                label="退勤"
                class="w-full"
                severity="danger"
                @click="taikinConfirmModal = true"
              />
            </div>
          </div>
        </form>
      </div>
    </div>
    <Dialog
      v-model:visible="taikinConfirmModal"
      header="退勤しますか？"
      modal
      dismissable-mask
      :draggable="false"
      class="w-full max-w-md"
    >
      <div class="w-full">
        <p class="text-center">一旦退勤登録をする場合、本日業務が終了します。</p>
        <p class="text-center">本当に今日の業務を終了しますか？</p>
        <div class="mt-8 flex items-center justify-center gap-9">
          <Button
            label="いいえ"
            icon="pi pi-times"
            size="small"
            severity="secondary"
            class="w-32"
            @click="taikinConfirmModal = false"
          />
          <Button
            label="はい"
            icon="pi pi-check"
            size="small"
            class="w-32"
            severity="success"
            @click="handleSubmit(2)"
          />
        </div>
      </div>
    </Dialog>
  </AuthenticatedLayout>
</template>
<style lang="scss" scoped>
.status-tab {
  position: relative;
  display: flex;
  justify-content: center;
  z-index: 0;

  &::before {
    content: "";
    position: absolute;
    width: 10px;
    height: 10px;
    bottom: -9px;
    clip-path: polygon(0 0, 100% 0, 50% 100%);
  }

  &.syukin {
    &::before {
      background-color: #06b6d4;
    }
  }

  &.taikin {
    &::before {
      background-color: #ef4444;
    }
  }
}

.rest-status-tab {
  position: relative;
  display: flex;
  justify-content: center;
  z-index: 0;

  &::before {
    content: "";
    position: absolute;
    width: 10px;
    height: 10px;
    top: -9px;
    background-color: #0d9488;
    clip-path: polygon(50% 0, 100% 100%, 0 100%);
  }
}
</style>
