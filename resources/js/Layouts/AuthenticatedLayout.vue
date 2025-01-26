<script setup>
import { ref, onMounted, computed } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import { Head, Link } from "@inertiajs/vue3";

const showingNavigationDropdown = ref(false);
const props = defineProps({
  title: String,
});

const currentRoute = computed(() => {
  return route().current();
});

const syncing = ref(false);
onMounted(() => {
  window.addEventListener("scroll", (e) => {
    let top = window.pageYOffset;
    if (top > 2000) {
      document.getElementById("scrolltotop").style.display = "block";
    } else {
      document.getElementById("scrolltotop").style.display = "none";
    }
  });
});

const scrollTop = () => {
  window.scrollTo({
    top: 0,
    behavior: "smooth",
  });
};

const currentUrl = ref(location.href);
</script>

<template>
  <Head :title="props.title" />
  <div class="relative w-full">
    <div class="min-h-screen bg-gray-100">
      <nav class="sticky top-0 z-50 bg-white border-b border-gray-100 shadow-md">
        <!-- Primary Navigation Menu -->
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
          <div class="flex justify-between h-16">
            <div class="flex items-center">
              <!-- Logo -->
              <div class="flex items-center shrink-0">
                <Link :href="route('user.attendance.list.index')">
                  <ApplicationLogo class="block w-auto text-gray-800 fill-current h-9" />
                </Link>
              </div>
              <div class="items-center hidden gap-8 pl-8 text-gray-600 sm:flex">
                <Link
                  :href="route('user.attendance.today.syukkin')"
                  class="text-lg transition duration-150 nav-item hover:text-teal-500"
                  :class="{ 'text-teal-500': currentRoute?.split('.')[2] == 'today' }"
                >
                  <FontAwesomeIcon
                    icon="fa-solid fa-person-walking"
                    class="pr-2 text-xl"
                  />
                  今日の打刻
                </Link>
                <Link
                  :href="route('user.attendance.list.index')"
                  class="text-lg transition duration-150 nav-item hover:text-teal-500"
                  :class="{ 'text-teal-500': currentRoute?.split('.')[2] == 'list' }"
                >
                  <FontAwesomeIcon icon="fa-solid fa-list" class="pr-2 text-xl" />
                  打刻一覧
                </Link>
              </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ml-6">
              <!-- Settings Dropdown -->
              <div class="sync">
                <Link :href="route(currentRoute)" @click="syncing = true">
                  <i class="text-teal-600 pi pi-sync" :class="syncing ? 'pi-spin' : ''" />
                </Link>
              </div>
              <div class="relative ml-3">
                <Dropdown align="right" width="48">
                  <template #trigger>
                    <span class="inline-flex rounded-md">
                      <button
                        type="button"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out bg-white border border-transparent rounded-md hover:text-gray-700 focus:outline-none"
                      >
                        <i class="pi pi-user text-rose-500" />
                        {{ $page.props.auth?.user?.name }}
                        <i class="pi pi-angle-down" />
                      </button>
                    </span>
                  </template>

                  <template #content>
                    <DropdownLink :href="route('profile.edit')" class="hidden">
                      情報変更
                    </DropdownLink>
                    <DropdownLink :href="route('logout')" method="post" as="button">
                      ログアウト
                    </DropdownLink>
                  </template>
                </Dropdown>
              </div>
            </div>

            <!-- Hamburger -->
            <div class="flex items-center -mr-2 sm:hidden">
              <div class="pr-3 sync">
                <Link :href="route(currentRoute)" @click="syncing = true">
                  <i class="text-teal-600 pi pi-sync" :class="syncing ? 'pi-spin' : ''" />
                </Link>
              </div>
              <button
                @click="showingNavigationDropdown = !showingNavigationDropdown"
                class="inline-flex items-center justify-center p-2 text-gray-400 transition duration-150 ease-in-out rounded-md hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500"
              >
                <svg
                  class="w-6 h-6"
                  stroke="currentColor"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <path
                    :class="{
                      hidden: showingNavigationDropdown,
                      'inline-flex': !showingNavigationDropdown,
                    }"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"
                  />
                  <path
                    :class="{
                      hidden: !showingNavigationDropdown,
                      'inline-flex': showingNavigationDropdown,
                    }"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Responsive Navigation Menu -->
        <div
          :class="{
            block: showingNavigationDropdown,
            hidden: !showingNavigationDropdown,
          }"
          class="sm:hidden"
        >
          <!-- Responsive Settings Options -->
          <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
              <div class="text-base font-medium text-gray-800">
                <i class="pi pi-user text-rose-500" />
                {{ $page.props.auth?.user?.name }}
              </div>
              <div class="text-sm font-medium text-gray-500">
                {{ $page.props.auth?.user?.email }}
              </div>
            </div>

            <div class="mt-3 space-y-1">
              <ResponsiveNavLink :href="route('profile.edit')" class="hidden">
                情報変更
              </ResponsiveNavLink>
              <ResponsiveNavLink :href="route('user.attendance.today.syukkin')">
                今日の打刻
              </ResponsiveNavLink>
              <ResponsiveNavLink :href="route('user.attendance.list.index')">
                打刻一覧
              </ResponsiveNavLink>
              <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                ログアウト
              </ResponsiveNavLink>
            </div>
          </div>
        </div>
      </nav>
      <!-- Page Heading -->
      <header class="bg-white shadow" v-if="$slots.header">
        <div class="px-4 py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
          <slot name="header" />
        </div>
      </header>
      <!-- Page Content -->
      <main class="relative">
        <!-- BreadCrumb -->
        <slot />
      </main>
    </div>
  </div>
  <Transition>
    <div id="scrolltotop" class="fixed hidden bottom-5 right-5">
      <div class="flex items-center justify-center w-12 h-12 rounded-full">
        <Button icon="pi pi-angle-up" rounded @click="scrollTop" />
      </div>
    </div>
  </Transition>
</template>
<style lang="scss" scoped>
.nav-item {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
