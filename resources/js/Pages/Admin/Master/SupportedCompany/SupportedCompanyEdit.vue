<script setup>
import CustomToast from '@/Components/CustomToast.vue';
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { getAddress } from '@/Utils/action';

const props = defineProps({
    supportedCompanyDetail: Object,
})

const toast = useToast();
const form = useForm({
    id: props.supportedCompanyDetail.id,
    companyName: props.supportedCompanyDetail.supported_company_name,
    companyPerson: props.supportedCompanyDetail?.supported_company_person,
    companyEmail: props.supportedCompanyDetail?.supported_company_email,
    companyTel: props.supportedCompanyDetail?.supported_company_tel,
    companyZipCode: props.supportedCompanyDetail?.supported_company_zipcode,
    companyAddress: props.supportedCompanyDetail?.supported_company_address,
    redirectOption: null
})

watch(form, async (n, o) => {
    const address = await getAddress(form.companyZipCode);
    if (address?.prefecture) {
        let result = "";
        result+= address.prefecture
        result+= address.area
        result+= address.address
        form.companyAddress = result
    }
}, { deep: true })

const toastBack = ref();
const submit = (e) => {
    form.redirectOption = e;

    form.put(route('admin.master.supported_company.update'), {
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
    <AdminLayout title="応援に行く先の会社">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-solid fa-thumbs-up" />
                <h3>
                    応援に行く先の会社
                    <small>応援に行った先の会社情報を管理します。</small>
                </h3>
            </template>
            <MasterEditBox @emitSubmit=submit link="supported_company" :data="supportedCompanyDetail">
                <div class="w-full max-w-7xl input-form">
                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="会社名" essential />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-building"></i>
                                <InputText v-model="form.companyName" class="w-full" placeholder="会社名を入力してください。" />
                            </div>
                            <InputError :message=form.errors.companyName />
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="担当者" />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-user"></i>
                                <InputText v-model="form.companyPerson" class="w-full" placeholder="担当名を入力してください。" />
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
                                <InputText v-model="form.companyEmail" class="w-full" placeholder="メールアドレスを入力してください。" />
                            </div>
                            <InputError :message=form.errors.companyEmail />
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="電話" />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-phone"></i>
                                <InputText v-model="form.companyTel" class="w-full" placeholder="半角数字で入力してください。" />
                            </div>
                            <span class="text-sm text-black/40 font-regular">
                                <FontAwesomeIcon icon="fa-solid fa-circle-info" />
                                数字、"-"または"_"で記入してください。
                            </span>
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="郵便番号" />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="" style="font-style: normal; padding-bottom: 4px; top: 1rem;">〒</i>
                                <InputText v-model="form.companyZipCode" class="w-full" placeholder="半角数字で入力してください。" />
                            </div>
                            <span class="text-sm text-black/40 font-regular">
                                <FontAwesomeIcon icon="fa-solid fa-circle-info" />
                                数字、"-"または"_"で記入してください。
                            </span>
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="住所" />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-map-marker"></i>
                                <InputText v-model="form.companyAddress" class="w-full" placeholder="郵便番号入力時に自動入力されます。" />
                            </div>
                        </div>
                    </div>
                </div>
            </MasterEditBox>
        </MainContent>
    </AdminLayout>
</template>