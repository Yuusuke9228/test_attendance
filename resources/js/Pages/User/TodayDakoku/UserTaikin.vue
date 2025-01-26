<script setup>
import CustomToast from "@/Components/CustomToast.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ref, onMounted, watch, computed } from "vue";
import { getAddress, waitForGeolocationPermission } from "@/Utils/action.js";
import { driveRideOptions, supportCompanyFlag } from "@/Utils/field";
import { useToast } from "primevue/usetoast";
import moment from "moment";

const props = defineProps({
  userOrganization: Object,
  attend_type: Object,
  todayDakouData: Object,
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
  id: props.todayDakouData.id,
  targetDate: moment().format("yyyy/MM/DD"),
  attendTime: moment().format("HH:mm:ss"),
  attendType: 2,
});

const toast = useToast();
const progressValue = ref(0);
const breakPosition1 = ref(0);
const breakPosition2 = ref(0);
const breakPosition3 = ref(0);
onMounted(() => {
  setInterval(updateProgress, 1000);
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

const toastBack = ref();
const submit = () => {
  form.post(route("user.attendance.today.taikin.store"), {
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
    onError: () => {
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
</script>
<template>
  <CustomToast group="headless" :bgClass="toastBack" />
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
            <div class="absolute left-0 z-50 -top-8 -md:top-10 syukin-tab">
              <div
                class="p-1 font-bold text-white rounded-md bg-sky-500 status-tab syukin"
              >
                出勤{{ userOrganization.startTime }}
              </div>
            </div>
            <div class="absolute right-0 z-50 -top-8 -md:top-10 taikin-tab">
              <div
                class="p-1 font-bold text-white bg-red-500 rounded-md status-tab taikin"
              >
                退勤{{ userOrganization.endTime }}
              </div>
            </div>
            <div class="px-8">
              <ProgressBar
                :value="progressValue"
                :show-value="false"
                style="height: 8px"
              />
            </div>
            <!-- rest 1 -->
            <div
              class="absolute z-50 -bottom-8 -md:top-10 taikin-tab"
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
              class="absolute z-50 -bottom-8 -md:top-10 taikin-tab"
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
              class="absolute z-50 -bottom-8 -md:top-10 taikin-tab"
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
        <form @submit.prevent="submit" class="mt-12">
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
                disabled
              >
              </Dropdown>
              <InputError :message="form.errors.attendType" />
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
                disabled
              />
              <InputError :message="form.errors.targetDate" />
            </div>
          </div>

          <div class="mt-4 form-inner">
            <div class="label-field label-right">
              <InputLabel
                :value="form.attendType == 2 ? '退勤時刻' : '出勤時間'"
                essential
              />
            </div>
            <div class="input-field">
              <VueDatePicker
                v-model="form.attendTime"
                locale="ja"
                model-type="HH:mm:ss"
                format="HH:mm"
                time-picker
                autoApply
                :placeholder="
                  form.attendType == 2
                    ? '退勤時間を選択してください。'
                    : '出勤時間を選択してください。'
                "
              >
                <template #input-icon>
                  <i class="mt-1 ml-2 pi pi-clock"></i>
                </template>
              </VueDatePicker>
              <InputError :message="form.errors.attendTime" />
            </div>
          </div>
          <hr class="my-4" />
          <div class="flex flex-col items-center gap-2 attend-action__btn">
            <div class="w-full">
              <Button
                type="submit"
                icon="pi pi-sign-out"
                label="退勤"
                class="w-full"
                severity="danger"
              />
            </div>
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
