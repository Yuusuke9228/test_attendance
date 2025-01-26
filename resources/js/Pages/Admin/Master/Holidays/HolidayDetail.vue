<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import { ref, onMounted, computed } from "vue";

const props = defineProps({
  holidays: Object,
});
</script>
<template>
  <AdminLayout title="休日管理">
    <MainContent>
      <template #header>
        <FontAwesomeIcon icon="fa-solid fa-cake-candles" />
        <h3>
          休日管理
          <small>休日を管理します。</small>
        </h3>
      </template>
      <MasterDetailBox link="holiday" :data="holidays">
        <div class="w-full center">
          <div class="relative flex flex-col m-auto max-w-7xl main-info">
            <div class="pb-4 text-lg input-form">
              <div class="mt-3 form-inner">
                <div class="label-field">
                  <p>休日</p>
                </div>
                <div class="input-field flex">
                  <p>{{ holidays.holiday_date }}</p>
                  <em class="text-gray-600">
                    {{ holidays.paid_holiday ? "（有給の計画取得日）" : "" }}</em
                  >
                </div>
              </div>
            </div>
          </div>
        </div>
      </MasterDetailBox>
    </MainContent>
  </AdminLayout>
</template>
