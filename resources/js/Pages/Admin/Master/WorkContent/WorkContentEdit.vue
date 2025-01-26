<script setup>
import CustomToast from '@/Components/CustomToast.vue';
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import moment from 'moment'

const props = defineProps({
    workContentsDetail: Object,
})

const toast = useToast();
const form = useForm({
    id: props.workContentsDetail.id,
    workContentName: props.workContentsDetail.work_content_name,
    redirectOption: null
})


const toastBack = ref();
const submit = (e) => {
    form.redirectOption = e;

    form.put(route('admin.master.work_contents.update'), {
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
    <AdminLayout title="作業内容">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-solid fa-person-digging" />
                <h3>
                    作業内容
                    <small>職種で選択したものの中の作業項目を管理します</small>
                </h3>
            </template>
            <MasterEditBox @emitSubmit=submit link="work_contents" :data="workContentsDetail">
                <div class="w-full max-w-7xl input-form">
                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="職種管理" />
                        </div>
                        <div class="input-field">
                            {{ workContentsDetail.occupation?.occupation_name }}
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="作業名" essential/>
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
            </MasterEditBox>
        </MainContent>
    </AdminLayout></template>