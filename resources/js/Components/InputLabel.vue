<script setup>
const props = defineProps({
  value: String,
  htmlFor: String,
  essential: Boolean,
});
</script>

<template>
  <label :for="htmlFor" class="flex items-center text-sm font-medium text-gray-700">
    <span v-if="value">{{ value }}</span>
    <span v-if="essential" class="text-red-600">*</span>
    <span v-else><slot /></span>
  </label>
</template>
