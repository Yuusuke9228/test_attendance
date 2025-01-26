<script setup>
import { ref, computed } from "vue";
import { getParams } from "@/Utils/action";

const props = defineProps({
  data: Object,
});
const currentPage = computed(() => {
  return props.data.current_page;
});
const lastPage = computed(() => {
  return props.data.last_page;
});
const links = computed(() => {
  return props.data.links.map((item) => {
    if (item.label.toString().includes('前へ')) {
      item.label = '前へ'
      if (item.url != null) {
        item.value = currentPage.value - 1;
      }
    }

    if (item.label.toString().includes('次へ')) {
      item.label = '次へ'
      if (item.url != null) {
        item.value = currentPage.value + 1;
      }
    }
    if (!item.label.toString().includes('前へ') && !item.label.toString().includes('次へ')) {
      item.value = item.label
    }
    return item;
  })
})
</script>
<template>
  <div class="flex items-center justify-end gap-4">
    <div v-if="data && data.last_page > 1" class="flex items-center justify-end">
      <ul class="flex items-center overflow-hidden text-gray-700 border border-green-500 rounded-md">
        <!-- previous link &laquo;  -->
        <li v-for="(item, index) in links">
          <Button :label="item.label" :outlined="currentPage != item.value" :disabled="item.label == '...'"
            class="w-10 h-10 p-0 border-t-0 border-b-0 border-l-0 rounded-none p-button-sm shrink-0" severity="success"
            :class="{ 'bg-gray-300': item.label == '...' }" @click="$emit('pageAction', item.value)" />
        </li>
      </ul>
    </div>
  </div>
</template>
<style lang="scss" scoped>
ul {
  li {
    display: flex;
    align-items: center;
  }
}

.p-button {
  flex-shrink: 0;
}
</style>
