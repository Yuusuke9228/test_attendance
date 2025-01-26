<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    locationDetail: Object,
})
</script>
<template>
    <AdminLayout title="現場管理">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-solid fa-location-dot" />
                <h3>
                    現場管理
                    <small>打刻場所を選択するマスターです。 有効フラグが立っている現場のみ表示されます。</small>
                </h3>
            </template>
            <MasterDetailBox link="location" :data="locationDetail">
                <div class="w-full center">
                    <div class="relative flex flex-col m-auto max-w-7xl main-info">
                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>現場名</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ locationDetail?.location_name }}</p>
                                </div>
                            </div>

                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>住所</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ locationDetail?.location_address }}</p>
                                </div>
                            </div>

                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>有効無効</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ locationDetail?.location_flag ? '有効' : '無効' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </MasterDetailBox>
        </MainContent>
    </AdminLayout>
</template>