<script setup>
import CustomToast from "@/Components/CustomToast.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import { useToast } from "primevue/usetoast";
import { getAddress, zipcodeType } from "@/Utils/action";

const props = defineProps({
  organizationDetail: Object,
  organizationList: Object,
});

const toast = useToast();
const form = useForm({
  id: props.organizationDetail.id,
  proOrganization: props.organizationDetail.organization_parent_name
    ? props.organizationList.filter(
        (filter) =>
          filter.organization_name == props.organizationDetail.organization_parent_name
      )[0]
    : null,
  organizationCode: props.organizationDetail.organization_code,
  organizationName: props.organizationDetail.organization_name,
  organizationZipCode: props.organizationDetail.organization_zipcode,
  organizationAddress: props.organizationDetail.organization_address,
  organizationMaster: props.organizationDetail.organization_master_name,
  redirectOption: null,
});

const toastBack = ref();

const handleGetAddress = async (e) => {
  const zipcode = e.target.value;
  if (zipcode) {
    const addrObject = await getAddress(zipcode);
    let addrText;
    addrText = addrObject.prefecture;
    addrText += addrObject.area;
    addrText += addrObject.address;
    form.organizationAddress = addrText;
  }
};

const submit = (e) => {
  form.redirectOption = e;

  form.put(route("admin.master.organization.update"), {
    onSuccess: () => {
      toastBack.value = "bg-green-500/70";
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
  <AdminLayout title="組織">
    <MainContent>
      <template #header>
        <FontAwesomeIcon icon="fa-solid fa-sitemap" />
        <h3>組織</h3>
      </template>
      <MasterEditBox @emitSubmit="submit" link="organization" :data="organizationDetail">
        <div class="w-full max-w-7xl input-form">
          <div class="my-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="親組織" />
            </div>
            <div class="input-field">
              <Dropdown
                v-model="form.proOrganization"
                :options="organizationList"
                optionLabel="organization_name"
                show-clear
                class="w-full"
                placeholder="親組織を入力してください。"
              />
            </div>
          </div>

          <div class="my-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="組織コード" essential />
            </div>
            <div class="input-field">
              <div class="w-full p-input-icon-left">
                <i class="pi pi-code"></i>
                <InputText
                  v-model="form.organizationCode"
                  class="w-full"
                  placeholder="組織コードを入力してください。"
                />
              </div>
              <p class="text-sm text-black/40">
                <FontAwesomeIcon icon="fa-solid fa-circle-info" />
                保存後、変更はできません。英小文字、英大文字、数字、"."(ドット)、"-"または"_"で記入してください。
              </p>
              <InputError :message="form.errors.organizationCode" />
            </div>
          </div>

          <div class="my-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="組織名" essential />
            </div>
            <div class="input-field">
              <div class="w-full p-input-icon-left">
                <i class="pi pi-pencil"></i>
                <InputText
                  v-model="form.organizationName"
                  class="w-full"
                  placeholder="組織名を入力してください。"
                />
              </div>
              <InputError :message="form.errors.organizationName" />
            </div>
          </div>

          <div class="my-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="郵便番号" />
            </div>
            <div class="input-field">
              <div class="w-full p-input-icon-left">
                <i class="pi pi-pencil"></i>
                <InputText
                  v-model="form.organizationZipCode"
                  class="w-full"
                  placeholder="郵便番号を入力してください。"
                  @keyup="handleGetAddress"
                  @keydown="zipcodeType"
                />
              </div>
              <InputError :message="form.errors.organizationZipCode" />
            </div>
          </div>

          <div class="my-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="住所" />
            </div>
            <div class="input-field">
              <div class="w-full p-input-icon-left">
                <i class="pi pi-pencil"></i>
                <InputText
                  v-model="form.organizationAddress"
                  class="w-full"
                  placeholder="住所を入力してください。"
                />
              </div>
              <InputError :message="form.errors.organizationAddress" />
            </div>
          </div>

          <div class="my-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="代表者名" />
            </div>
            <div class="input-field">
              <div class="w-full p-input-icon-left">
                <i class="pi pi-pencil"></i>
                <InputText
                  v-model="form.organizationMaster"
                  class="w-full"
                  placeholder="代表者名を入力してください。"
                />
              </div>
              <InputError :message="form.errors.organizationMaster" />
            </div>
          </div>
        </div>
      </MasterEditBox>
    </MainContent>
  </AdminLayout>
</template>
