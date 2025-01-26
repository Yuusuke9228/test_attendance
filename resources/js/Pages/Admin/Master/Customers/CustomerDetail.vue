<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    customerDetail: Object,
})
</script>
<template>
    <AdminLayout title="得意先">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-solid fa-crown" />
                <h3>
                    得意先
                    <small>得意先情報を管理します。</small>
                </h3>
            </template>
            <MasterDetailBox link="customer" :data="customerDetail">
                <div class="w-full center">
                    <div class="relative flex flex-col m-auto max-w-7xl main-info">
                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>会社名</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ customerDetail?.customer_name }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>担当者</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ customerDetail?.customer_person }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>敬称</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ customerDetail?.customer_saluation }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>メール</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ customerDetail?.customer_email }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>電話
</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ customerDetail?.customer_tel }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>ファックス番号</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ customerDetail?.customer_fax }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>郵便番号</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ customerDetail?.customer_zip_code }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>住所１</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ customerDetail?.customer_address_1 }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>住所２</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ customerDetail?.customer_address_2 }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>備考</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ customerDetail?.customer_memo }}</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </MasterDetailBox>
        </MainContent>
    </AdminLayout>
</template>