<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    breakTimeDetail: Object,
})
</script>
<template>
    <AdminLayout title="休憩時間・勤務形態管理">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-solid fa-bookmark" />
                <h3>
                    休憩時間・勤務形態管理
                    <small>勤務形態毎の休憩時間を管理します。</small>
                </h3>
            </template>
            <MasterDetailBox link="breaktime" :data="breakTimeDetail">
                <div class="w-full center">
                    <div class="relative flex flex-col m-auto max-w-7xl main-info">
                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>勤務形態コード</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ breakTimeDetail.break_work_pattern_cd }}</p>
                                </div>
                            </div>

                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>組織</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ breakTimeDetail.organization.organization_name }}</p>
                                </div>
                            </div>

                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>管理名</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ breakTimeDetail.break_name }}</p>
                                </div>
                            </div>
                            <!-- Time -->
                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>勤務開始時刻</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ breakTimeDetail.break_start_time }}</p>
                                </div>
                            </div>
                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>勤務終了時刻</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ breakTimeDetail.break_end_time }}</p>
                                </div>
                            </div>
                            <!-- Time1 -->
                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>休憩開始時刻１</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ breakTimeDetail.break_start_time1 }}</p>
                                </div>
                            </div>
                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>休憩終了時刻１</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ breakTimeDetail.break_end_time1 }}</p>
                                </div>
                            </div>
                            <!-- Time2 -->
                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>休憩開始時刻２</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ breakTimeDetail.break_start_time2 }}</p>
                                </div>
                            </div>
                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>休憩終了時刻２</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ breakTimeDetail.break_end_time2 }}</p>
                                </div>
                            </div>
                            <!-- Time3 -->
                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>休憩開始時刻３</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ breakTimeDetail.break_start_time3 }}</p>
                                </div>
                            </div>
                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>休憩終了時刻３</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ breakTimeDetail.break_end_time3 }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </MasterDetailBox>
        </MainContent>
    </AdminLayout>
</template>