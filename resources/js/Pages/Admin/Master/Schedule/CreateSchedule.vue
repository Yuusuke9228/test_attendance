<script setup>
import CustomToast from '@/Components/CustomToast.vue';
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import moment from 'moment'

const props = defineProps({
    occupationList: Object,
    workLocationList: Object,
    usersList: Object,
})

const toast = useToast();
const form = useForm({
    occupation: null,
    workLocation: null,
    users: [],
    date: null,
    startTime: '08:00:00',
    endTime: '17:00:00',
    redirectOption: null
})

const toastBack = ref();
const submit = (redirectOption) => {
    form.redirectOption = redirectOption;
    form.post(route('admin.master.schedule.store'), {
        onFinsh: () => {
            form.reset();
        },
        onSuccess: () => {
            toastBack.value = 'bg-green-500/70';
            form.reset();
            toast.add({
                severity: 'custom',
                summary: '操作成功！',
                life: 2000,
                group: 'headless'
            })
        },
        onErrorr: () => {
            toastBack.value = 'bg-red-500/70';
            toast.add({
                severity: 'custom',
                summary: '操作失敗！',
                life: 2000,
                group: 'headless'
            })
        }
    })
}
</script>
<template>
    <CustomToast group="headless" :bgClass="toastBack" />
    <AdminLayout title="作業予定管理">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-regular fa-calendar-check" />
                <h3>
                    作業予定管理
                    <small>現場毎の作業者の予定を管理します。 従業員が、指定日の何時から何時まで対象の現場で作業する予定かを管理します。 打刻場所との整合性を確認する為に使用します。</small>
                </h3>
            </template>
            <MasterCreateForm @emitSubmit = submit link="schedule">
                <div class="w-full max-w-7xl">
                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="職種" />
                        </div>
                        <div class="input-field">
                            <Dropdown v-model="form.occupation" :options="occupationList" optionLabel="occupation_name"
                                class="w-full" />
                        </div>
                    </div>
                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="現場" />
                        </div>
                        <div class="input-field">
                            <Dropdown v-model="form.workLocation" :options="workLocationList" optionLabel="location_name"
                                class="w-full" />
                        </div>
                    </div>
                    <div class="my-4 form-inner">
                        <div class="label-field label-right">
                            <InputLabel value="従業員" essential />
                        </div>
                        <div class="input-field">
                            <MultiSelect v-model="form.users" display="chip" filter showClear empty-filter-message="検索結果なし"
                                :options="usersList" optionLabel="name" optionValue="id" class="w-full" />
                            <InputError :message="form.errors.users" />
                        </div>
                    </div>
                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="日付" essential />
                        </div>
                        <div class="input-field w-72">
                            <VueDatePicker v-model="form.date" locale="ja" format="yyyy/MM/dd" modelType="yyyy/MM/dd"
                                :enable-time-picker="false" auto-apply class="" />
                            <InputError :message="form.errors.date" />
                        </div>
                    </div>
                    <div class="my-4 form-inner">
                        <div class="label-field label-right">
                            <InputLabel value="開始予定時刻" />
                        </div>
                        <div class="input-field w-72">
                            <VueDatePicker v-model="form.startTime" locale="ja" format="HH:mm:ss" modelType="HH:mm:ss"
                                time-picker auto-apply class="z-50" />
                        </div>
                    </div>
                    <div class="my-4 form-inner">
                        <div class="label-field label-right">
                            <InputLabel value="終了予定時刻" />
                        </div>
                        <div class="input-field w-72">
                            <VueDatePicker v-model="form.endTime" locale="ja" format="HH:mm:ss" modelType="HH:mm:ss"
                                time-picker auto-apply class="z-50" />
                        </div>
                    </div>
                </div>
            </MasterCreateForm>
        </MainContent>
    </AdminLayout>
</template>