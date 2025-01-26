<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
</script>

<template>
  <div
    class="guest-layout flex flex-col items-center min-h-screen pt-6 bg-gradient-to-tr from-gray-300 to-gray-200 sm:justify-center sm:pt-0"
  >
      <!--
    <div>
      <ApplicationLogo class="h-[60px] text-gray-500 fill-current" />
    </div>
    -->

    <div
      class="w-full px-6 py-4 mt-6 overflow-hidden bg-white shadow-md sm:max-w-md sm:rounded-md"
    >
        <ApplicationLogo  class="h-[60px] text-gray-500 fill-current" />
        <hr />
      <slot />
    </div>
  </div>
</template>
<style lang="scss" scope>
.guest-layout {
  position: relative;
  z-index: 0;
  &::before {
    content: "";
    position: absolute;
    width: 100%;
    height: 100%;
    background-image: radial-gradient(circle, #33333330 0, transparent 30%),
      radial-gradient(circle, #33333330 0, transparent 30%);
    background-size: 6px 6px;
    background-position: 0 0, 3px 3px;
    z-index: -1;
  }
}
</style>
