<script setup>
import CustomToast from '@/Components/CustomToast.vue';
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import moment from 'moment'

const props = defineProps({
    timezoneData: Object
})

const toast = useToast();
const form = useForm({
    id: props.timezoneData.id,
    timezone: props.timezoneData.detail_times,
    redirectOption: null
})


const toastBack = ref();
const submit = (e) => {
    form.redirectOption = e;

    form.put(route('admin.master.timezone.update'), {
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
    <AdminLayout title="時間帯区分">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-solid fa-clock" />
                <h3>
                    時間帯区分
                </h3>
            </template>
            <MasterEditBox @emitSubmit=submit link="timezone" :data="timezoneData">
                <div class="w-full max-w-7xl input-form">
                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="時間帯" essential />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-clock"></i>
                                <InputText v-model="form.timezone" class="w-full" placeholder="時間帯を入力してください。" />
                            </div>
                            <InputError :message=form.errors.timezone />
                        </div>
                    </div>
                </div>
            </MasterEditBox>
        </MainContent>
    </AdminLayout>
</template>