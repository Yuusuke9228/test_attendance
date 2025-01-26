<script setup>
import CustomToast from '@/Components/CustomToast.vue';
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { getAddress } from '@/Utils/action';

const props = defineProps({
    customerDetail: Object,
})

const toast = useToast();
const form = useForm({
    id: props.customerDetail?.id,
    name: props.customerDetail?.customer_name,
    person: props.customerDetail?.customer_person,
    saluation: props.customerDetail?.customer_saluation,
    email: props.customerDetail?.customer_email,
    tel: props.customerDetail?.customer_tel,
    fax: props.customerDetail?.customer_fax,
    zipCode: props.customerDetail?.customer_zip_code,
    address_1: props.customerDetail?.customer_address_1,
    address_2: props.customerDetail?.customer_address_2,
    memo: props.customerDetail?.customer_memo,
    redirectOption: null
})

watch(form, async (n, o) => {
    const address = await getAddress(form.zipCode);
    if (address?.prefecture) {
        let result = "";
        result += address.prefecture
        result += address.area
        result += address.address
        form.address_1 = result
    }
}, { deep: true })

const toastBack = ref();
const submit = (e) => {
    form.redirectOption = e;

    form.put(route('admin.master.customer.update'), {
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
    <AdminLayout title="得意先">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-solid fa-crown" />
                <h3>
                    得意先
                    <small>得意先情報を管理します。</small>
                </h3>
            </template>
            <MasterEditBox @emitSubmit=submit link="customer" :data="customerDetail">
                <div class="w-full max-w-7xl input-form">
                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="会社名" essential />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-building"></i>
                                <InputText v-model="form.name" class="w-full" placeholder="会社名を入力してください。" />
                            </div>
                            <InputError :message=form.errors.name />
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="担当者" />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-user"></i>
                                <InputText v-model="form.person" class="w-full" placeholder="担当者を入力してください。" />
                            </div>
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="敬称" />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-pencil"></i>
                                <InputText v-model="form.saluation" class="w-full" placeholder="敬称を入力してください。" />
                            </div>
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="メール" />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-at"></i>
                                <InputText v-model="form.email" class="w-full" placeholder="メールアドレスを入力してください。" />
                            </div>
                            <InputError :message="form.errors.email" />
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="電話" />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-phone"></i>
                                <InputText v-model="form.tel" class="w-full" placeholder="電話番号を入力してください。" />
                            </div>
                            <p class="text-sm text-black/40">
                                <FontAwesomeIcon icon="fa-solid fa-circle-info" />
                                数字、"-"または"_"で記入してください。
                            </p>
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="ファックス番号" />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <FontAwesomeIcon icon="fa-solid fa-fax" />
                                <InputText v-model="form.fax" class="w-full" placeholder="ファックス番号を入力してください。" />
                            </div>
                            <p class="text-sm text-black/40">
                                <FontAwesomeIcon icon="fa-solid fa-circle-info" />
                                数字、"-"または"_"で記入してください。
                            </p>
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="郵便番号" />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="" style="font-style: normal; padding-bottom: 4px; top: 1rem;">〒</i>
                                <InputText v-model="form.zipCode" class="w-full" placeholder="郵便番号を入力してください。" />
                            </div>
                            <p class="text-sm text-black/40">
                                <FontAwesomeIcon icon="fa-solid fa-circle-info" />
                                数字、"-"または"_"で記入してください。
                            </p>
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="住所１" />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-map"></i>
                                <InputText v-model="form.address_1" class="w-full"
                                    placeholder="住所１を入力してください。(郵便番号入力時に自動入力されます。)" />
                            </div>
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="住所２" />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-map-marker"></i>
                                <InputText v-model="form.address_2" class="w-full" placeholder="住所２を入力してください。" />
                            </div>
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="備考" />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-book"></i>
                                <InputText v-model="form.memo" class="w-full" placeholder="備考を入力してください。" />
                            </div>
                        </div>
                    </div>
                </div>
        </MasterEditBox>
    </MainContent>
</AdminLayout></template>