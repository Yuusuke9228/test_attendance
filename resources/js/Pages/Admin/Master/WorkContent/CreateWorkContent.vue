<script setup>
import CustomToast from '@/Components/CustomToast.vue';
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    occupation: Object
})

const toast = useToast();
const form = useForm({
    occupation: null,
    workContentName: null,
    redirectOption: null,
})

const toastBack = ref();
const submit = (redirectOption) => {
    form.redirectOption = redirectOption;
    form.post(route('admin.master.work_contents.store'), {
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
    <AdminLayout title="作業内容">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-solid fa-person-digging" />
                <h3>
                    作業内容
                    <small>職種で選択したものの中の作業項目を管理します</small>
                </h3>
            </template>
            <MasterCreateForm @emitSubmit=submit link="work_contents">
                <div class="w-full max-w-7xl">
                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="職種管理" essential />
                        </div>
                        <div class="input-field">
                            <Dropdown v-model="form.occupation" :options="occupation" optionLabel="occupation_name" optionValue="id" class="w-full" placeholder="職種管理を選択してください。" />
                            <InputError :message=form.errors.occupation />
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="作業名" essential />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-pencil"></i>
                                <InputText v-model="form.workContentName" class="w-full" placeholder="作業名を入力してください。" />
                            </div>
                            <InputError :message=form.errors.workContentName />
                        </div>
                    </div>
                </div>
            </MasterCreateForm>
        </MainContent>
    </AdminLayout>
</template>