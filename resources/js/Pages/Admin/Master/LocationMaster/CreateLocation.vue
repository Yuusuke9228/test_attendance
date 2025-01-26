<script setup>
import CustomToast from '@/Components/CustomToast.vue';
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import moment from 'moment'

const props = defineProps({

})

const toast = useToast();
const form = useForm({
    locationName: null,
    address: null,
    flag: true,
    redirectOption: null
})

const toastBack = ref();
const submit = (redirectOption) => {
    form.redirectOption = redirectOption;
    form.post(route('admin.master.location.store'), {
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
    <AdminLayout title="現場管理">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-regular fa-calendar-check" />
                <h3>
                    現場管理
                    <small>打刻場所を選択するマスターです。 有効フラグが立っている現場のみ表示されます。</small>
                </h3>
            </template>
            <MasterCreateForm @emitSubmit=submit link="location">
                <div class="w-full max-w-7xl">
                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="現場名" essential />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-map-marker"></i>
                                <InputText v-model="form.locationName" class="w-full" placeholder="現場名を入力してください。"/>
                            </div>
                            <InputError :message = form.errors.locationName />
                        </div>
                    </div>
                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="住所" />
                        </div>
                        <div class="input-field">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-map"></i>
                                <InputText v-model="form.address" class="w-full" placeholder="住所を入力してください。"/>
                            </div>
                        </div>
                    </div>
                    <div class="my-4 form-inner">
                        <div class="label-field label-right">
                            <InputLabel value="有効無効" />
                        </div>
                        <div class="input-field">
                            <div class="flex items-center gap-2">
                                <span>無効</span>
                                <InputSwitch v-model="form.flag" />
                                <span>有効</span>
                            </div>
                        </div>
                    </div>
                </div>
            </MasterCreateForm>
        </MainContent>
    </AdminLayout>
</template>