<script setup>
const props = defineProps({
    dakou_status: Object,
});

</script>
<template>
    <AuthenticatedLayout title="業務終了">
        <div class="w-full max-w-md p-0 m-auto md:p-6 md:max-w-7xl user-page__dashboard">
            <div class="p-4 bg-white rounded-lg shadow-lg md:p-12">
                <div class="mt-12">
                    <div>
                        <p class="mt-4 text-xl font-bold text-center text-red-500">本日の業務は終了しました。</p>
                    </div>
                    <div class="form-inner">
                        <div class="label-field label-right">
                            <InputLabel value="日付" essential />
                        </div>
                        <div class="input-field ">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-calendar"></i>
                                <InputText :value="dakou_status?.target_date" disabled class="w-full"/>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 form-inner">
                        <div class="label-field label-right">
                            <InputLabel value="出勤時間"  />
                        </div>
                        <div class="input-field ">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-clock"></i>
                                <InputText :value="dakou_status?.dp_syukkin_time" disabled class="w-full"/>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 form-inner">
                        <div class="label-field label-right">
                            <InputLabel value="退勤時間"  />
                        </div>
                        <div class="input-field ">
                            <div class="w-full p-input-icon-left">
                                <i class="pi pi-clock"></i>
                                <InputText :value="dakou_status?.dp_taikin_time" disabled class="w-full"/>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<style lang="scss" scoped>
.status-tab {
    position: relative;
    display: flex;
    justify-content: center;
    z-index:0;
    &::before{
        content: "";
        position: absolute;
        width: 10px;
        height: 10px;
        bottom: -9px;
        clip-path: polygon(0 0, 100% 0, 50% 100%)
    }
    &.syukin {
        &::before {
            background-color: #06b6d4;
        }
    }
    &.taikin {
        &::before {
            background-color: #ef4444;
        }
    }
}
.rest-status-tab {
    position: relative;
    display: flex;
    justify-content: center;
    z-index:0;
    &::before{
        content: "";
        position: absolute;
        width: 10px;
        height: 10px;
        top: -9px;
        background-color: #0d9488;
        clip-path: polygon(50% 0, 100% 100%, 0 100%);
    }
}
</style>