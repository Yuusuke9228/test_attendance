<script setup>
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>
<template>
    <AdminLayout title="ダッシュボード">
        <div class="w-full dashboard-page">
            <div class="header-title">
                <FontAwesomeIcon icon="fa-solid fa-house" />
                <h1>ダッシュボード </h1>
            </div>
            <div class="grid grid-cols-2 gap-8 mt-4 content-field">
                <div class="col-span-2 recently-attendance-history field-box">
                    <div class="box-header">
                        <h3 class="box-title">打刻</h3>
                    </div>
                    <div class="box-content">
                    </div>
                </div>
                <div class="w-full field-box">
                    <div class="box-header">
                        <h3 class="box-title">打刻</h3>
                    </div>
                    <div class="box-content">
                    </div>
                </div>
                <div class="w-full field-box">
                    <div class="box-header">
                        <h3 class="box-title">打刻</h3>
                    </div>
                    <div class="box-content">
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
<style lang="scss" scoped>
.box-content {
    min-height: 15vh;
}
</style>