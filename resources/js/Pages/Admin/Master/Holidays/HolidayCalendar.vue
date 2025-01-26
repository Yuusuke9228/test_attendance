<script setup>
import CustomToast from "@/Components/CustomToast.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ref, onMounted, computed, watch } from "vue";
import { useToast } from "primevue/usetoast";
import moment from "moment";
import axios from "axios";
import { calendarObject, delay } from "@/Utils/action";

const toast = useToast();
const holidaySavedStatus = ref(false);
const currentDate = ref(moment().format("yyyy"));
const nonHolidays = ref(0);
const visibleRemoveConfirm = ref(false);
const weekendSetVisible = ref(false);

const form = useForm({
  year: currentDate.value,
  holidayDate: [],
  redirectOption: 1,
});

const startMonth = 4;

const calendarList = computed(() => {
  return calendarObject(`${currentDate.value}/${startMonth}/01`, 0).dateList;
});
const daysList = computed(() => {
  return calendarObject(`${currentDate.value}/${startMonth}/01`, 0).daysList;
});

const handleAddWeekendHoliday = async () => {
  if (!holidaySavedStatus.value) {
    if (weekendSetVisible.value) {
      await makingHolidayWithWeekday();
    } else {
      form.holidayDate = [];
    }
  }
};

const addDate = (d_, paid) => {
  let fDate = moment(new Date(d_)).format("yyyy-MM-DD");
  if (!form.holidayDate.filter((f) => f.date == fDate)[0]) {
    form.holidayDate.push({ date: fDate, paid: paid });
  } else {
    let elIndex = form.holidayDate.findIndex((f) => f.date == fDate);
    if (elIndex != -1) {
      form.holidayDate.splice(elIndex, 1);
    }
  }
};
onMounted(async () => {
  await fetchHolidays();
  // await makingHolidayWithWeekday();
  await getNonHolidays();

  let isdblClick = false;
  let dateCells = document.querySelectorAll(".date-cell");
  dateCells.forEach((cell) => {
    cell.addEventListener("click", async () => {
      await delay(250);
      if (!isdblClick) {
        let date = cell.getAttribute("data-date");
        if (date) {
          addDate(date, 0);
        }
      }
    });
    cell.addEventListener("dblclick", async () => {
      isdblClick = true;
      await delay(500);
      let date = cell.getAttribute("data-date");
      if (date) {
        addDate(date, 1);
      }
      isdblClick = false;
    });
  });
});

// method
const fetchHolidays = async () => {
  let holidays = await axios.get(
    route("admin.master.holiday.getholidays", { year: currentDate.value })
  );
  if (holidays.data.length > 0) {
    holidaySavedStatus.value = true;
    form.holidayDate = holidays.data.map((item) => {
      return {
        date: item.holiday_date,
        paid: item.paid_holiday ? 1 : 0,
      };
    });
  } else {
    holidaySavedStatus.value = false;
    form.holidayDate = [];
  }
};
const makingHolidayWithWeekday = async () => {
  if (form.holidayDate.length > 0) {
    return;
  }
  const startDate = moment(`${currentDate.value}/${startMonth}/01`);
  const endDate = moment(`${currentDate.value}/${startMonth}/01`).add(12, "M");
  const currDate = startDate.clone();
  while (currDate.isSameOrBefore(endDate)) {
    if (currDate.format("d") == 0 || currDate.format("d") == 6) {
      if (!form.holidayDate.filter((f) => f.date == currDate.format("yyyy-MM-DD"))[0]) {
        form.holidayDate.push({
          date: currDate.format("yyyy-MM-DD"),
          paid: 0,
        });
      }
    }
    currDate.add(1, "day");
  }
};
const getNonHolidays = async () => {
  let start_date = moment([currentDate.value, 3, 1]); // 4/1
  let end_date = moment([currentDate.value * 1 + 1, 3, 1]); // 4/1
  let diffDays = end_date.diff(start_date, "days");
  nonHolidays.value = diffDays - form.holidayDate.length;
};
const getMonthlyHolidays = (month) => {
  /**
   * Parameter month: yyyy/MM
   */
  return (
    form.holidayDate.filter(
      (filter) =>
        moment(new Date(filter.date)).format("yyyy/MM") == month && filter.paid == 0
    )?.length ?? 0
  );
};
const getYearlyHolidays = (type) => {
  if (type == "general") {
    return form.holidayDate.filter((f) => f.paid == 0)?.length ?? 0;
  } else if (type == "paid") {
    return form.holidayDate.filter((f) => f.paid == 1)?.length ?? 0;
  }
};
const getPaidMonthlyHolidays = (month) => {
  /**
   * Parameter month: yyyy/MM
   */
  return (
    form.holidayDate.filter(
      (filter) =>
        moment(new Date(filter.date)).format("yyyy/MM") == month && filter.paid == 1
    )?.length ?? 0
  );
};

const getMonthlyWorkingDays = (month) => {
  /**
   * Parameter month: yyyy/MM
   */
  let holidays =
    form.holidayDate.filter(
      (filter) =>
        moment(new Date(filter.date)).format("yyyy/MM") == month && filter.paid == 0
    )?.length ?? 0;
  let fullDays = moment(month, "yyyy/MM").daysInMonth();
  return fullDays - holidays;
};
watch(
  currentDate,
  async () => {
    await fetchHolidays();
    // await makingHolidayWithWeekday();
    form.year = currentDate.value;
    deleteForm.year = currentDate.value;
  },
  { deep: true }
);

watch(
  form,
  async () => {
    await getNonHolidays();
  },
  { deep: true }
);

const decrease = () => {
  currentDate.value = moment(new Date(currentDate.value))
    .subtract(1, "year")
    .format("yyyy");
  form.year = currentDate.value;
  deleteForm.year = currentDate.value;
};

const increase = () => {
  currentDate.value = moment(new Date(currentDate.value)).add(1, "year").format("yyyy");
  form.year = currentDate.value;
  deleteForm.year = currentDate.value;
};
const highLightToday = (date) => {
  if (date !== "" && date == moment().format("yyyy/MM/DD")) {
    return "bg-sky-500 text-white font-bold";
  }
};
const highLightWeekday = (day) => {
  if (day == 0) {
    return "text-red-500";
  } else if (day == 6) {
    return "text-sky-500";
  }
};
const weekdayMark = (day) => {
  if (day !== "") {
    if (moment(new Date(day)).format("d") == 0) {
      return "text-red-500";
    }
    if (moment(new Date(day)).format("d") == 6) {
      return "text-sky-500";
    }
  }
};
const holidaysMark = (day) => {
  let t1 = day !== "";
  let t2 = form.holidayDate.filter(
    (f) => f.date == moment(new Date(day)).format("yyyy-MM-DD")
  )[0];
  let t3 = t2 && t2?.paid == 1;
  if (t1 && t2) {
    if (t3) {
      return "bg-orange-200";
    }
    return "bg-pink-200";
  }
};

const menu = ref();
const menuItems = ref([
  {
    key: 0,
    label: "休日登録",
    command: () => handleAddHoliday(1),
  },
  {
    key: 1,
    label: "有給休日登録",
    command: () => handleAddHoliday(2),
  },
]);
const selectedDay = ref();
const onRightClick = (event, day) => {
  selectedDay.value = day;
  menu.value.show(event);
};

const handleAddHoliday = (e) => {
  if (e == 1) {
    addDate(selectedDay.value, 0);
  } else if (e == 2) {
    addDate(selectedDay.value, 1);
  }
};

const handleExportExcel = async () => {
  let startDate = moment(`${currentDate.value}/${startMonth}/01`).format("yyyy-MM-DD");
  let endDate = moment(`${currentDate.value}/${startMonth}/01`)
    .add(12, "M")
    .subtract(1, "d")
    .format("yyyy-MM-DD");
  await axios
    .post(route("admin.master.holiday.exportCalendar"), {
      year: currentDate.value,
      list: calendarList.value,
      startDate: startDate,
      endDate: endDate,
    })
    .then((res) => {
      if (res.data) {
        location.href = route("file_download", { file_path: res.data.path });
      }
    })
    .catch((error) => {
      console.error(error);
    });
};

const submit = () => {
  form.post(route("admin.master.holiday.store"), {
    onSuccess: () => {
      toast.add({
        severity: "success",
        summary: "登録済み",
        life: 2000,
      });
    },
    onError: (e) => {
      toast.add({
        severity: "error",
        summary: "登録失敗！",
        detail: "日付を選択してください。",
        life: 2000,
      });
    },
  });
};
const deleteForm = useForm({
  year: currentDate.value,
});
const handleRemove = () => {
  deleteForm.delete(route("admin.master.holiday.destroy.year"), {
    onSuccess: () => {
      toast.add({
        severity: "success",
        summary: "登録済み",
        life: 2000,
      });
    },
    onFinish: () => {
      visibleRemoveConfirm.value = false;
    },
  });
};
</script>
<template>
  <Toast position="top-center" />
  <AdminLayout title="休日管理">
    <MainContent>
      <template #header>
        <FontAwesomeIcon icon="fa-solid fa-cake-candles" />
        <h3>
          休日管理
          <small>休日を管理します。</small>
        </h3>
      </template>
      <Loader v-if="form.processing" />
      <div class="w-full p-4 bg-white rounded-md shadow-lg holidays-calendar">
        <div class="flex justify-between pb-4 border-b itmes-center">
          <div class="relative flex items-center gap-2 header">
            <div class="flex items-center p-buttonset">
              <Button icon="pi pi-angle-left" size="small" outlined @click="decrease" />
              <Button icon="pi pi-angle-right" size="small" outlined @click="increase" />
            </div>
            <div class="flex items-center">
              <VueDatePicker
                v-model="currentDate"
                locale="ja"
                year-picker
                modelType="yyyy"
                format="yyyy年"
                autoApply
              />
            </div>
            <div class="flex items-center ml-4">
              <CheckBox
                inputId="weekend_set_holiday"
                v-model="weekendSetVisible"
                binary
                @change="handleAddWeekendHoliday"
              />
              <label for="weekend_set_holiday" class="pl-2">
                <span>土、日曜日を休日に追加</span>
                <small class="block">一度DBに登録した年度には適用されません。</small>
              </label>
            </div>
          </div>
          <div class="flex flex-col items-end">
            <div class="flex items-end gap-4">
              <p class="font-bold text-gray-700">非休日数: {{ nonHolidays }}日</p>
              <Button
                label="保存する"
                icon="pi pi-save"
                size="small"
                severity="success"
                @click="submit"
              />
              <Button
                label="削除する"
                icon="pi pi-trash"
                size="small"
                severity="danger"
                @click="visibleRemoveConfirm = true"
              />
              <Button
                label="Excel出力"
                icon="pi pi-download"
                size="small"
                severity="info"
                @click="handleExportExcel"
              />
            </div>
            <span class="text-end w-full text-sm">
              <i class="pi pi-info-circle text-xs"></i>
              追加した休日を保存した後にEXCELに出力してください。
            </span>
          </div>
        </div>
        <div class="saved_status text-sm font-bold mt-1">
          <p v-if="holidaySavedStatus" class="text-green-600">
            休日一覧に登録されました。
          </p>
          <p v-else class="text-red-500">まだ休日一覧に登録されていません。</p>
        </div>
        <div class="mt-4 calendar-field">
          <div
            class="grid w-full grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
          >
            <div
              v-for="(months, index) in calendarList"
              :key="index"
              class="overflow-hidden border border-teal-500 rounded-md calendar-table-field"
            >
              <table class="w-full bg-white table-auto">
                <thead>
                  <tr>
                    <th colspan="7" class="text-white bg-teal-500">
                      <div class="text-center">
                        <span>
                          {{
                            moment(`${currentDate}/${startMonth}/1`)
                              .add(index, "M")
                              .format("yyyy年M月")
                          }}
                        </span>
                      </div>
                      <div class="text-center">
                        <small class="font-medium">
                          稼働日数：
                          {{
                            getMonthlyWorkingDays(
                              moment(`${currentDate}/${startMonth}/1`)
                                .add(index, "M")
                                .format("yyyy/MM")
                            )
                          }}日
                        </small>
                        <small class="font-medium">
                          休日数：
                          {{
                            getMonthlyHolidays(
                              moment(`${currentDate}/${startMonth}/1`)
                                .add(index, "M")
                                .format("yyyy/MM")
                            )
                          }}日
                        </small>
                        <small class="font-medium">
                          有給休日数：{{
                            getPaidMonthlyHolidays(
                              moment(`${currentDate}/${startMonth}/1`)
                                .add(index, "M")
                                .format("yyyy/MM")
                            )
                          }}日
                        </small>
                      </div>
                    </th>
                  </tr>
                  <tr>
                    <th
                      v-for="(item, weekIndex) in daysList"
                      :key="weekIndex"
                      class="text-teal-800 weekday-header"
                    >
                      <div class="flex items-center justify-center gap-2">
                        <span :class="highLightWeekday(item.value)">
                          {{ item.label }}
                        </span>
                      </div>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(weekday, weekIndex) in months" :key="weekIndex">
                    <td
                      v-for="(day, dayIndex) in weekday"
                      :key="dayIndex"
                      :data-date="day"
                      class="date-cell"
                      :class="[weekdayMark(day), holidaysMark(day)]"
                      @contextmenu="onRightClick($event, day)"
                    >
                      <span :class="highLightToday(day)">
                        {{ day ? moment(new Date(day)).format("D") : "" }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="flex justify-center gap-8 mt-2 font-bold">
            <p>
              <span>年間稼働日数：</span>
              <span>{{ nonHolidays }}日</span>
            </p>
            <p class="text-pink-600">
              <span>年間休日数：</span>
              <span>{{ getYearlyHolidays("general") }}日</span>
            </p>
            <p class="text-orange-500">
              <span>年間有給休日数：</span>
              <span>{{ getYearlyHolidays("paid") }}日</span>
            </p>
          </div>
        </div>
      </div>
      <ContextMenu ref="menu" :model="menuItems" class="cursor-pointer !min-w-[40px]">
        <template #item="{ item, props }">
          <p
            class="py-1 px-2 text-xs text-red-600 flex items-center gap-2"
            :class="{ 'text-sky-600': item.key == 0 }"
          >
            <i class="pi pi-plus-circle text-xs"></i>
            <span>{{ item.label }}</span>
          </p>
        </template>
      </ContextMenu>
    </MainContent>
  </AdminLayout>
  <Dialog
    v-model:visible="visibleRemoveConfirm"
    modal
    dismissable-mask
    :draggable="false"
    class="w-full max-w-lg"
  >
    <template #header>
      <span class="text-lg font-bold text-red-600">削除しますか？</span>
    </template>
    <div class="w-full text-center">
      <i class="text-5xl text-red-500 pi pi-info-circle"></i>
      <p class="text-xl font-bold text-red-500">本当に削除しますか？</p>
      <span>
        {{
          `${currentDate}年4月1日～
        ${currentDate * 1 + 1}年3月31日までの休日を削除します。`
        }}
      </span>
      <div class="flex items-center justify-center w-full gap-4 mt-4">
        <Button
          label="いいえ"
          class="w-24 shrink-0"
          severity="secondary"
          @click="visibleRemoveConfirm = false"
        />
        <Button
          label="はい"
          class="w-24 shrink-0"
          severity="success"
          @click="handleRemove"
        />
      </div>
    </div>
  </Dialog>
</template>
<style lang="scss" scoped>
.holidays-calendar {
  .calendar-field {
    table {
      tr {
        th,
        td {
          &:not(:first-child) {
            border-left: 1px solid #c6c6c6;
          }

          border-top: 1px solid #c6c6c6;
          user-select: none;
          -webkit-user-select: none;
        }

        th {
          height: 30px;
          text-align: center;
          vertical-align: middle;
          font-size: 16px;

          &.weekday-header {
            background-color: #f7f7f7;
          }
        }

        td {
          height: 25px;
          text-align: center;
          vertical-align: middle;
          cursor: pointer;

          span {
            display: block;
            width: 23px;
            height: 23px;
            margin: auto;
            border-radius: 100%;
          }
        }
      }
    }
  }
}
</style>
