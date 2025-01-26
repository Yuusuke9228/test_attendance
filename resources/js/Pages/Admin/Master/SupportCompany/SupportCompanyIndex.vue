<script setup>
import CustomToast from '@/Components/CustomToast.vue';
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import moment from 'moment'

const props = defineProps({
	supportCompany: Object
})

const toast = useToast();
const supportCompanyData = ref([]);
const count = ref(0);
const visibleRemoveDialog = ref(false);

onMounted(() => {
	if (props.supportCompany) {
		supportCompanyData.value = props.supportCompany.data.map((item) => {
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
	deleteForm.delete(route('admin.master.support_company.destroy'), {
		onSuccess: () => {
			toastBack.value = 'bg-green-500/70';
			toast.add({
				severity: 'custom',
				summary: '削除成功！',
				life: 2000,
				group: 'headless'
			})
			supportCompanyData.value = props.supportCompany.data.filter(filter => filter.id !== deleteForm.id)
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
	cop: urlParams.get('cop'),
	boss: urlParams.get('boss'),
	mail: urlParams.get('mail'),
	tel: urlParams.get('tel'),
	zip: urlParams.get('zip'),
	addr: urlParams.get('addr'),
	visible: 1,
})
const filterAction = () => {
	form.get(route('admin.master.support_company.index'));
}
</script>
<template>
	<CustomToast group="headless" :bgClass="toastBack" />
	<AdminLayout title="応援に来てもらう会社">
		<MainContent>
			<template #header>
				<FontAwesomeIcon icon="fa-regular fa-thumbs-up" />
				<h3>
					応援に来てもらう会社
					<small>応援に来てもらう会社の情報を管理します。</small>
				</h3>
			</template>
			<MasterContentBox link="support_company"
				@filter="(e) => e == 'detail' ? detailFilter = true : detailFilter = false">
				<div v-if="detailFilter"
					class="w-full detail__search grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 2xl:grid-cols-6 gap-y-2 gap-x-4 mb-2 p-2 border border-sky-400 rounded-md bg-gray-200">
					<div class="w-full schedule-id">
						<InputNumber v-model="form.id" :use-grouping="false" input-class="py-1.5 w-full" class="w-full"
							placeholder="IDで検索"  @keyup.enter="filterAction"/>
					</div>
					<div class="corporate">
						<InputText v-model="form.cop" class="py-1.5 w-full" placeholder="会社名で検索" @keyup.enter="filterAction"/>
					</div>
					<div class="boss_name">
						<InputText v-model="form.boss" class="py-1.5 w-full" placeholder="担当者名で検索" @keyup.enter="filterAction"/>
					</div>
					<div class="email_address">
						<InputText v-model="form.mail" class="py-1.5 w-full" placeholder="メールで検索" @keyup.enter="filterAction"/>
					</div>
					<div class="tel-number">
						<InputText v-model="form.tel" class="py-1.5 w-full" placeholder="電話番号で検索" @keyup.enter="filterAction"/>
					</div>
					<div class="post_code">
						<InputText v-model="form.zip" class="py-1.5 w-full" placeholder="郵便番号で検索" @keyup.enter="filterAction"/>
					</div>
					<div class="address">
						<InputText v-model="form.addr" class="py-1.5 w-full" placeholder="住所で検索" @keyup.enter="filterAction"/>
					</div>
					<div class="filter-btn md:col-span-4 lg:col-span-1">
						<Button severity="danger" label="検索" icon="pi pi-search" class="w-full" size="small" @click="filterAction" />
					</div>
				</div>
				<div class="w-full border datatable center">
					<DataTable :value="supportCompanyData" data-key="id" selectionMode="multiple" class="p-datatable-sm">
						<Column field="id" header="ID" sortable />
						<Column field="support_company_name" header="会社名" sortable />
						<Column field="support_company_person" header="担当者" sortable />
						<Column field="support_company_email" header="メール" sortable />
						<Column field="support_company_tel" header="電話" sortable />
						<Column field="support_company_zipcode" header="郵便番号" sortable />
						<Column field="support_company_address" header="住所" sortable />
						<Column field="created_at" header="作成日時" sortable />
						<Column field="updated_at" header="更新日時" sortable />
						<Column header="操作">
							<template #body="slotProps">
								<div class="flex items-center justify-center gap-3">
									<Link :href="route('admin.master.support_company.show', { id: slotProps.data.id })">
									<FontAwesomeIcon icon="fa-solid fa-eye" class="text-sky-500" />
									</Link>
									<Link :href="route('admin.master.support_company.edit', { id: slotProps.data.id })">
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
			<LinkPagination :data="supportCompany" />
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
	</AdminLayout></template>