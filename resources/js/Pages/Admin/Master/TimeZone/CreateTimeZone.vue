<script setup>
import CustomToast from '@/Components/CustomToast.vue';
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast';

const props = defineProps({

})

const toast = useToast();
const form = useForm({
    timezone: null,
    redirectOption: null
})

const toastBack = ref();
const submit = (redirectOption) => {
    form.redirectOption = redirectOption;
    form.post(route('admin.master.timezone.store'), {
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
    <AdminLayout title="時間帯区分">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-regular fa-clock" />
                <h3>
                    時間帯区分
                </h3>
            </template>
            <MasterCreateForm @emitSubmit=submit link="timezone">
                <div class="w-full max-w-7xl">
                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="時間帯" essential />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-clock"></i>
                                <InputText v-model="form.timezone" class="w-full" placeholder="時間帯を入力してください。"/>
                            </div>
                            <InputError :message = form.errors.timezone />
                        </div>
                    </div>
                </div>
            </MasterCreateForm>
        </MainContent>
    </AdminLayout>
</template>