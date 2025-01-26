<script setup>
import { useSlots } from 'vue';
import { useToast } from 'primevue/usetoast';
import { Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import moment from 'moment'

const props = defineProps({
    title: String,
    link: String,
    data: Object,
})

const emit = defineEmits(['emitSubmit'])

const visibleRemoveDialog = ref(false);
const redirectOption = ref(1);

const submit = () => {
    emit("emitSubmit", redirectOption.value);
}

const deleteForm = useForm({
    id: props.data.id
})

const toast = useToast();
const toastBack = ref();
const removeRow = () => {
    deleteForm.delete(route(`admin.master.${props.link}.destroy`), {
        onSuccess: () => {
            toastBack.value = 'bg-green-500/70';
            toast.add({
                severity: 'custom',
                summary: '削除成功！',
                life: 2000,
                group: 'headless'
            })
            visibleRemoveDialog.value = false;
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
    visibleRemoveDialog.value = false;
}

</script>
<template>
    <CustomToast group="headless" :bgClass="toastBack" />
    <div class="field-box">
        <div class="w-full box-header">
            <div class="flex items-center justify-between">
                <h4>{{ title ?? '編集' }}</h4>
                <div class="flex gap-2">
                    <Link :href="link ? route(`admin.master.${link}.create`) : '#'">
                    <Button label="新規" icon="pi pi-plus" class="py-1" size="small" severity="success" />
                    </Link>
                    <Link :href="link ? route(`admin.master.${link}.show`, { id: data.id }) : '#'">
                    <Button label="表示" icon="pi pi-eye" class="py-1" size="small" severity="info" />
                    </Link>
                    <Link :href="link ? route(`admin.master.${link}.index`) : '#'">
                    <Button label="一覧" icon="pi pi-list" class="py-1" size="small" severity="secondary" />
                    </Link>
                    <Button label="削除" icon="pi pi-trash" class="py-1" size="small" severity="danger"
                        @click="visibleRemoveDialog = true" />
                </div>
            </div>
        </div>
        <form @submit.prevent=submit class="box-content">
            <div class="p-3 system-values">
                <ul class="flex items-stretch justify-center system-values-list">
                    <li>
                        <p class="system-values-label">ID</p>
                        <p class="system-values-item">{{ data?.id }}</p>
                    </li>
                    <li>
                        <p class="system-values-label">作成日時</p>
                        <p class="system-values-item">
                            {{ data?.created_at ? moment(data?.created_at).format('yyyy/MM/DD HH:mm:ss') : "" }}
                        </p>
                    </li>
                    <li>
                        <p class="system-values-label">更新日時</p>
                        <p class="system-values-item">
                            {{ data?.updated_at ? moment(data?.updated_at).format('yyyy/MM/DD HH:mm:ss') : "" }}
                        </p>
                    </li>
                </ul>
            </div>
            <hr class="pb-4">
            <slot name="default" />
            <div>
                <hr>
                <div class="my-4 text-end btns max-w-7xl">
                    <div class="flex items-center justify-end gap-2">
                        <div class="">
                            <RadioButton inputId="list" name="redirectOption" v-model="redirectOption" :value="1" />
                            <label for="list" class="pl-2">一覧</label>
                        </div>
                        <div>
                            <RadioButton inputId="show" name="redirectOption" v-model="redirectOption" :value="2" />
                            <label for="show" class="pl-2">表示</label>
                        </div>
                        <div>
                            <RadioButton inputId="create" name="redirectOption" v-model="redirectOption" :value="3" />
                            <label for="create" class="pl-2">新規作成</label>
                        </div>
                        <div class="pl-3">
                            <Button type="submit" icon="pi pi-save" label="保存" severity="success" />
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <Dialog v-model:visible="visibleRemoveDialog" modal dismissable-mask :draggable="false" class="w-96">
        <template #header>
            <span class="text-lg font-bold text-red-600">削除しますか？</span>
        </template>
        <div class="w-full text-center">
            <i class="text-5xl text-red-500 pi pi-info-circle"></i>
            <p class="text-xl font-bold text-red-500">本当に削除しますか？</p>
            <div class="flex items-center justify-center w-full gap-4 mt-4">
                <Button label="取り消し" class="w-24 shrink-0" severity="secondary" @click="visibleRemoveDialog = false" />
                <Button label="確認" class="w-24 shrink-0" severity="success" @click="removeRow" />
            </div>
        </div>
    </Dialog>
</template>