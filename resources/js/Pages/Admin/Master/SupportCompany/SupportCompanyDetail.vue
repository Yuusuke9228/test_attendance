<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    supportCompanyDetail: Object,
})
</script>
<template>
    <AdminLayout title="応援に来てもらう会社">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-regular fa-thumbs-up" />
                <h3>
                    応援に来てもらう会社
                    <small>応援に来てもらう会社の情報を管理します。</small>
                </h3>
            </template>
            <MasterDetailBox link="support_company" :data="supportCompanyDetail">
                <div class="w-full center input-form">
                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="会社名" />
                        </div>
                        <div class="input-field">
                            {{ supportCompanyDetail.support_company_name }}
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="担当者" />
                        </div>
                        <div class="input-field">
                            {{ supportCompanyDetail?.support_company_person }}
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="メール" />
                        </div>
                        <div class="input-field">
                            {{ supportCompanyDetail?.support_company_email }}
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="電話" />
                        </div>
                        <div class="input-field">
                            {{ supportCompanyDetail?.support_company_tel }}
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="郵便番号" />
                        </div>
                        <div class="input-field">
                            {{ supportCompanyDetail?.support_company_zipcode }}
                        </div>
                    </div>

                    <div class="my-4 form-inner center">
                        <div class="label-field label-right">
                            <InputLabel value="住所" />
                        </div>
                        <div class="input-field">
                            {{ supportCompanyDetail?.support_company_address }}
                        </div>
                    </div>
                </div>
            </MasterDetailBox>
        </MainContent>
    </AdminLayout>
</template>