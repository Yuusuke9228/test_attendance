<script setup>
import { Asset } from "@/Assets";

const props = defineProps({
  small: Boolean,
});
</script>
<template>
  <img v-if="small" :src="$page.props.appUrl + Asset.logo_sp" alt="logo" />
  <img v-else :src="$page.props.appUrl + Asset.logo" alt="logo" />
</template>
