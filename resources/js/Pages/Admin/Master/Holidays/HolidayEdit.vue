<script setup>
import CustomToast from "@/Components/CustomToast.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ref, onMounted, computed } from "vue";
import { useToast } from "primevue/usetoast";
import moment from "moment";

const props = defineProps({
  holidays: Object,
});

const toast = useToast();
const form = useForm({
  id: props.holidays.id,
  holidayDate: moment(props.holidays.holiday_date).format("yyyy/MM/DD"),
  paidHolidayVisible: props.holidays?.paid_holiday,
  redirectOption: null,
});

const toastBack = ref();
const submit = (e) => {
  form.redirectOption = e;

  form.put(route("admin.master.holiday.update"), {
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
</script>
<template>
  <CustomToast group="headless" :bgClass="toastBack" />
  <AdminLayout title="休日管理">
    <MainContent>
      <template #header>
        <FontAwesomeIcon icon="fa-solid fa-cake-candles" />
        <h3>
          休日管理
          <small>休日を管理します。</small>
        </h3>
      </template>
      <MasterEditBox @emitSubmit="submit" link="holiday" :data="holidays">
        <div class="w-full max-w-7xl input-form">
          <div class="my-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="休日" essential />
            </div>
            <div class="input-field w-72">
              <VueDatePicker
                v-model="form.holidayDate"
                locale="ja"
                modelType="yyyy/MM/dd"
                format="yyyy/MM/dd"
                :enable-time-picker="false"
                autoApply
                placeholder=""
              />
              <InputError :message="form.errors.holidayDate" />
            </div>
          </div>
          <div class="my-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel htmlFor="paid_holiday" value="有給の計画取得日" class="pl-2" />
            </div>
            <div class="input-field flex items-center">
              <CheckBox inputId="paid_holiday" v-model="form.paidHolidayVisible" binary />
            </div>
          </div>
        </div>
      </MasterEditBox>
    </MainContent>
  </AdminLayout>
</template>
