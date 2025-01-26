<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    workContentsDetail: Object,
})
</script>
<template>
    <AdminLayout title="作業内容">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-solid fa-person-digging" />
                <h3>
                    作業内容
                    <small>職種で選択したものの中の作業項目を管理します</small>
                </h3>
            </template>
            <MasterDetailBox link="work_contents" :data="workContentsDetail">
                <div class="w-full center">
                    <div class="relative flex flex-col m-auto max-w-7xl main-info">
                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>職種管理</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ workContentsDetail.occupation?.occupation_name }}</p>
                                </div>
                            </div>

                            <div class="mt-4 form-inner">
                                <div class="label-field">
                                    <p>作業名</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ workContentsDetail.work_content_name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </MasterDetailBox>
        </MainContent>
    </AdminLayout>
</template>