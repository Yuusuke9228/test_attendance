<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    title: String,
    link: String,
})
const emit = defineEmits(['emitSubmit']);

const redirectOption = ref(1);
const submit = () => {
    emit("emitSubmit", redirectOption.value);
}
</script>
<template>
    <div class="field-box">
        <div class="w-full box-header">
            <div class="flex justify-between">
                <h4>{{ title ?? '作成' }}</h4>
                <Link :href="route('admin.master.' + link + '.index')">
                <Button label="一覧" icon="pi pi-list" class="py-1" size="small" severity="info" />
                </Link>
            </div>
        </div>
        <div class="box-content">
            <div class="w-full">
                <form @submit.prevent="submit" class="relative w-full input-form">
                    <slot name="default" />
                    <div>
                        <hr>
                        <div class="my-4 text-end btns max-w-7xl">
                            <div class="flex items-center justify-end gap-2">
                                <div class="">
                                    <RadioButton inputId="list" name="redirectOption" v-model="redirectOption" :value="1" />
                                    <label for="list" class="pl-2">一覧</label>
                                </div>
                                <div>
                                    <RadioButton inputId="show" name="redirectOption" v-model="redirectOption" :value="2" />
                                    <label for="show" class="pl-2">表示</label>
                                </div>
                                <div>
                                    <RadioButton inputId="create" name="redirectOption" v-model="redirectOption" :value="3" />
                                    <label for="create" class="pl-2">新規作成</label>
                                </div>
                                <div class="pl-3">
                                    <Button type="submit" icon="pi pi-save" label="保存" severity="success" />
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>