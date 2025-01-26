<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ref, reactive, onMounted, computed, watch } from 'vue';
import { master } from '@/Utils/action';
import { useToast } from 'primevue/usetoast';
import axios from 'axios'
import moment from 'moment'
import CustomToast from '@/Components/CustomToast.vue'
import Loader from '@/Components/Loader.vue';

const props = defineProps({
    user: Object,
    dakoku: Object,
})

const breakTimeDetailVisible = ref(false);
const removeConfirm = ref(false);
const removeCurrentUser = () => {

}

const userInfo = computed(() => {
    if (props.user) {
        return props.user
    }
});

const dakokuData = computed(() => {
    if (props.dakoku) {
        return props.dakoku;
    }
})
</script>

<template>
    <AdminLayout title="ユーザー詳細情報">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-solid fa-users" />
                <h3>ユーザー管理</h3>
            </template>
            <ContentBox>
                <template #header>
                    <div class="flex items-center justify-between w-full">
                        <h3>
                            <FontAwesomeIcon icon="fa-solid fa-user" />
                            ユーザー詳細情報
                        </h3>
                        <div class="flex items-center gap-2 btn_broup">
                            <Link :href="route('admin.users.create')">
                            <Button label="新規登録" icon="pi pi-user-plus" size="small" class="py-1" />
                            </Link>
                            <Link :href="route('admin.users.index')">
                            <Button label="ユーザー一覧" icon="pi pi-users" severity="help" size="small" class="py-1" />
                            </Link>
                            <Link :href="route('admin.attendance.index')">
                            <Button label="打刻一覧" icon="pi pi-list" severity="info" size="small" class="py-1" />
                            </Link>
                            <Link :href="route('admin.users.edit', { id: userInfo?.id })">
                            <Button label="情報変更" icon="pi pi-user-edit" severity="success" size="small" class="py-1" />
                            </Link>
                            <Button label="削除" icon="pi pi-trash" severity="danger" size="small" class="py-1"
                                @click="removeConfirm = true" />
                        </div>
                    </div>
                </template>
                <div class="w-full">
                    <div class="p-3 system-values">
                        <ul class="flex items-stretch justify-center system-values-list">
                            <li>
                                <p class="system-values-label">ID</p>
                                <p class="system-values-item">{{ userInfo?.id }}</p>
                            </li>
                            <li>
                                <p class="system-values-label">作成ユーザー</p>
                                <p class="system-values-item">{{ userInfo?.update_history[0]?.creater?.name }}</p>
                            </li>
                            <li>
                                <p class="system-values-label">更新ユーザー</p>
                                <p class="system-values-item">{{ userInfo?.update_history[0]?.updater?.name }}</p>
                            </li>
                            <li>
                                <p class="system-values-label">作成日時</p>
                                <p class="system-values-item">{{ moment(userInfo?.created_at).format('yyyy/MM/DD HH:mm:ss')
                                }}</p>
                            </li>
                            <li>
                                <p class="system-values-label">更新日時</p>
                                <p class="system-values-item">{{ moment(userInfo?.updated_at).format('yyyy/MM/DD HH:mm:ss')
                                }}</p>
                            </li>
                        </ul>
                    </div>
                    <hr class="my-4">
                    <div class="relative flex flex-col m-auto max-w-7xl main-info">
                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>ユーザーコード</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ userInfo.code }}</p>
                                </div>
                            </div>

                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>ユーザー名</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ userInfo.name }}</p>
                                </div>
                            </div>

                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>メールアドレス</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ userInfo.email }}</p>
                                </div>
                            </div>

                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>勤務形態コード</p>
                                </div>
                                <div class="input-field">
                                    <p class="font-bold underline cursor-pointer text-sky-600" @click="breakTimeDetailVisible = true">
                                        {{ userInfo?.user_data?.break_times?.break_work_pattern_cd }}
                                        {{ userInfo?.user_data?.break_times?.organization?.organization_name }}
                                        {{ userInfo?.user_data?.break_times?.break_name }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </ContentBox>
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <ContentBox class="mt-8">
                    <template #header>
                        <div class="flex items-center justify-between w-full">
                            <h3>
                                <FontAwesomeIcon icon="fa-solid fa-clock-rotate-left" />
                                更新履歴
                            </h3>
                        </div>
                    </template>
                    <div class="w-full px-4 py-2 max-h-[300px] overflow-y-auto">
                        <div v-if="userInfo.update_history.length > 0" class="updating-history">
                            <div v-for="(item, index) in userInfo.update_history" :key="index">
                                <p class="flex items-end gap-2">
                                    <span class="text-lg font-bold">No. {{ index + 1 }}</span>
                                    <span>{{ moment(item.updated_at).format('yyyy-MM-DD HH:mm:ss') }}</span>
                                    <span>(更新ユーザー : {{ item?.updater?.name }}) </span>
                                </p>
                            </div>
                        </div>
                        <div v-else>
                            <p>変更履歴はありません。</p>
                        </div>
                    </div>
                </ContentBox>
                <ContentBox class="mt-8">
                    <template #header>
                        <div class="flex items-center justify-between w-full">
                            <h3>
                                <FontAwesomeIcon icon="fa-solid fa-building-circle-check" />
                                出勤履歴
                            </h3>
                        </div>
                    </template>
                    <div class="w-full px-4 py-2 max-h-[300px] overflow-y-auto">
                        <div v-if="dakokuData.length > 0" class="updating-history">
                            <div v-for="(item, index) in dakokuData" :key="index">
                                <p class="flex items-end gap-2">
                                    <span>{{ index + 1 }}.</span>
                                    <span>{{ moment(item.target_date).format('yyyy-MM-DD') }}</span>
                                    <span>{{ item.attend_type.attend_type_name }}</span>
                                    <span>{{ item.dp_ride_flg }}</span>
                                    <span>{{ moment(item.attend_type.created_at).format('HH:mm:ss') }}</span>
                                    <span>{{ item.dp_support_flg == 1 ? '応援来てもらった先' : item.dp_support_flg == 2 ? '応援に行った先' :
                                        'なし' }}</span>
                                    <span v-if="item.dp_support_flg == 1">（{{ item.support_company?.support_company_name
                                    }}）</span>
                                    <span v-if="item.dp_support_flg == 2">（{{ item.supported_company?.supported_company_name
                                    }}）</span>
                                </p>
                            </div>
                        </div>
                        <div v-else>
                            <p>出勤履歴はありません。</p>
                        </div>
                    </div>
                </ContentBox>
            </div>
        </MainContent>
    </AdminLayout>
    <!-- Dialog to confirmation for removing user -->
    <Dialog v-model:visible="removeConfirm" modal dismissable-mask :draggable="false" class="w-96">
        <template #header>
            <span class="text-lg font-bold text-red-600">削除しますか？</span>
        </template>
        <div class="w-full text-center">
            <i class="text-5xl text-red-500 pi pi-info-circle"></i>
            <div class="flex items-center justify-center w-full gap-4 mt-4">
                <Button label="いいえ" class="w-24 shrink-0" severity="secondary" @click="removeConfirm = false" />
                <Button label="はい" class="w-24 shrink-0" severity="success" @click="removeCurrentUser" />
            </div>
        </div>
    </Dialog>
    <!-- breaktime detail dialog -->
    <Dialog v-model:visible="breakTimeDetailVisible" modal dismissable-mask :draggable="false" header="勤務形態コード詳細" class="w-full max-w-lg">
        <div class="w-full">
            <table class="w-full table-auto">
                <tbody>
                    <tr>
                        <th>ID</th>
                        <td>{{userInfo?.user_data?.break_times?.id}}</td>
                    </tr>
                    <tr>
                        <th>勤務形態コード</th>
                        <td>{{userInfo?.user_data?.break_times?.break_work_pattern_cd}}</td>
                    </tr>
                    <tr>
                        <th>組織</th>
                        <td>{{userInfo?.user_data?.break_times?.organization?.organization_name}}</td>
                    </tr>
                    <tr>
                        <th>管理名</th>
                        <td>{{userInfo?.user_data?.break_times?.break_name}}</td>
                    </tr>
                    <tr>
                        <th>勤務開始時刻</th>
                        <td>{{userInfo?.user_data?.break_times?.break_start_time}}</td>
                    </tr>
                    <tr>
                        <th>勤務終了時刻</th>
                        <td>{{userInfo?.user_data?.break_times?.break_end_time}}</td>
                    </tr>
                    <tr>
                        <th>休憩開始時刻１</th>
                        <td>{{userInfo?.user_data?.break_times?.break_start_time1}}</td>
                    </tr>
                    <tr>
                        <th>休憩終了時刻１</th>
                        <td>{{userInfo?.user_data?.break_times?.break_end_time1}}</td>
                    </tr>
                    <tr>
                        <th>休憩開始時刻２</th>
                        <td>{{userInfo?.user_data?.break_times?.break_start_time2}}</td>
                    </tr>
                    <tr>
                        <th>休憩終了時刻２</th>
                        <td>{{userInfo?.user_data?.break_times?.break_end_time2}}</td>
                    </tr>
                    <tr>
                        <th>休憩開始時刻３</th>
                        <td>{{userInfo?.user_data?.break_times?.break_start_time3}}</td>
                    </tr>
                    <tr>
                        <th>休憩終了時刻３</th>
                        <td>{{userInfo?.user_data?.break_times?.break_end_time3}}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </Dialog>
</template>

<style lang="scss"></style>