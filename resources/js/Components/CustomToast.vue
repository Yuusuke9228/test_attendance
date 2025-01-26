<script setup>
const props = defineProps({
    position: String,
    bgClass: String,
    summaryClass: String,
    detailClass: String
})
</script>
<template>
    <Toast :position="position ?? 'top-center'" >
        <template #container="{ message, closeCallback }">
            <section class="flex p-2 w-full shadow-2 rounded-lg overflow-hidden" :class="bgClass ?? 'bg-green-500/50'">
                <div class="flex justify-center flex-col w-full text-center">
                    <p v-if="message.summary" class="font-bold text-center text-white text-lg" :class="summaryClass">{{ message.summary }}</p>
                    <p v-if="message.detail" class="text-center text-white" :class="detailClass">{{ message.detail }}</p>
                </div>
            </section>
        </template>
    </Toast>
</template>