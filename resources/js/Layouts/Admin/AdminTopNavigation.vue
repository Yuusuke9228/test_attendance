<script setup>
import UserIcon from "@/Components/UserIcon.vue";
import moment from "moment";
import { useForm, usePage, Link } from "@inertiajs/vue3";
import { ref, onMounted, computed } from "vue";
import Loader from "@/Components/Loader.vue";

const emit = defineEmits(["menuAction"]);

const userName = computed(() => {
  return usePage().props.auth.user?.name;
});
const userDate = computed(() => {
  if (usePage().props.auth.user) {
    return moment(usePage().props.auth.user?.created_at).format(
      "yyyy年MM月DD日 HH:mm:ss"
    );
  }
});

const dropdownVisible = ref(false);

const form = useForm({
  s: null,
});

const syncing = ref(false);
const currentUrl = location.href;

const expandAction = () => {
  emit("menuAction");
};

const urlParams = new URLSearchParams(window.location.search);
const paramValue = urlParams.get("s");
if (urlParams.has("s")) {
  form.s = paramValue;
}
const globalSearch = (e) => {
  if (form.s) {
    form.get(route("admin.filter.index"));
  }
};
</script>
<template>
  <div class="sticky top-0 w-full bg-lback h-[50px] shadow-md z-50">
    <div class="relative w-full">
      <div class="flex items-center justify-between w-full">
        <div class="flex items-center left-region">
          <div
            class="flex items-center justify-center px-3 py-4 border-l border-r cursor-pointer hover:bg-white"
            @click="expandAction"
          >
            <i class="block pi pi-bars"></i>
          </div>
          <div class="flex items-center float-left px-4 py-2 global-search-field">
            <div class="p-input-icon-right">
              <i class="pi pi-search"></i>
              <InputText
                v-model="form.s"
                size="small"
                class="py-2 text-xs border-none p-inputtext-sm w-96"
                placeholder="キーワードを入力してEnterキーを押します。"
                @keyup.enter="globalSearch"
              />
            </div>
          </div>
        </div>
        <div class="relative flex items-center right-region">
          <div class="" role="button">
            <Link
              :href="route('admin.master.holiday.calendar')"
              class="border-l hover:bg-white active:bg-sky-100 px-4 py-[13px] block"
            >
              <i class="block pi pi-calendar"></i>
            </Link>
          </div>
          <div class="" role="button">
            <Link
              :href="currentUrl ?? '#'"
              @click="syncing = true"
              class="border-l hover:bg-white active:bg-sky-100 px-4 py-[13px] block"
            >
              <i class="pi pi-sync" :class="{ 'pi-spin': syncing }"></i>
            </Link>
          </div>
          <div
            class="adminDropdownPannel px-4 py-[13px] border-l hover:bg-white flex items-center gap-1"
            role="button"
            @click="dropdownVisible = !dropdownVisible"
          >
            <UserIcon imgClass="w-6" />
            <span class="text-14 text-txt">{{ $page.props.auth.user.name }}</span>
          </div>
        </div>
      </div>
      <!-- dropdown admin info pannel -->
      <div v-if="dropdownVisible" class="absolute right-0 z-50 shadow w-72">
        <div class="w-full shadow-sm">
          <div
            class="bg-gradient-to-br via-[#222] from-[#344] to-[#334] avatar p-3 text-center text-white"
          >
            <UserIcon imgClass="w-20 rounded-full border-2 border-slate-500/40 m-auto" />
            <div class="py-2 text-txt">
              <p class="text-14">{{ userName ?? "" }}</p>
              <p class="text-12">作成日時 ： {{ userDate ?? "" }}</p>
            </div>
          </div>
          <div class="flex items-center justify-between w-full p-3 rounded-b-md bg-lback">
            <Link :href="route('admin.base.index')">
              <Button
                label="設定"
                icon="pi pi-cog"
                size="small"
                class="py-1 rounded-sm"
                severity="success"
              />
            </Link>
            <Link :href="route('logout')" method="POST" as="button">
              <Button
                label="ログアウト"
                icon="pi pi-sign-out"
                size="small"
                class="py-1 rounded-sm"
                severity="danger"
              />
            </Link>
          </div>
        </div>
      </div>
      <!-- background drop -->
      <div
        v-if="dropdownVisible"
        class="fixed top-0 left-0 z-20 w-full h-screen bg-transparent"
        @click="dropdownVisible = false"
      ></div>
    </div>
  </div>
  <div v-if="form.processing" class="">
    <Loader title="検索中" />
  </div>
</template>
