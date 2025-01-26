<script setup>
import CustomToast from '@/Components/CustomToast.vue';
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import moment from 'moment'

const props = defineProps({
    schedule: Object,
    usersList: Object,
    occupations: Object,
    locations: Object,
})

const toast = useToast();
const scheduleData = ref([]);
const visibleRemoveDialog = ref(false);
const toastBack = ref();

const deleteForm = useForm({
    id: null,
});

onMounted(() => {
    if (props.schedule) {
        scheduleData.value = props.schedule.data.map((item) => {
            let users = "";
            JSON.parse(item?.user_id).map((item) => {
                users += props.usersList.filter(filter => filter.id == item)[0].name + "、";
            })
            item.created_at = moment(item.created_at).format('yyyy-MM-DD HH:mm:ss');
            item.updated_at = moment(item.updated_at).format('yyyy-MM-DD HH:mm:ss')
            item.users = users !== "" ? users.slice(0, -1) : "";
            return item;
        });
    }
})

const deleteRowVisible = (id) => {
    deleteForm.id = id;
    visibleRemoveDialog.value = true;
}
const destroyData = () => {
    deleteForm.delete(route('admin.master.schedule.destroy'), {
        onSuccess: () => {
            toastBack.value = 'bg-green-500/70';
            toast.add({
                severity: 'custom',
                summary: '削除成功！',
                life: 2000,
                group: 'headless'
            })
            scheduleData.value = scheduleData.value.filter(filter => filter.id !== deleteForm.id)
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
    visibleRemoveDialog.value = false
}

// Detail Filter
const urlParams = new URLSearchParams(window.location.search);
const detailFilter = ref(urlParams.get('visible') == 1 ? true : false);
const form = useForm({
    id: urlParams.get('id') ? parseInt(urlParams.get('id')) : null,
    sdate: urlParams.get('sdate') ?? null,
    cdate: urlParams.get('cdate'),
    occp: urlParams.get('occp') ? parseInt(urlParams.get('occp')) : null,
    loc: urlParams.get('loc') ? parseInt(urlParams.get('loc')) : null,
    user: urlParams.get('user') ? parseInt(urlParams.get('user')) : null,
    start: urlParams.get('start'),
    close: urlParams.get('close'),
    visible: 1,
})
const filterAction = () => {
    form.get(route('admin.master.schedule.index'));
}
</script>
<template>
    <CustomToast group="headless" :bgClass="toastBack" />
    <AdminLayout title="作業予定管理">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-regular fa-calendar-check" />
                <h3>
                    作業予定管理
                    <small>現場毎の作業者の予定を管理します。 従業員が、指定日の何時から何時まで対象の現場で作業する予定かを管理します。 打刻場所との整合性を確認する為に使用します。</small>
                </h3>
            </template>
            <MasterContentBox link="schedule" @filter="(e) => e == 'detail' ? detailFilter = true : detailFilter = false">
                <div v-if="detailFilter"
                    class="w-full detail__search grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-8 gap-y-2 gap-x-4 mb-2 p-2 border border-sky-400 rounded-md bg-gray-200">
                    <div class="schedule-id">
                        <InputNumber v-model="form.id" :use-grouping="false" input-class="py-1.5 w-full" class="w-full" placeholder="IDで検索" />
                    </div>
                    <div class="target-sdate">
                        <VueDatePicker v-model="form.sdate" locale="ja" format="yyyy/M/d" modelType="yyyy/MM/dd"
                            :enable-time-picker="false" placeholder="打刻開始日" autoApply class="!w-full" position="left" />
                    </div>
                    <div class="target-cdate">
                        <VueDatePicker v-model="form.cdate" locale="ja" format="yyyy/M/d" modelType="yyyy/MM/dd"
                            :enable-time-picker="false" placeholder="打刻終了日" autoApply class="!w-full" position="left" />
                    </div>
                    <div class="syukkin_time">
                        <VueDatePicker v-model="form.start" locale="ja" format="HH:mm" modelType="HH:mm:ss" timePicker
                            placeholder="開始予定時刻" autoApply class="!w-full">
                            <template #input-icon>
                                <i class="pi pi-clock pl-2 pt-1"></i>
                            </template>
                        </VueDatePicker>
                    </div>
                    <div class="taikin_time">
                        <VueDatePicker v-model="form.close" locale="ja" format="HH:mm" modelType="HH:mm:ss" timePicker
                            placeholder="終了予定時刻" autoApply class="!w-full">
                            <template #input-icon>
                                <i class="pi pi-clock pl-2 pt-1"></i>
                            </template>
                        </VueDatePicker>
                    </div>
                    <div class="user">
                        <Dropdown v-model="form.user" :options="usersList" optionLabel="name" optionValue="id"
                            input-class="py-1.5" scroll-height="600px" placeholder="ユーザーを選択" class="w-full" show-clear />
                    </div>
                    <div class="occupation">
                        <Dropdown v-model="form.occp" :options="occupations" optionLabel="occupation_name" optionValue="id"
                            scroll-height="600px" input-class="py-1.5" placeholder="職種を選択" class="w-full" show-clear />
                    </div>
                    <div class="locations">
                        <Dropdown v-model="form.loc" :options="locations" optionLabel="location_name" optionValue="id"
                            scroll-height="600px" input-class="py-1.5" placeholder="現場を選択" class="w-full" show-clear />
                    </div>
                    <div class="filter-btn md:col-span-1 lg:col-span-4 2xl:col-span-8">
                        <Button severity="danger" label="検索" icon="pi pi-search" class="w-full" size="small"
                            @click="filterAction" />
                    </div>
                </div>
                <div class="w-full border datatable center">
                    <DataTable :value="scheduleData" data-key="id" selectionMode="multiple"
                        class="p-datatable-sm">
                        <Column field="id" header="ID" sortable />
                        <Column field="schedule_date" header="日付" sortable class="whitespace-nowrap" />
                        <Column field="occupations.occupation_name" header="職種" sortable class="whitespace-nowrap" />
                        <Column field="locations.location_name" header="現場" sortable class="whitespace-nowrap" />
                        <Column field="users" header="従業員" sortable />
                        <Column field="schedule_start_time" header="開始予定時刻" sortable />
                        <Column field="schedule_end_time" header="終了予定時刻" sortable />
                        <Column field="created_at" header="作成日時" sortable />
                        <Column field="updated_at" header="更新日時" sortable />
                        <Column header="操作">
                            <template #body="slotProps">
                                <div class="flex items-center justify-center gap-3">
                                    <Link :href="route('admin.master.schedule.show', { id: slotProps.data.id })">
                                    <FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
                                    </Link>
                                    <Link :href="route('admin.master.schedule.edit', { id: slotProps.data.id })">
                                    <FontAwesomeIcon icon="fa-solid fa-pen-to-square" class="text-teal-500" />
                                    </Link>
                                    <FontAwesomeIcon icon="fa-solid fa-trash-can" class="text-rose-500"
                                        @click="deleteRowVisible(slotProps.data.id)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </MasterContentBox>
        </MainContent>
        <div class="flex items-center justify-center px-6 mt-6">
            <LinkPagination :data="schedule" />
        </div>

        <!-- Dialog to confirmation for removing user -->
        <Dialog v-model:visible="visibleRemoveDialog" modal dismissable-mask :draggable="false" class="w-96">
            <template #header>
                <span class="text-lg font-bold text-red-600">削除しますか？</span>
            </template>
            <div class="w-full text-center">
                <i class="text-5xl text-red-500 pi pi-info-circle"></i>
                <p class="text-xl font-bold text-red-500">削除しますか？</p>
                <div class="flex items-center justify-center w-full gap-4 mt-4">
                    <Button label="いいえ" class="w-24 shrink-0" severity="secondary" @click="visibleRemoveDialog = false" />
                    <Button label="はい" class="w-24 shrink-0" severity="success" @click="destroyData" />
                </div>
            </div>
        </Dialog>
    </AdminLayout>
</template>