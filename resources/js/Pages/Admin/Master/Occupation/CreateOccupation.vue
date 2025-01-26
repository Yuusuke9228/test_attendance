<script setup>
import CustomToast from "@/Components/CustomToast.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ref, onMounted, computed } from "vue";
import { useToast } from "primevue/usetoast";
import moment from "moment";

const toast = useToast();
const form = useForm({
  name: null,
  workContent: [],
  redirectOption: null,
});

const toastBack = ref();

const addWorkContent = () => {
  form.workContent.push({ workContentName: null });
};
const removeWorkContent = (index) => {
  form.workContent.splice(index, 1);
};
const submit = (redirectOption) => {
  form.redirectOption = redirectOption;
  form.post(route("admin.master.occupation.store"), {
    onFinsh: () => {
      form.reset();
    },
    onSuccess: () => {
      toastBack.value = "bg-green-500/70";
      form.reset();
      toast.add({
        severity: "custom",
        summary: "操作成功！",
        life: 2000,
        group: "headless",
      });
    },
    onErrorr: () => {
      toastBack.value = "bg-red-500/70";
      toast.add({
        severity: "custom",
        summary: "操作失敗！",
        life: 2000,
        group: "headless",
      });
    },
  });
};
</script>
<template>
  <CustomToast group="headless" :bgClass="toastBack" />
  <AdminLayout title="職種管理">
    <MainContent>
      <template #header>
        <i class="pi pi-flag-fill"></i>
        <h3>
          職種管理
          <small>現場（大工・解体）/ 加工 など職種を管理します。</small>
        </h3>
      </template>
      <MasterCreateForm @emitSubmit="submit" link="occupation">
        <div class="w-full max-w-7xl input-form">
          <div class="my-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="職種名" essential />
            </div>
            <div class="input-field">
              <div class="w-full p-input-icon-left">
                <i class="pi pi-pencil"></i>
                <InputText
                  v-model="form.name"
                  class="w-full"
                  placeholder="職種名を入力してください。"
                />
              </div>
              <InputError :message="form.errors.name" />
            </div>
          </div>
        </div>
        <div class="px-12 my-4">
          <p class="text-lg">作業内容</p>
          <hr class="my-2" />
          <div class="mt-4">
            <div class="grid w-full grid-cols-12 gap-2 text-lg text-center bg-gray-200">
              <div class="flex items-center justify-center col-span-10 py-2">
                作業名<span class="text-red-500">*</span>
              </div>
              <div class="flex items-center justify-center col-span-2">操作</div>
            </div>
          </div>
          <div v-if="form.workContent.length > 0" class="w-full">
            <div v-for="(item, index) in form.workContent" :key="index" class="w-full">
              <hr class="my-2" />
              <div class="grid w-full grid-cols-12 gap-2 text-center">
                <div class="flex items-center justify-center col-span-10">
                  <div class="w-full">
                    <div class="w-full p-input-icon-left">
                      <i class="pi pi-pencil"></i>
                      <InputText
                        v-model="item['workContentName']"
                        class="w-full"
                        placeholder="作業内容を入力してください。"
                      />
                    </div>
                    <InputError
                      :message="form.errors[`workContent.${index}.workContentName`]"
                    />
                  </div>
                </div>
                <div class="col-span-2">
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    class="w-10 h-10 text-white"
                    @click="removeWorkContent(index)"
                  />
                </div>
              </div>
            </div>
          </div>
          <hr class="my-2" />
          <div class="py-3">
            <Button
              label="新規"
              icon="pi pi-plus"
              size="small"
              severity="success"
              @click="addWorkContent"
            />
          </div>
        </div>
      </MasterCreateForm>
    </MainContent>
  </AdminLayout>
</template>
