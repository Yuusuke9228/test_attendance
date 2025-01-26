<script setup>
    const props = defineProps({
        value: String,
    })
</script>
<template>
        <div class="flex items-center gap-1 text-xs text-gray-500 mt-0.5">
            <i class="pi pi-info-circle text-gray-600 pr-1 text-xs"></i>
            <slot />
        </div>
</template>