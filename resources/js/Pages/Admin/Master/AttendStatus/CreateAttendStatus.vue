<script setup>
import CustomToast from '@/Components/CustomToast.vue';
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();
const form = useForm({
    statusName: null,
    redirectOption: null
})

const toastBack = ref();
const submit = (redirectOption) => {
    form.redirectOption = redirectOption;
    form.post(route('admin.master.attend_statuses.store'), {
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
    <AdminLayout title="残業・早退・遅刻">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-solid fa-user-clock" />
                <h3>
                    残業・早退・遅刻
                    <small>各種区分選択項目を管理します。</small>
                </h3>
            </template>
            <MasterCreateForm @emitSubmit=submit link="attend_statuses">
                <div class="w-full max-w-7xl">
                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="選択肢表示名" essential />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-pencil"></i>
                                <InputText v-model="form.statusName" class="w-full" placeholder="選択肢の表示名を入力してください。" />
                            </div>
                            <InputError :message=form.errors.statusName />
                        </div>
                    </div>
                </div>
            </MasterCreateForm>
        </MainContent>
    </AdminLayout>
</template>