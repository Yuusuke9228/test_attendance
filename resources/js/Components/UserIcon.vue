<script setup>
import {Asset} from '@/Assets'

const props = defineProps({
    imgClass: null,
})
</script>
<template>
    <img :src="$page.props.assetUrl + Asset.icon.userIcon" alt="user" class="select-none" :class="imgClass ?? 'w-10'">
</template>