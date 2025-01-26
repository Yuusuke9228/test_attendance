<script setup>
import CustomToast from '@/Components/CustomToast.vue';
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import moment from 'moment';

const props = defineProps({
	workContent: Object
})

const toast = useToast();
const workContentData = ref([]);
const count = ref(0);
const visibleRemoveDialog = ref(false);

onMounted(() => {
	if (props.workContent) {
		workContentData.value = props.workContent.data.map((item) => {
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
	deleteForm.delete(route('admin.master.work_contents.destroy'), {
		onSuccess: () => {
			toastBack.value = 'bg-green-500/70';
			toast.add({
				severity: 'custom',
				summary: '削除成功！',
				life: 2000,
				group: 'headless'
			})
			workContentData.value = props.workContent.data.filter(filter => filter.id !== deleteForm.id)
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
	occp: urlParams.get('occp'),
	woc: urlParams.get('woc'),
	visible: 1,
})
const filterAction = () => {
	form.get(route('admin.master.work_contents.index'));
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
			<MasterContentBox link="work_contents" @filter="(e) => e == 'detail' ? detailFilter = true : detailFilter = false">
				<div v-if="detailFilter"
					class="w-full detail__search grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 2xl:grid-cols-6 gap-y-2 gap-x-4 mb-2 p-2 border border-sky-400 rounded-md bg-gray-200">
					<div class="schedule-id">
						<InputNumber v-model="form.id" :use-grouping="false" input-class="py-1.5 w-full" class="w-full"
							placeholder="IDで検索" />
					</div>
					<div class="occupation">
						<InputText v-model="form.occp" class="py-1.5 w-full" placeholder="職種名で検索" show-clear />
					</div>
					<div class="work_content">
						<InputText v-model="form.woc" class="py-1.5 w-full" placeholder="作業内容で検索" />
					</div>
					<div class="filter-btn md:col-span-4 lg:col-span-1">
						<Button severity="danger" label="検索" icon="pi pi-search" class="w-full" size="small" @click="filterAction" />
					</div>
				</div>
				<div class="w-full border datatable center">
					<DataTable :value="workContentData" data-key="id" selectionMode="multiple" class="p-datatable-sm">
						<Column field="id" header="ID" sortable />
						<Column field="occupation.occupation_name" header="職種管理" sortable />
						<Column field="work_content_name" header="作業名" sortable />
						<Column field="created_at" header="作成日時" sortable />
						<Column field="updated_at" header="更新日時" sortable />
						<Column header="操作">
							<template #body="slotProps">
								<div class="flex items-center justify-center gap-3">
									<Link :href="route('admin.master.work_contents.show', { id: slotProps.data.id })">
									<FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
									</Link>
									<Link :href="route('admin.master.work_contents.edit', { id: slotProps.data.id })">
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
			<LinkPagination :data="workContent" />
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