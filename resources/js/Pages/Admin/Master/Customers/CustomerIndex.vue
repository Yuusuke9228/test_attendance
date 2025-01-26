<script setup>
import CustomToast from '@/Components/CustomToast.vue';
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import moment from 'moment';

const props = defineProps({
    customer: Object
})

const toast = useToast();
const customerData = ref([]);
const count = ref(0);
const visibleRemoveDialog = ref(false);

onMounted(() => {
    if (props.customer) {
        customerData.value = props.customer.data.map((item) => {
            item.created_at = moment(item.created_at).format('yyyy-MM-DD HH:mm:ss');
            item.updated_at = moment(item.updated_at).format('yyyy-MM-DD HH:mm:ss')
            return item;
        });
    }
})

const deleteForm = useForm({
    id: null
})
const deleteConfirmVisible = (id) => {
    deleteForm.id = id;
    visibleRemoveDialog.value = true;
}
const toastBack = ref();
const removeData = () => {
    deleteForm.delete(route('admin.master.customer.destroy'), {
        onSuccess: () => {
            toastBack.value = 'bg-green-500/70';
            toast.add({
                severity: 'custom',
                summary: '削除成功！',
                life: 2000,
                group: 'headless'
            })
            customerData.value = props.customer.data.filter(filter => filter.id !== deleteForm.id)
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
    visibleRemoveDialog.value = false
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
            <MasterContentBox link="customer">
                <div class="w-full border datatable center">
                    <DataTable :value="customerData" data-key="id" selectionMode="multiple" class="p-datatable-sm">
                        <Column field="id" header="ID" sortable />
                        <Column field="customer_name" header="会社名" sortable />
                        <Column field="customer_person" header="担当者" sortable />
                        <Column field="customer_saluation" header="敬称" sortable />
                        <Column field="customer_email" header="メール" sortable />
                        <Column field="customer_tel" header="電話" sortable />
                        <Column field="customer_fax" header="ファックス番号" sortable />
                        <Column field="customer_zip_code" header="郵便番号" sortable />
                        <Column field="customer_address_1" header="住所１" sortable />
                        <Column field="customer_address_2" header="住所２" sortable />
                        <Column field="customer_memo" header="メモ" sortable />
                        <Column field="created_at" header="作成日時" sortable />
                        <Column field="updated_at" header="更新日時" sortable />
                        <Column header="操作">
                            <template #body="slotProps">
                                <div class="flex items-center justify-center gap-3">
                                    <Link :href="route('admin.master.customer.show', { id: slotProps.data.id })">
                                    <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                                    </Link>
                                    <Link :href="route('admin.master.customer.edit', { id: slotProps.data.id })">
                                    <FontAwesomeIcon icon="fa-solid fa-pen-to-square" class="text-teal-500" />
                                    </Link>
                                    <FontAwesomeIcon icon="fa-solid fa-trash-can" class="text-rose-500"
                                        @click="deleteConfirmVisible(slotProps.data.id)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </MasterContentBox>
        </MainContent>
        <div class="flex items-center justify-center px-6 mt-6">
            <LinkPagination :data="customer" />
        </div>

        <!-- Dialog to confirmation for removing user -->
        <Dialog v-model:visible="visibleRemoveDialog" modal dismissable-mask :draggable="false" class="w-96">
            <template #header>
                <span class="text-lg font-bold text-red-600">削除しますか？</span>
            </template>
            <div class="w-full text-center">
                <i class="text-5xl text-red-500 pi pi-info-circle"></i>
                <p class="text-xl font-bold text-red-500">本当に削除しますか？</p>
                <div class="flex items-center justify-center w-full gap-4 mt-4">
                    <Button label="いいえ" class="w-24 shrink-0" severity="secondary" @click="visibleRemoveDialog = false" />
                    <Button label="はい" class="w-24 shrink-0" severity="success" @click="removeData" />
                </div>
            </div>
        </Dialog>
    </AdminLayout>
</template>