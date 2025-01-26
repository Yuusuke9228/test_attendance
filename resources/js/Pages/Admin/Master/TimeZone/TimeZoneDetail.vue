<script setup>
const props = defineProps({
    timezone: Object,
})
</script>
<template>
    <AdminLayout title="時間帯区分">
        <MainContent>
            <template #header>
                <FontAwesomeIcon icon="fa-solid fa-clock" />
                <h3>
                    時間帯区分
                </h3>
            </template>
            <MasterDetailBox link="timezone" :data="timezone">
                <div class="w-full center">
                    <div class="relative flex flex-col m-auto max-w-7xl main-info">
                        <div class="pb-4 text-lg input-form">
                            <div class="mt-3 form-inner">
                                <div class="label-field">
                                    <p>時間帯</p>
                                </div>
                                <div class="input-field">
                                    <p>{{ timezone?.detail_times }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </MasterDetailBox>
        </MainContent>
    </AdminLayout>
</template>