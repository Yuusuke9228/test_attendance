<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, onMounted, computed } from "vue";
import { useToast } from "primevue/usetoast";
import axios from "axios";
import moment from "moment";

const loading = ref(true);
const wrongData = ref([]);

onMounted(() => {
  axios
    .get(route("admin.consistency.fetch"))
    .then((res) => {
      if (res.data) {
        wrongData.value = res.data;
      }
    })
    .finally(() => {
      loading.value = false;
    });
});
</script>
<template>
  <AdminLayout title="打刻の整合性確認">
    <MainContent title="打刻の整合性確認" icon="pi-check-circle">
      <div class="consistency-check p-3">
        <div class="data-list shadow-lg p-3">
          <div class="custom-table w-full overflow-auto">
            <div v-if="loading" class="flex text-center items-center flex-col">
              <Spinner />
              <p class="mt-4">検索中</p>
            </div>
            <div v-else-if="wrongData.length == 0">
              <p>検索結果なし！</p>
            </div>
            <table v-else class="table-auto w-full">
              <thead>
                <tr>
                  <th>No</th>
                  <th>日付</th>
                  <th>ユーザー</th>
                  <th>現場</th>
                  <th>職種</th>
                  <th>応援区分</th>
                  <th>応援情報</th>
                  <th>応援人数</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in wrongData" :key="index">
                  <td>{{ index + 1 }}</td>
                  <td>{{ item.date }}</td>
                  <td>{{ item.user }}</td>
                  <td>{{ item.location }}</td>
                  <td>{{ item.occupation }}</td>
                  <td>{{ item.flag }}</td>
                  <td>{{ item.comp_name }}</td>
                  <td>{{ item.nums }}</td>
                  <td>
                    <Link :href="route('admin.master.attendance.edit', { id: item.id })">
                      <FontAwesomeIcon
                        icon="fa-regular fa-pen-to-square"
                        class="text-sky-500 hover:text-rose-500 transition"
                      />
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </MainContent>
  </AdminLayout>
</template>
