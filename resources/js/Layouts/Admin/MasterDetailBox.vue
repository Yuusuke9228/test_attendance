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
    contentClass:String
})

const visibleRemoveDialog = ref(false);

const deleteForm = useForm({
    id: props.data?.id
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
    <div class="field-box" :class="contentClass">
        <div class="w-full box-header">
            <div class="flex items-center justify-between">
                <h4>{{ title ?? '詳細' }}</h4>
                <div v-if="data" class="flex gap-2">
                    <Link :href="link ? route(`admin.master.${link}.create`) : '#'">
                    <Button label="新規" icon="pi pi-plus" class="py-1" size="small" severity="success" />
                    </Link>
                    <Link :href="link ? route(`admin.master.${link}.index`) : '#'">
                    <Button label="一覧" icon="pi pi-list" class="py-1" size="small" severity="secondary" />
                    </Link>
                    <Link :href="link ? route(`admin.master.${link}.edit`, { id: data.id }) : '#'">
                    <Button label="編集" icon="pi pi-file-edit" class="py-1" size="small" severity="info" />
                    </Link>
                    <Button label="削除" icon="pi pi-trash" class="py-1" size="small" severity="danger"
                        @click="visibleRemoveDialog = true" />
                </div>
            </div>
        </div>
        <div class="box-content">
            <div v-if="data" class="p-3 system-values">
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
        </div>
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