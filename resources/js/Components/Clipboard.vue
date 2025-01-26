<script setup>
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    value: String
})

const toast = useToast();
const copyStatus = ref(false);
const copyClipBoard = () => {
    const textarea = document.createElement('textarea');
    textarea.value = props.value;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    copyStatus.value = true;
    document.body.removeChild(textarea);
    toast.add({
        severity: 'custom',
        summary: 'クリップボードにコピーされました。',
        group: 'headless',
        life: 2000
    })
    setTimeout(() => {
        copyStatus.value = false;
    }, 2000);
}
</script>
<template>
    <div class="card flex justify-content-center">
        <Toast position="top-center" group="headless" @close="copyStatus = false">
            <template #container="{ message, closeCallback }">
                <section class="flex p-3 gap-3 w-full bg-green-600/90 shadow-2 rounded-lg">
                    <div class="flex justify-center w-full text-center">
                        <p class="text-center text-white">{{ message.summary }}</p>
                    </div>
                </section>
            </template>
        </Toast>
    </div>
    <div class="relative">
        <Chip class="px-8 py-1 bg-sky-200 relative group" @click="copyClipBoard">
            {{ value }}
            <span class="cursor-pointer absolute right-1 group-hover:block" :class="copyStatus ? 'block' : 'hidden'">
                <i class="pi p-1 bg-white/60 rounded-full"
                    :class="copyStatus ? 'pi-check-circle text-green-500' : 'pi-copy text-rose-500'" />
            </span>
        </Chip>
    </div>
</template>