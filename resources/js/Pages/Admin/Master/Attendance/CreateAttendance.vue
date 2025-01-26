<script setup>
import CustomToast from "@/Components/CustomToast.vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { ref, onMounted, watch, computed } from "vue";
import { useToast } from "primevue/usetoast";
import moment from "moment";
import axios from "axios";
import { getAddress, waitForGeolocationPermission } from "@/Utils/action.js";
import { driveRideOptions, supportCompanyFlag, dpType } from "@/Utils/field";

const props = defineProps({
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

const numsOfPeopleList = computed(() => {
  return Array.from({ length: 20 }, (_, index) => (index + 1) * 0.5);
});

const form = useForm({
  targetDate: moment().format("yyyy/MM/DD"),
  user: null,
  attendType: 1,
  dpType: "1日",
  syukkinTime: null,
  taikinTime: null,
  driveRide: null,
  supportFlag: 0,
  otherFlag: null,
  memo: null,
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
    },
  ],
  redirectOption: null,
});

onMounted(() => {
  waitForGeolocationPermission((res) => {
    form.address = res;
  });
});

const existDateMsg = ref();
const checkDuplicateDate = async () => {
  if (form.user?.id && form.targetDate) {
    axios
      .get(route("admin.master.attendance.check.exist"), {
        params: { id: form.user?.id, date: form.targetDate },
      })
      .then((res) => {
        if (res.data) {
          existDateMsg.value = res.data.message;
        } else {
          existDateMsg.value = null;
        }
      });
  }
};
const chooseDay = async () => {
  await checkDuplicateDate();
};

const choosingUser = async () => {
  if (form.user) {
    let result = await axios.post(
      route("admin.master.attendance.timeset", { id: form.user?.id })
    );
    if (result.data) {
      form.syukkinTime = result.data.break_start_time;
      form.taikinTime = result.data.break_end_time;
    }
  }
  await checkDuplicateDate();
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
  if (form.children.length > 1) {
    form.children.splice(index, 1);
  }
};

const toastBack = ref();
const submit = (redirectOption) => {
  let duplicate = [];
  form.children.forEach((item) => {
    let elKey =
      item.timezone +
      "_" +
      item.occupation +
      "_" +
      item.workContent +
      "_" +
      item.location;
    if (!duplicate.includes(elKey)) {
      duplicate.push(elKey);
    }
  });
  if (form.children.length != duplicate.length) {
    toastBack.value = "bg-red-500/70";
    toast.add({
      severity: "custom",
      summary: "操作失敗！",
      detail: "同じ時間帯、職種、現場名、応援区分、応援会社の重複入力は不可能です。",
      life: 2000,
      group: "headless",
    });
    return;
  }

  if (existDateMsg.value) {
    toastBack.value = "bg-red-500/70";
    toast.add({
      severity: "custom",
      summary: "操作失敗！",
      detail: form.targetDate + "の打刻データが既に登録されています。",
      life: 2000,
      group: "headless",
    });
    return;
  }
  form.redirectOption = redirectOption;
  form.post(route("admin.master.attendance.store"), {
    onFinsh: () => {
      form.reset();
    },
    onSuccess: () => {
      toastBack.value = "bg-green-500/70";
      form.reset();
      toast.add({
        severity: "custom",
        summary: "操作成功！",
        life: 2000,
        group: "headless",
      });
    },
    onErrorr: (e) => {
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

const minDate = computed(() => {
  // 月の締め処理を作って欲しい
  if (props.close_status) {
    return moment(props.close_month).add(1, "month").format("yyyy/MM/DD");
  }
});

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
  <Toast />
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
      <MasterCreateForm @emitSubmit="submit" link="attendance">
        <div class="w-full m-auto max-w-7xl input-form">
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
                @update:model-value="choosingUser"
                empty-message="検索結果なし"
                placeholder="選択してください"
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

          <div class="mt-4 form-inner center">
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
                :minDate="minDate"
                :maxDate="new Date()"
                auto-apply
                @update:model-value="chooseDay"
              />
              <InputError :message="form.errors.targetDate" />
              <InputError :message="existDateMsg" />
            </div>
          </div>

          <div class="mt-4 form-inner center">
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
                  <i class="pl-3 mt-1 pi pi-clock"></i>
                </template>
              </VueDatePicker>
              <InputError :message="form.errors.syukkinTime" />
            </div>
          </div>

          <div class="mt-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="退勤時刻" essential />
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
                  <i class="pl-3 mt-1 pi pi-clock"></i>
                </template>
              </VueDatePicker>
              <InputError :message="form.errors.taikinTime" />
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

          <div class="mt-4 form-inner">
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
                  disabled
                  placeholder="現在住所"
                />
              </div>
            </div>
          </div>

          <hr class="my-4" />

          <div class="child-dakoku-field">
            <h2 class="text-lg font-500 text-sky-600">詳細登録</h2>
            <div v-for="(item, index) in form.children" :key="index" class="">
              <div class="flex items-start detail-dakoku">
                <div class="grid items-start grid-cols-2 gap-4">
                  <div class="mt-4 detail-inner">
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
                          placeholder="回数を選択"
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
                        @update:model-value="handleSupportFlg($event, index)"
                      />
                    </div>
                  </div>
                  <div
                    v-if="item.supportFlag !== 0"
                    class="grid grid-cols-3 col-span-3 gap-4 items-end"
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
                    <div
                      v-if="item.supportedCompany || item.supportCompany"
                      class="mt-4 detail-inner"
                    >
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
              <div class="w-full">
                <div
                  v-if="form.children.length > 1 && index > 0"
                  class="flex justify-end"
                >
                  <button type="button" @click="removeChild(index)" class="text-red-500">
                    削除する
                  </button>
                </div>
                <hr class="my-2 clear-both" />
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
            <hr class="my-4" />
          </div>
        </div>
      </MasterCreateForm>
    </MainContent>
  </AdminLayout>
</template>
<style lang="scss" scoped>
.detail-inner {
}

.detail-dakoku {
  display: grid;
}

.recommend__message {
  .detail-content {
    p {
      span:first-child {
        display: inline-block;
        width: 180px;
      }
    }
  }
}
</style>
