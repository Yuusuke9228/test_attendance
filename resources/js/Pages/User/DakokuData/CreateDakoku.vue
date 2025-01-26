<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { ref, onMounted, watch, computed } from "vue";
import { waitForGeolocationPermission } from "@/Utils/action.js";
import { driveRideOptions, supportCompanyFlag } from "@/Utils/field";
import { dpType } from "@/Utils/field";
import moment from "moment";
import Swal from "sweetalert2";

const props = defineProps({
  dakoku_data: Object,
  choosingDate: String,
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
  close_status: Number,
  close_date: String,
  min_date: String,
  holidays: Object,
});

const numsOfPeopleList = computed(() => {
  return Array.from({ length: 20 }, (_, index) => (index + 1) * 0.5);
});

const urlParams = new URLSearchParams(window.location.search);
const editParams = urlParams.get("edit");
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
          return {
            title: workCode + organization,
            startTime: startTime,
            endTime: endTime,
          };
        }
      }
    }
  }
});

const form = useForm({
  id: props.dakoku_data?.id ?? null,
  dpUser: props.dakoku_data?.dp_user ?? null,
  dpType: props.dakoku_data?.dp_type,
  targetDate: props.dakoku_data?.target_date
    ? moment(props.dakoku_data?.target_date).format("yyyy/MM/DD")
    : props.choosingDate
    ? moment(props.choosingDate).format("yyyy/MM/DD")
    : moment().format("yyyy/MM/DD"),
  attendTime: props.dakoku_data?.dp_syukkin_time
    ? props.dakoku_data?.dp_syukkin_time
    : moment().format("HH:mm:ss"),
  syukkinTime: props.dakoku_data?.dp_syukkin_time
    ? props.dakoku_data?.dp_syukkin_time
    : userOrganization.value?.startTime + ":00",
  taikinTime: props.dakoku_data?.dp_taikin_time
    ? props.dakoku_data?.dp_taikin_time
    : userOrganization.value?.endTime + ":00",
  attendType: props.dakoku_data?.dp_status ?? 2,
  driveRide: props.dakoku_data?.dp_ride_flg ?? null,
  otherFlag: props.dakoku_data?.dp_other_flg ?? null,
  memo: props.dakoku_data?.dp_memo ?? null,
  address: props.dakoku_data?.dp_dakou_address ?? null,
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
onMounted(() => {
  waitForGeolocationPermission((res) => {
    form.address = res;
  });
  if (props.dakoku_data?.dakoku_children.length > 0) {
    form.children = props.dakoku_data?.dakoku_children.map((item, key) => {
      workContents.value[key] = props.work_contents.filter(
        (filter) => filter.work_content_occp_id == item?.dp_occupation_id
      );
      return {
        id: item?.id ?? null,
        supportFlag: item?.dp_support_flg ?? null,
        supportCompany: item.support_company?.id ?? null,
        supportedCompany: item.supported_company?.id ?? null,
        peopleNums: item.dp_nums_of_people ?? null,
        occupation: item?.dp_occupation_id ?? null,
        uniqueCounter: item?.dp_unique_counter ?? null,
        workContent: item?.dp_work_contens_id ?? null,
        location: item?.dp_work_location_id ?? null,
        timezone: item?.dp_timezone_id ?? null,
      };
    });
  }
});

const chooseOccupation = (index) => {
  if (form.children[index]["occupation"]) {
    workContents.value[index] = props.work_contents.filter(
      (filter) => filter.work_content_occp_id == form.children[index]["occupation"]
    );
    console.log(workContents.value);
  } else {
    workContents.value[index] = [];
    form.children[index]["workContent"] = null;
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
  });
};
const removeChild = (index) => {
  if (form.children.length > 0) {
    form.children.splice(index, 1);
  }
};

const chooseDay = () => {
  // form.get(route("user.attendance.list.create", { date: form.targetDate }));
};

const submit = async () => {
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
      icon: "info",
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
  // taikin time validation
  if (form.attendType == 2 && form.taikinTime == null) {
    Swal.fire({
      toast: true,
      html: `<span style='color:white;'>勤務時間を選択してください。</span>`,
      icon: "info",
      showConfirmButton: false,
      timer: 3000,
      background: "#002030",
      position: "bottom-right",
    });
    return;
  }
  form.post(route("user.attendance.list.store"), {
    onSuccess: () => {
      form.reset();
      Swal.fire({
        toast: true,
        html: `<span style='color:white;'>登録されました。</span>`,
        icon: "success",
        showConfirmButton: false,
        timer: 3000,
        background: "#0284c7",
        position: "bottom-right",
      });
    },
    onError: (e) => {
      let msg = "必須項目を入力してください。";
      if (e.error) {
        msg = e.error;
      }
      Swal.fire({
        toast: true,
        html: `<span style='color:white;'>操作失敗！<br />${msg}</span>`,
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
            class="relative flex items-center justify-center gap-4 mt-4 text-sm times-bar md:text-md"
          >
            <div class="p-1 font-bold text-white rounded-md bg-sky-500">
              出勤時間：{{ userOrganization.startTime }}
            </div>
            <div class="p-1 font-bold text-white bg-red-500 rounded-md">
              退勤時間：{{ userOrganization.endTime }}
            </div>
          </div>
        </div>
        <form @submit.prevent="submit" class="mt-6">
          <div v-if="dakoku_data" class="border-b existing-data">
            <p v-if="!editParams" class="text-red-600">
              {{ moment(dakoku_data?.target_date).format("yyyy年M月D日") }}
              の打刻データはすでに登録されています。
            </p>
            <small class="text-gray-500">登録する場合は、変更内容で更新されます。</small>
          </div>
          <div class="mt-4 form-inner">
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
              <InputError :message="form.errors.attendType" />
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
                auto-apply
                :max-date="new Date()"
                :min-date="min_date"
                :disabled="editParams == 'true'"
                position="left"
                @update:model-value="chooseDay"
                :markers="holidays"
              />
              <small class="text-gray-600">
                <FontAwesomeIcon icon="fa-solid fa-circle-info" />
                5日間の打刻データのみ編集できます。
              </small>
              <InputError :message="form.errors.targetDate" />
            </div>
          </div>

          <div class="mt-4 form-inner">
            <div class="label-field label-right">
              <InputLabel value="出勤時間" essential />
            </div>
            <div class="input-field">
              <VueDatePicker
                v-model="form.syukkinTime"
                locale="ja"
                model-type="HH:mm:ss"
                format="HH:mm"
                time-picker
                autoApply
                placeholder="出勤時間を選択してください。"
              >
                <template #input-icon>
                  <i class="mt-1 ml-2 pi pi-clock"></i>
                </template>
              </VueDatePicker>
              <InputError :message="form.errors.syukkinTime" />
            </div>
          </div>
          <div v-if="form.attendType == 2" class="mt-4 form-inner">
            <div class="label-field label-right">
              <InputLabel value="退勤時刻" />
            </div>
            <div class="input-field">
              <VueDatePicker
                v-model="form.taikinTime"
                locale="ja"
                model-type="HH:mm:ss"
                format="HH:mm"
                time-picker
                autoApply
                placeholder="退勤時間を選択してください。"
              >
                <template #input-icon>
                  <i class="mt-1 ml-2 pi pi-clock"></i>
                </template>
              </VueDatePicker>
              <InputError :message="form.errors.taikinTime" />
            </div>
          </div>

          <div class="other-info">
            <div v-if="form.attendType !== 3" class="mt-4 form-inner">
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
          <div v-if="form.attendType !== 3" class="child-dakoku-fields">
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
                    <InputLabel value="時間帯" essential />
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
                      <InputLabel value="職種" essential />
                    </div>
                    <div class="input-field">
                      <Dropdown
                        v-model="item.occupation"
                        :options="occupation"
                        optionLabel="occupation_name"
                        optionValue="id"
                        class="w-full"
                        placeholder="職種を選択してください"
                        @change="chooseOccupation(index)"
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
                    <InputError :message="form.errors[`children.${index}.location`]" />
                  </div>
                </div>
                <!-- 応援区分 -->
                <div>
                  <div class="mt-4 form-inner">
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
              <div v-if="form.children.length > 0 && index > 0" class="w-full">
                <div class="flex justify-center mt-1">
                  <Button
                    icon="pi pi-times"
                    @click="removeChild(index)"
                    rounded
                    severity="secondary"
                  ></Button>
                </div>
              </div>
            </div>
            <div class="flex items-center justify-center">
              <Button icon="pi pi-plus" rounded severity="success" @click="addChild" />
            </div>
            <hr class="my-4" />
          </div>
          <div class="flex flex-col items-center gap-2 attend-action__btn">
            <Button
              type="submit"
              label="登録する"
              icon="pi pi-save"
              class="w-full"
              severity="success"
            />
          </div>
        </form>
      </div>
    </div>
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
