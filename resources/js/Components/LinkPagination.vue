<script setup>
import { ref, computed, onMounted } from "vue";
import { getParams } from "@/Utils/action";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  data: Object,
});
const currentUrl = ref(location.href);

const links = computed(() => {
  if (props.data?.links) {
    return props.data?.links.map((item) => {
      if (item.label.includes("&raquo;")) {
        item.label = item.label.replace("次へ &raquo;", "»");
      }
      if (item.label.includes("&laquo;")) {
        item.label = item.label.replace("&laquo; 前へ", "«");
      }
      return item;
    });
  }
});

const total = ref(0);
const perPage = ref();
const currentPage = ref();
const lastPage = ref();

onMounted(() => {
  if (props.data) {
    total.value = props.data?.total;
    perPage.value = props.data?.per_page;
    currentPage.value = props.data?.current_page;
    lastPage.value = props.data?.last_page;
  }
});
</script>
<template>
  <div v-if="total > 0" class="flex justify-between w-full paginator">
    <div>
      全 {{ total }} 件中 {{ (currentPage - 1) * perPage + 1 }} -
      {{
        total < perPage ? total : currentPage != lastPage ? perPage * currentPage : total
      }}
      件目
    </div>
    <ul class="flex items-center justify-center rounded-md">
      <li v-for="(item, index) in links" class="shrink-0">
        <Link
          :href="item.url ?? '#'"
          class="flex items-center justify-center w-full shrink-0"
        >
          <span class="page-link-text" :class="{ active: item.label == currentPage }">{{
            item.label
          }}</span>
        </Link>
      </li>
    </ul>
  </div>
</template>
<style lang="scss" scoped>
li {
  &:first-child {
    .page-link-text {
      border-top-left-radius: 3px;
      border-bottom-left-radius: 3px;
    }
  }
  &:last-child {
    .page-link-text {
      border-top-right-radius: 3px;
      border-bottom-right-radius: 3px;
    }
  }
  .page-link-text {
    position: relative;
    float: left;
    padding: 6px 12px;
    margin-left: -1px;
    line-height: 1.5;
    color: #337ab7;
    text-decoration: none;
    background-color: #fff;
    border: 1px solid #ddd;
    &.active {
      background-color: #337ab7;
      border-color: #337ab7;
      color: white;
    }
  }
}
</style>
