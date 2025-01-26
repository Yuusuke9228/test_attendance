<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    occupationDetail: Object,
})
</script>
<template>
    <AdminLayout title="職種管理">
        <MainContent>
            <template #header>
                <i class="pi pi-flag-fill"></i>
                <h3>
                    職種管理
                    <small>現場（大工・解体）/ 加工 など職種を管理します。</small>
                </h3>
            </template>
            <MasterDetailBox link="occupation" :data="occupationDetail">
                <div class="w-full center">
                    <div class="relative flex flex-col m-auto max-w-7xl main-info">
                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>職種名</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ occupationDetail.occupation_name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </MasterDetailBox>
        </MainContent>
    </AdminLayout>
</template>