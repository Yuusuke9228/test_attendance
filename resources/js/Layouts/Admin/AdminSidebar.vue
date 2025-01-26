<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import UserIcon from "@/Components/UserIcon.vue";
import { Link } from "@inertiajs/vue3";
import { field } from "@/Utils/field";
import { computed, ref, onMounted } from "vue";

const props = defineProps({
  menu: Boolean,
});
const routeGroup = computed(() => {
  let fullRoute = route().current();
  return fullRoute.split(".");
});

const foldVisible = ref(false);

onMounted(() => {
  if (routeGroup.value[1] == "master") {
    foldVisible.value = true;
  }
});
</script>
<template>
  <div
    class="w-full min-h-screen border-r border-gray-300 sidebar bg-lback"
    :class="{ 'sidebar-mini': menu }"
  >
    <div class="sticky top-0 flex items-center justify-center h-[50px] border-b bg-lback">
      <ApplicationLogo class="h-[36px]" :small="menu" />
    </div>
    <div class="relative sidebar-items">
      <div class="sticky top-0 h-[80px] user-info-header bg-lback">
        <div class="flex items-center gap-4 p-2">
          <UserIcon imgClass="w-10" />
          <div class="name_code">
            <p class="text-sm text-gray-600 font-500">
              {{ $page.props.auth.user?.name }}
            </p>
            <p class="text-xs text-txt">@{{ $page.props.auth.user?.code }}</p>
          </div>
        </div>
        <div class="p-2 text-xs text-gray-500 menu-tab-title">メニュー</div>
      </div>
      <div class="h-[calc(100vh-130px)] menu-link-list-field">
        <ul
          v-if="field.menu"
          class="text-gray-800 cursor-pointer menu-list font-500 text-14"
        >
          <li v-for="(item, index) in field.menu" :key="index" class="list-items">
            <Link
              v-if="!item.children"
              :href="item.link ? route(item.link) : '#'"
              class="flex items-center gap-2 link-item hover:bg-back hover:text-black"
              :class="{ active: item.group !== null && item.group == routeGroup[1] }"
            >
              <i class="pi" :class="item.icon"></i>
              <div class="menu-label">
                <span>{{ item.label }}</span>
              </div>
            </Link>
            <div v-else class="relative child-group">
              <div
                class="relative flex items-center justify-between gap-2 pr-3 hover:bg-back hover:text-black"
                @click="foldVisible = !foldVisible"
              >
                <div class="flex items-center gap-2 link-item">
                  <i class="pi" :class="item.icon"></i>
                  <div class="menu-label">
                    <span>{{ item.label }}</span>
                  </div>
                </div>
                <i
                  class="pi"
                  :class="!foldVisible ? 'pi-angle-down' : 'pi-angle-left'"
                ></i>
              </div>
              <div
                class="overflow-hidden sub-menu"
                :class="foldVisible ? 'unfold' : 'fold'"
              >
                <div v-for="(subItem, subIndex) in item?.children" :key="subIndex">
                  <Link
                    :href="subItem.link ? route(subItem.link) : '#'"
                    class="flex items-center gap-2 py-2 pl-6 hover:bg-back hover:text-black whitespace-nowrap"
                    :class="{ active: subItem.subGroup == routeGroup[2] }"
                  >
                    <i class="pi" :class="subItem.icon"></i>
                    <span>{{ subItem.label }}</span>
                  </Link>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>
<style lang="scss" scoped>
.active {
  color: white;
  background-color: #c5c5c5;
}

.fold {
  height: 0;
  overflow: hidden;
  transition: height 0.3s ease-out;
}

.unfold {
  height: 440px;
  transition: height 0.3s ease-in;
}

// sidebar expand & collapse
.sidebar {
  .menu-link-list-field {
    width: 230px;
    overflow-y: auto;
    overflow-x: visible;

    .link-item {
      padding: 12px;

      .menu-label {
        white-space: nowrap;
      }
    }
  }

  &.sidebar-mini {
    .sidebar-items {
      .user-info-header {
        height: 50px;

        .name_code,
        .menu-tab-title {
          display: none;
        }
      }

      .list-items {
        position: relative;

        .link-item {
          position: relative;
          display: block;
          display: flex;
          width: 50px;
          gap: 20px;
          margin: 0;
          padding: 12px 15px;
          z-index: 19999999;
          transition: 0.3s ease;

          .menu-label {
            display: none;

            display: block;
            line-height: 1;
            border-top-left-radius: 3px;
            border-top-right-radius: 3px;
            overflow: hidden;
            background-color: rgb(236 240 245);
            z-index: 0;
          }

          &:hover {
            .menu-label {
              position: absolute;
              width: 200px;
              height: 40px;
              left: 50px;
              padding: 12px;
              z-index: 999999;
            }
          }

          &.active {
            &:hover {
              .menu-label {
                color: white;
                background-color: #c5c5c5;
              }
            }
          }
        }
      }

      .child-group {
        .sub-menu {
          overflow: hidden !important;
          display: none;
          background-color: red;
          height: 372px;
          margin-left: -2px;

          &.fold {
            overflow: visible;
            transition: height 0.3s ease-out;
          }
        }

        &:hover {
          .menu-label {
            display: block;
            position: absolute;
            width: 200px;
            height: 40px;
            left: 50px;
            padding: 12px;
            z-index: 999999;
          }

          .sub-menu {
            display: block;
            position: absolute;
            left: 50px;
            z-index: 999999;

            & > div {
              background-color: rgb(236 240 245);

              a {
                padding: 5px 1rem;
                padding-left: 0.5rem;
              }
            }
          }
        }
      }
    }
  }
}
</style>
