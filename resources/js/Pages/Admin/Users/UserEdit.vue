<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ref, reactive, onMounted, computed, watch } from 'vue';
import { master } from '@/Utils/action';
import { useToast } from 'primevue/usetoast';
import axios from 'axios'
import moment from 'moment'
import Clipboard from '@/Components/Clipboard.vue'
import CustomToast from '@/Components/CustomToast.vue'

const props = defineProps({
    userInfo: Object
})
const toast = useToast();
const visibleWorkStyleDialog = ref(false);
const selectionWorkStyle = ref();
const workParentCodeList = ref([]);
onMounted(async () => {
    let breakTimes = await master('breakTime');
    if (breakTimes?.data) {
        workParentCodeList.value = breakTimes.data.map((item) => {
            item.updated_at = moment(item.updated_at).format('yyyy/MM/DD HH:mm:ss')
            return item;
        });
    }
})

const autoGenPassword = ref(false);

const form = useForm({
    id: props.userInfo.id,
    code: props.userInfo.code,
    name: props.userInfo.name,
    email: props.userInfo.email,
    password_updateable: false,
    password: null,
    password_confirmation: null,
    role: props.userInfo.role,
    available: props.userInfo.available ? true : false,
    workParentCode: props.userInfo?.user_data?.break_times
})

watch(autoGenPassword, async (newQuestion, oldQuestion) => {
    if (autoGenPassword.value == false) {
        form.password = null;
        form.password_confirmation = null;
    }
})
const setWorkParentCode = () => {
    if (selectionWorkStyle) {
        form.workParentCode = selectionWorkStyle.value;
        visibleWorkStyleDialog.value = false;
    }
}
const unsetWorkParentCode = () => {
    visibleWorkStyleDialog.value = false;
}

const generatePwd = () => {
    const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    let password = '';
    for (let i = 0; i < 8; i++) {
        const randomIndex = Math.floor(Math.random() * charset.length);
        password += charset[randomIndex];
    }
    form.password = password;
    form.password_confirmation = password;
}

const visible = ref(false);
const bgBack = ref('bg-green-500/60');
const duplicateStatus = ref(false);
const duplicateCodeCheck = async () => {
    visible.value = true;
    if (form.code) {
        const res = await axios.post(route('admin.users.check'), { code: form.code })
        if (res.data === false) {
            duplicateStatus.value = false;
            bgBack.value = 'bg-green-500/60';
            toast.add({
                severity: 'custom',
                summary: '重複コード確認！',
                detail: 'このコードは利用可能です。',
                group: 'headless',
                life: 2000,
            })
        } else {
            duplicateStatus.value = true;
            bgBack.value = 'bg-red-500/60';
            toast.add({
                severity: 'custom',
                summary: '重複コード確認！',
                detail: 'そのコードはすでに使用中です。',
                group: 'headless',
                life: 2000
            })
        }
    }
}

const submit = () => {
    form.patch(route('admin.users.update'), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: '変更成功！',
                detail: form.name+'さんの情報が変更されました。',
                life: 2000
            })
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: '変更失敗！',
                detail: '必須項目を入力してください。',
                life: 2000
            })
        }
    })
}
</script>
<template>
    <AdminLayout title="新規登録">
        <Toast />
        <CustomToast :bgClass="bgBack" position="bottom-right" group="headless" />
        <Loader v-if="form.processing" />
        <MainContent title="ユーザー管理">
            <template #icon>
                <FontAwesomeIcon icon="fa-solid fa-user" />
            </template>
            <ContentBox title="新規登録">
                <template #header>
                    <div class="flex items-center justify-between w-full">
                        <h3>
                            <FontAwesomeIcon icon="fa-solid fa-user-plus" />
                            新規登録
                        </h3>
                        <div class="flex items-center btn_group gap-col-1">
                            <Link :href="route('admin.users.index')" class="">
                            <Button label="一覧" icon="pi pi-list" size="small" class="py-1" severity="success" />
                            </Link>
                        </div>
                    </div>
                </template>
                <div class="w-full pb-8 user-register-page">
                    <form @submit.prevent="submit" class="input-form">

                        <div class="mt-8 form-inner">
                            <div class="mt-2 label-field label-right">
                                <InputLabel value="ユーザーコード" essential />
                            </div>
                            <div class="flex items-center gap-2 input-field">
                                <div class="w-full">
                                    <div class="flex items-center gap-2">
                                        <div class="w-full p-input-icon-left">
                                            <i class="pi pi-id-card"></i>
                                            <InputText v-model="form.code" class="w-full"
                                                :class="duplicateStatus ? 'p-invalid' : ''" placeholder="識別子用のコードを入力してください。"
                                                @change="duplicateCodeCheck" />
                                        </div>
                                        <Button label="重複検査" icon="pi pi-eye" size="small" class="shrink-0 w-32 py-2.5"
                                            :class="{ 'opacity-25': !form.code }" :disabled="!form.code" severity="info"
                                            @click="duplicateCodeCheck" />

                                    </div>
                                    <SmallLabel class="ml-2">
                                        保存後、変更はできません。英小文字、英大文字、数字、"."(ドット)、"-"または"_"で記入してください"-"または"_"で記入してください。他のデータと同じ値は登録できません
                                    </SmallLabel>
                                    <InputError :message="form.errors.code" />
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 form-inner center">
                            <div class="label-field label-right">
                                <InputLabel value="ユーザー名" essential />
                            </div>
                            <div class="input-field">
                                <div class="w-full p-input-icon-left">
                                    <i class="pi pi-user"></i>
                                    <InputText v-model="form.name" class="w-full" :class="{ 'p-invalid': form.errors.name }"
                                        placeholder="ユーザー名を入力してください。" />
                                </div>
                                <InputError :message="form.errors.name" />
                            </div>
                        </div>

                        <div class="mt-8 form-inner center">
                            <div class="label-field label-right">
                                <InputLabel value="メールアドレス" essential />
                            </div>
                            <div class="input-field">
                                <div class="w-full p-input-icon-left">
                                    <i class="pi pi-at"></i>
                                    <InputText v-model="form.email" class="w-full" :class="{ 'p-invalid': form.errors.email }"
                                        :autoComplete="false" placeholder="メールアドレスを入力してください。" />
                                </div>
                                <InputError :message="form.errors.email" />
                            </div>
                        </div>

                        <div class="mt-8 form-inner">
                            <div class="mt-2 label-field label-right">
                                <InputLabel value="勤務形態コード" />
                            </div>
                            <div class="flex items-center gap-2 input-field">
                                <Dropdown v-model="form.workParentCode" :options="workParentCodeList" show-clear filter
                                    class="w-full " placeholder="勤務形態選択してください" empty-message="データなし"
                                    empty-filter-message="データなし" filterLocale="ja" dataKey="id"
                                    :filterFields="['break_work_pattern_cd', 'break_organization', 'break_name']">
                                    <template #value="slotProps">
                                        <div v-if="slotProps.value" class="flex items-center gap-2">
                                            <div class="flex items-center">
                                                <i class="pi pi-palette"></i>
                                            </div>
                                            <div>
                                                <span>{{ slotProps.value.break_work_pattern_cd }}</span>
                                                <span>{{ slotProps.value.organization?.organization_name }}</span>
                                                <span>{{ slotProps.value.break_name }}</span>
                                            </div>
                                        </div>
                                        <div v-else class="flex items-center gap-1">
                                            <i class="pi pi-palette"></i>
                                            {{ slotProps.placeholder }}
                                        </div>
                                    </template>
                                    <template #option="slotProps">
                                        <div class="flex items-center gap-1">
                                            <span>{{ slotProps.option.break_work_pattern_cd }}</span>
                                            <span>{{ slotProps.option.organization?.organization_name }}</span>
                                            <span>{{ slotProps.option.break_name }}</span>
                                        </div>
                                    </template>
                                </Dropdown>
                                <Button label="サーチ" icon="pi pi-search" size="small" class="shrink-0 py-2.5 w-32"
                                    severity="info" @click="visibleWorkStyleDialog = !visibleWorkStyleDialog" />
                            </div>
                        </div>

                        <div class="mt-8 form-inner center">
                            <div class="label-field label-right">
                                <InputLabel value="ロール設定" />
                            </div>
                            <div class="input-field">
                                <div class="flex items-center gap-8">
                                    <div class="flex items-center">
                                        <RadioButton v-model="form.role" inputId="admin" name="role" :value="1" />
                                        <InputLabel htmlFor="admin" value="管理者" class="pl-2" />
                                    </div>
                                    <div class="flex items-center">
                                        <RadioButton v-model="form.role" inputId="user" name="role" :value="2" />
                                        <InputLabel htmlFor="user" value="ユーザー" class="pl-2" />
                                    </div>
                                </div>
                                <InputError :message="form.errors.role" />
                            </div>
                        </div>
                        <div class="my-8 form-inner center">
                            <div class="label-field label-right">
                                <InputLabel value="ログイン権限付与" />
                            </div>
                            <div class="input-field">
                                <div class="flex items-center gap-2">
                                    <InputLabel value="不可" class="pl-2" />
                                    <InputSwitch v-model="form.available" />
                                    <InputLabel value="可" class="pl-2" />
                                </div>
                                <InputError :message="form.errors.available" />
                            </div>
                        </div>
                        <hr>
                        <div class="mt-8 form-inner center">
                            <div class="label-field label-right">
                                <InputLabel htmlFor="updateable-password" value="パスワードを変更する？" />
                            </div>
                            <div class="input-field">
                                <CheckBox inputId="updateable-password" v-model="form.password_updateable" binary />
                            </div>
                        </div>
                        <div v-if="form.password_updateable" class="mt-4 security-setting">
                            <div class="mt-8 form-inner center">
                                <div class="label-field label-right">
                                    <InputLabel value="パスワード自動生成" />
                                </div>
                                <div class="flex items-center gap-8 input-field">
                                    <CheckBox v-model="autoGenPassword" binary />
                                    <Button v-if="autoGenPassword" icon="pi pi-sync" rounded outlined size="small"
                                        severity="success" @click="generatePwd" />
                                    <div v-if="form.password && autoGenPassword" class="relative ml-8">
                                        <Clipboard :value="form.password" />
                                    </div>
                                </div>
                            </div>
                            <div v-if="!autoGenPassword" class="w-full">
                                <div class="mt-8 form-inner center">
                                    <div class="label-field label-right">
                                        <InputLabel value="パスワード設定" essential :autoComplete="false" />
                                    </div>
                                    <div class="input-field">
                                        <div class="w-full p-input-icon-left">
                                            <i class="pi pi-lock"></i>
                                            <InputText type="password" v-model="form.password" class="w-full"
                                                placeholder="パスワードを入力してください。" />
                                        </div>
                                        <InputError :message="form.errors.password" />
                                    </div>
                                </div>
                                <div class="mt-8 form-inner center">
                                    <div class="label-field label-right">
                                        <InputLabel value="パスワード確認" essential />
                                    </div>
                                    <div class="input-field">
                                        <div class="w-full p-input-icon-left">
                                            <i class="pi pi-lock-open"></i>
                                            <InputText type="password" v-model="form.password_confirmation" class="w-full"
                                                placeholder="確認のために再入力してください。" />
                                        </div>
                                        <InputError :message="form.errors.password_confirmation" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="my-8">
                        <div class="mt-8 text-center">
                            <Button type="submit" :label="!form.processing ? '変更する' : ''"
                                :icon="form.processing ? 'pi pi-spin pi-spinner' : 'pi pi-save'" severity="info"
                                class="m-auto w-44" :disabled="form.processing" />
                        </div>
                    </form>
                </div>
            </ContentBox>
        </MainContent>
    </AdminLayout>
    <Dialog v-model:visible="visibleWorkStyleDialog" header="サーチ : 休憩時間・勤務形態管理" modal dismissableMask :draggable="false"
        class="w-full max-w-7xl">
        <div class="flex items-center w-full gap-2 pb-2 border-b filter-tab">
            <Button label="フィルタ" icon="pi pi-filter-fill" size="small" class="py-2" />
            <div class="p-input-icon-left">
                <i class="pi pi-search"></i>
                <InputText v-model="filter" class="p-inputtext-sm" />
            </div>
        </div>
        <div class="data-list datatable center">
            <DataTable :value="workParentCodeList" v-model:selection="selectionWorkStyle" selectionMode="single"
                data-key="id" stripedRows class="border border-teal-300 p-datatable-sm">
                <Column header="ID" field="id" sortable />
                <Column header="勤務形態コー" field="break_work_pattern_cd" sortable />
                <Column header="組織" field="organization.organization_name" sortable bodyClass="whitespace-nowrap" />
                <Column header="管理名" field="break_name" sortable />
                <Column header="勤務開始時刻" field="break_start_time" sortable />
                <Column header="勤務終了時刻" field="break_end_time" sortable />
                <Column header="休憩開始時刻１" field="break_start_time1" sortable />
                <Column header="休憩終了時刻１" field="break_end_time1" sortable />
                <Column header="休憩開始時刻２" field="break_start_time2" sortable />
                <Column header="休憩終了時刻２" field="break_end_time2" sortable />
                <Column header="休憩開始時刻３" field="break_start_time3" sortable />
                <Column header="休憩終了時刻３" field="break_end_time3" sortable />
                <Column header="更新日時" field="updated_at" sortable bodyClass="whitespace-nowrap" />
            </DataTable>
        </div>
        <div class="flex items-center w-full gap-8 mt-4 selected-item">
            <label for="" class="whitespace-nowrap">選択</label>
            <div class="flex items-center w-full p-2 border rounded-md h-14">
                <div v-if="selectionWorkStyle"
                    class="relative flex items-center gap-2 p-2 text-white rounded-md bg-sky-600">
                    <p>
                        {{ selectionWorkStyle?.break_work_pattern_cd }}
                        {{ selectionWorkStyle?.organization.organization_name }}
                        {{ selectionWorkStyle?.break_name }}
                    </p>
                    <i class="cursor-pointer pi pi-times" @click="selectionWorkStyle = null"></i>
                </div>
            </div>
        </div>
        <template #footer>
            <div class="flex items-center justify-between">
                <Button label="閉じる" severity="secondary" size="small" outlined @click="unsetWorkParentCode" />
                <Button label="設定" severity="success" size="small" @click="setWorkParentCode" />
            </div>
        </template>
</Dialog></template>