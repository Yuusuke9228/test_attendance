<script setup>
import { useSlots } from "vue";

const props = defineProps({
  title: String,
  icon: String,
});
const slots = useSlots();
</script>
<template>
  <div class="w-full !overflow-visible field-box">
    <div class="box-header flex items-center gap-2 w-full">
      <i v-if="icon" class="pi" :class="icon"></i>
      <h3 v-if="!slots.header" class="box-title">{{ title }}</h3>
      <slot name="header" />
    </div>
    <div class="box-content !overflow-visible">
      <slot name="default" />
    </div>
  </div>
</template>
