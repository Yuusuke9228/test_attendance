<script setup>
import CustomToast from '@/Components/CustomToast.vue';
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import moment from 'moment';

const props = defineProps({
	breakTimes: Object
})

const toast = useToast();
const breakTimeData = ref([]);
const count = ref(0);
const visibleRemoveDialog = ref(false);

onMounted(() => {
	if (props.breakTimes) {
		breakTimeData.value = props.breakTimes.data.map((item) => {
			item.updated_at = moment(item.updated_at).format('yyyy-MM-DD HH:mm:ss')
			return item;
		});
	}
})

const deleteForm = useForm({
	id: null
})
const deleteRowVisible = (id) => {
	deleteForm.id = id;
	visibleRemoveDialog.value = true;
}
const toastBack = ref();
const removeData = () => {
	deleteForm.delete(route('admin.master.breaktime.destroy'), {
		onSuccess: () => {
			toastBack.value = 'bg-green-500/70';
			toast.add({
				severity: 'custom',
				summary: '削除成功！',
				life: 2000,
				group: 'headless'
			});
			breakTimeData.value = props.breakTimes.data.filter(filter => filter.id !== deleteForm.id)
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
	code: urlParams.get('code'),
	org: urlParams.get('org'),
	mgn: urlParams.get('mgn'),
	visible: 1, //detail Filter mode visible
})

const filterAction = () => {
	form.get(route('admin.master.breaktime.index'));
}
</script>
<template>
	<CustomToast group="headless" :bgClass="toastBack" />
	<AdminLayout title="休憩時間・勤務形態管理 ">
		<MainContent>
			<template #header>
				<FontAwesomeIcon icon="fa-solid fa-bookmark" />
				<h3>
					休憩時間・勤務形態管理
					<small>勤務形態毎の休憩時間を管理します。</small>
				</h3>
			</template>
			<MasterContentBox link="breaktime" @filter="(e) => e == 'detail' ? detailFilter = true : detailFilter = false">
				<div v-if="detailFilter"
					class="w-full detail__search grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 2xl:grid-cols-8 gap-y-2 gap-x-4 mb-2 p-2 border border-sky-400 rounded-md bg-gray-200">
					<div class="schedule-id">
						<InputNumber v-model="form.id" :use-grouping="false" input-class="py-1.5 w-full" class="w-full"
							placeholder="IDで検索" />
					</div>
					<div class="break_code">
						<InputText v-model="form.code" class="py-1.5 w-full" placeholder="勤務形態コードで検索" show-clear />
					</div>
					<div class="organization">
						<InputText v-model="form.org" class="py-1.5 w-full" placeholder="組織名で検索" />
					</div>
					<div class="manager">
						<InputText v-model="form.mgn" class="py-1.5 w-full" placeholder="管理名で検索" />
					</div>
					<div class="filter-btn md:col-span-4 lg:col-span-1">
						<Button severity="danger" label="検索" icon="pi pi-search" class="w-full" size="small" @click="filterAction" />
					</div>
				</div>
				<div class="w-full overflow-auto border realtive datatable center">
					<DataTable :value="breakTimeData" data-key="id" selectionMode="multiple" class="p-datatable-sm">
						<Column field="id" header="ID" sortable />
						<Column field="break_work_pattern_cd" header="勤務形態コード" sortable />
						<Column field="organization.organization_name" header="組織" sortable class="whitespace-nowrap" />
						<Column field="break_name" header="管理名" sortable />
						<Column field="break_start_time" header="勤務開始時刻" sortable />
						<Column field="break_end_time" header="勤務終了時刻" sortable />
						<Column field="break_start_time1" header="休憩開始時刻１" sortable />
						<Column field="break_end_time1" header="休憩終了時刻１" sortable />
						<Column field="break_start_time2" header="休憩開始時刻２" sortable />
						<Column field="break_end_time2" header="休憩終了時刻２" sortable />
						<Column field="break_start_time3" header="休憩開始時刻３" sortable />
						<Column field="break_end_time3" header="休憩終了時刻３" sortable />
						<Column field="updated_at" header="更新日時" sortable bodyClass="whitespace-nowrap" />
						<Column header="操作">
							<template #body="slotProps">
								<div class="flex items-center justify-center gap-3">
									<Link :href="route('admin.master.breaktime.show', { id: slotProps.data.id })">
									<FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
									</Link>
									<Link :href="route('admin.master.breaktime.edit', { id: slotProps.data.id })">
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
			<LinkPagination :data="breakTimes" />
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