<script setup>
import CustomToast from '@/Components/CustomToast.vue';
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import moment from 'moment'

const props = defineProps({
    breakTimeDetail: Object,
    organization: Object,
})

const toast = useToast();
const form = useForm({
    id: props.breakTimeDetail.id,
    code: props.breakTimeDetail.break_work_pattern_cd,
    organization: props.breakTimeDetail.break_organization,
    startTime: props.breakTimeDetail.break_start_time,
    endTime: props.breakTimeDetail.break_end_time,
    breakName: props.breakTimeDetail.break_name,
    startTime_1: props.breakTimeDetail.break_start_time1,
    endTime_1: props.breakTimeDetail.break_end_time1,
    startTime_2: props.breakTimeDetail.break_start_time2,
    endTime_2: props.breakTimeDetail.break_end_time2,
    startTime_3: props.breakTimeDetail.break_start_time3,
    endTime_3: props.breakTimeDetail.break_end_time3,
    redirectOption: null,
})


const toastBack = ref();
const submit = (e) => {
    form.redirectOption = e;

    form.put(route('admin.master.breaktime.update'), {
        onSuccess: () => {
            toastBack.value = 'bg-green-500/70';
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
    <AdminLayout title="休憩時間・勤務形態管理">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-solid fa-bookmark" />
                <h3>
                    休憩時間・勤務形態管理
                    <small>勤務形態毎の休憩時間を管理します。</small>
                </h3>
            </template>
            <MasterEditBox @emitSubmit=submit link="breaktime" :data="breakTimeDetail">
                <div class="w-full max-w-7xl input-form">
                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="勤務形態コード" essential />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-pencil"></i>
                                <InputText v-model="form.code" class="w-full" placeholder="勤務形態コードを入力してください。" />
                            </div>
                            <InputError :message=form.errors.code />
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="組織" />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-building"></i>
                                <Dropdown v-model="form.organization" :options="organization"
                                    optionLabel="organization_name" optionValue="id" show-clear class="w-full"
                                    placeholder="組織を入力してください。" />
                            </div>
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="管理名" />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-language"></i>
                                <InputText v-model="form.breakName" class="w-full" placeholder="住所を入力してください。" />
                            </div>
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="勤務開始時刻" essential />
                        </div>
                        <div class="input-field">
                            <div class="w-72">
                                <VueDatePicker v-model="form.startTime" locale="ja" format="HH:mm:ss"
                                    modelType="HH:mm:ss" time-picker autoApply class="w-full">
                                    <template #input-icon>
                                        <i class="pl-3 pi pi-clock"></i>
                                    </template>
                                </VueDatePicker>
                                <InputError :message=form.errors.startTime />
                            </div>
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="勤務終了時刻" essential />
                        </div>
                        <div class="input-field">
                            <div class="w-72">
                                <VueDatePicker v-model="form.endTime" locale="ja" format="HH:mm:ss" modelType="HH:mm:ss"
                                    time-picker autoApply class="w-full">
                                    <template #input-icon>
                                        <i class="pl-3 pi pi-clock"></i>
                                    </template>
                                </VueDatePicker>
                                <InputError :message=form.errors.endTime />
                            </div>
                        </div>
                    </div>
                    <!-- 1 -->
                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="休憩開始時刻１" essential />
                        </div>
                        <div class="input-field">
                            <div class="w-72">
                                <VueDatePicker v-model="form.startTime_1" locale="ja" format="HH:mm:ss"
                                    modelType="HH:mm:ss" time-picker autoApply class="w-full">
                                    <template #input-icon>
                                        <i class="pl-3 pi pi-clock"></i>
                                    </template>
                                </VueDatePicker>
                                <InputError :message=form.errors.startTime_1 />
                            </div>
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="休憩終了時刻１" essential />
                        </div>
                        <div class="input-field">
                            <div class="w-72">
                                <VueDatePicker v-model="form.endTime_1" locale="ja" format="HH:mm:ss"
                                    modelType="HH:mm:ss" time-picker autoApply class="w-full">
                                    <template #input-icon>
                                        <i class="pl-3 pi pi-clock"></i>
                                    </template>
                                </VueDatePicker>
                                <InputError :message=form.errors.endTime_1 />
                            </div>
                        </div>
                    </div>
                    <!-- 2 -->
                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="休憩開始時刻２" />
                        </div>
                        <div class="input-field">
                            <div class="w-72">
                                <VueDatePicker v-model="form.startTime_2" locale="ja" format="HH:mm:ss"
                                    modelType="HH:mm:ss" time-picker autoApply class="w-full">
                                    <template #input-icon>
                                        <i class="pl-3 pi pi-clock"></i>
                                    </template>
                                </VueDatePicker>
                            </div>
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="休憩終了時刻２" />
                        </div>
                        <div class="input-field">
                            <div class="w-72">
                                <VueDatePicker v-model="form.endTime_2" locale="ja" format="HH:mm:ss"
                                    modelType="HH:mm:ss" time-picker autoApply class="w-full">
                                    <template #input-icon>
                                        <i class="pl-3 pi pi-clock"></i>
                                    </template>
                                </VueDatePicker>
                            </div>
                        </div>
                    </div>
                    <!-- 3 -->
                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="休憩開始時刻３" />
                        </div>
                        <div class="input-field">
                            <div class="w-72">
                                <VueDatePicker v-model="form.startTime_3" locale="ja" format="HH:mm:ss"
                                    modelType="HH:mm:ss" time-picker autoApply class="w-full">
                                    <template #input-icon>
                                        <i class="pl-3 pi pi-clock"></i>
                                    </template>
                                </VueDatePicker>
                            </div>
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="休憩終了時刻３" />
                        </div>
                        <div class="input-field">
                            <div class="w-72">
                                <VueDatePicker v-model="form.endTime_3" locale="ja" format="HH:mm:ss"
                                    modelType="HH:mm:ss" time-picker autoApply class="w-full">
                                    <template #input-icon>
                                        <i class="pl-3 pi pi-clock"></i>
                                    </template>
                                </VueDatePicker>
                            </div>
                        </div>
                    </div>
                </div>
            </MasterEditBox>
        </MainContent>
    </AdminLayout>
</template>