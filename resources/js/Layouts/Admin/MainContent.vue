<script setup>
import { useSlots } from "vue";
const props = defineProps({
  title: String,
  icon: String,
});
const slots = useSlots();
</script>
<template>
  <div class="main-content">
    <div class="header-title w-full">
      <div v-if="!slots.header" class="w-full flex items-center gap-2">
        <i class="pi" :class="icon"></i>
        <h1>{{ title }}</h1>
      </div>
      <div class="flex items-center w-full gap-2">
        <slot name="header" />
      </div>
    </div>
    <div class="mt-4">
      <slot name="default" />
    </div>
  </div>
</template>
