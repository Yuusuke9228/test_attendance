<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    attendStatus: Object,
})
</script>
<template>
    <AdminLayout title="残業・早退・遅刻">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-solid fa-user-clock" />
                <h3>
                    残業・早退・遅刻
                    <small>各種区分選択項目を管理します。</small>
                </h3>
            </template>
            <MasterDetailBox link="attend_statuses" :data="attendStatus">
                <div class="w-full center">
                    <div class="relative flex flex-col m-auto max-w-7xl main-info">
                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>選択肢表示名</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ attendStatus.attend_name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </MasterDetailBox>
        </MainContent>
    </AdminLayout>
</template>