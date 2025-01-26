<script setup>
import CustomToast from '@/Components/CustomToast.vue';
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import moment from 'moment'

const props = defineProps({
    detailSchedule: Object,
    usersList: Object,
})

const employes = computed(() => {
    if(props.detailSchedule.user_id) {
        let users = "";
        JSON.parse(props.detailSchedule?.user_id).map((item) => {
            users+=props.usersList.filter(filter => filter.id == item)[0].name+"、";
        })
        return users?.slice(0,-1);
    }
})
const toast = useToast();
const visibleRemoveDialog = ref(false);

onMounted(() => {

})

const deleteForm = useForm({
    id: null
})

const toastBack = ref();
const remove = () => {
    deleteForm.delete(route('admin.master.schedule.destroy'), {
        onSuccess: () => {
            toastBack.value = 'bg-green-500/70';
            toast.add({
                severity: 'custom',
                summary: '削除成功！',
                life: 2000,
                group: 'headless'
            })
        },
        onErrorr: () => {
            toastBack.value = 'bg-red-500/70';
            toast.add({
                severity: 'custom',
                summary: '削除失敗！',
                life: 2000,
                group: 'headless'
            })
        }
    })
    visibleRemoveDialog.value = false
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
            <MasterDetailBox link="schedule" :data="detailSchedule">
                <div class="w-full center">
                    <div class="relative flex flex-col m-auto max-w-7xl main-info">
                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>職種</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ detailSchedule.occupations?.occupation_name }}</p>
                                </div>
                            </div>

                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>現場</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ detailSchedule.locations?.location_name }}</p>
                                </div>
                            </div>

                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>従業員</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ employes }}</p>
                                </div>
                            </div>

                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>日付</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ detailSchedule.schedule_date }}</p>
                                </div>
                            </div>
                            
                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>開始予定時刻</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ detailSchedule.schedule_start_time }}</p>
                                </div>
                            </div>

                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>終了予定時刻</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ detailSchedule.schedule_end_time }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </MasterDetailBox>
        </MainContent>
    </AdminLayout>
</template>