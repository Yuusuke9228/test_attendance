<script setup>
import AdminSidebar from "./AdminSidebar.vue";
import AdminTopNavigation from "./AdminTopNavigation.vue";
import { Head } from "@inertiajs/vue3";
import { ref, onMounted, watch, computed } from "vue";
import axios from "axios";
import { useStorage } from "@vueuse/core";

const props = defineProps({
  title: String,
});

const menu = useStorage("menu_status", true);

const checkingMasterData = ref(false);
onMounted(() => {
  axios.get(route("admin.check.master")).then((res) => {
    if (!res.data.success) {
      checkingMasterData.value = true;
    }
  });
});
const menuExpandAction = () => {
  menu.value = !menu.value;
};
</script>

<template>
  <Head :title="props.title" />
  <div
    class="z-50 w-full h-screen overflow-hidden transition duration-150 admin-page wf-kaku"
    :class="menu ? 'fold' : 'unfold'"
  >
    <AdminSidebar :menu="menu" />
    <div class="relative h-screen overflow-hidden">
      <AdminTopNavigation @menuAction="menuExpandAction" />
      <div
        class="relative top-0 page-content h-[calc(100vh-50px)] overflow-y-auto p-4 bg-lback"
      >
        <slot />
      </div>
    </div>
  </div>
  <Dialog
    v-model:visible="checkingMasterData"
    header="警告!"
    modal
    dismissableMask
    position="bottomright"
    class="w-full max-w-lg"
  >
    <div class="text-center">
      <i class="pi pi-info-circle text-red-500 text-5xl"></i>
      <p class="font-bold mt-4 text-lg">一部のマスターデータが存在しません。</p>
    </div>
  </Dialog>
</template>
<style lang="scss" scoped>
.admin-page {
  display: grid;
  transition: 0.3s ease;
  &.fold {
    grid-template-columns: 50px 1fr;
  }
  &.unfold {
    grid-template-columns: 230px 1fr;
  }
}
</style>
