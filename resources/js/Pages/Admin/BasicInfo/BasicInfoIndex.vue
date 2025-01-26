<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import { Inertia } from "@inertiajs/inertia";
import { ref, onMounted, watch } from "vue";
import { useToast } from "primevue/usetoast";
import moment from "moment";
import { getAddress } from "@/Utils/action";

const props = defineProps({
  prefecutres: Object,
  companySetting: Object,
});

const toast = useToast();
const form = useForm({
  name: props.companySetting?.company_name,
  kanaName: props.companySetting?.company_kana,
  zipCode: props.companySetting?.company_zip_code,
  address_1: props.companySetting?.company_addr01,
  address_2: props.companySetting?.company_addr02,
  tel_1: props.companySetting?.company_tel01,
  tel_2: props.companySetting?.company_tel02,
  tel_3: props.companySetting?.company_tel03,
  closeDate: props.companySetting
    ? moment(props.companySetting?.company_month_closing_date).format("yyyy/MM/DD")
    : null,
  closeStatus: props.companySetting?.company_month_closing_status == 1 ? true : false,
});

watch(
  form,
  async (n, o) => {
    const address = await getAddress(form.zipCode);
    if (address?.prefecture) {
      let result = "";
      result += address.prefecture;
      result += address.area;
      result += address.address;
      form.address_1 = result;
    }
  },
  { deep: true }
);

const toastBack = ref();
const submit = (redirectOption) => {
  form.put(route("admin.base.update"), {
    onSuccess: () => {
      toastBack.value = "bg-green-500/70";
      toast.add({
        severity: "custom",
        summary: "操作成功！",
        life: 2000,
        group: "headless",
      });
      //   Inertia.visit(route("admin.base.index"));
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
  <AdminLayout title="基本情報 ">
    <MainContent>
      <template #header>
        <FontAwesomeIcon icon="fa-solid fa-building" />
        <h3>基本情報</h3>
      </template>
      <MasterDetailBox>
        <div class="w-full m-auto max-w-7xl input-form">
          <form @submit.prevent="submit">
            <div class="my-4 form-inner center">
              <div class="label-field label-right">
                <InputLabel value="会社名" essential />
              </div>
              <div class="input-field">
                <div class="w-full p-input-icon-left">
                  <i class="pi pi-building"></i>
                  <InputText
                    v-model="form.name"
                    class="w-full"
                    placeholder="会社名を入力してください。"
                  />
                </div>
                <InputError :message="form.errors.name" />
              </div>
            </div>

            <div class="my-4 form-inner center">
              <div class="label-field label-right">
                <InputLabel value="会社カナ" />
              </div>
              <div class="input-field">
                <div class="w-full p-input-icon-left">
                  <i class="pi pi-building"></i>
                  <InputText
                    v-model="form.kanaName"
                    class="w-full"
                    placeholder="会社カナを入力してください。"
                  />
                </div>
              </div>
            </div>

            <div class="my-4 form-inner center">
              <div class="label-field label-right">
                <InputLabel value="郵便番号" />
              </div>
              <div class="input-field">
                <div class="w-full p-input-icon-left">
                  <i class="" style="font-style: normal; padding-bottom: 6px; top: 0.6rem"
                    >〒</i
                  >
                  <InputText
                    v-model="form.zipCode"
                    class="w-full"
                    placeholder="郵便番号を入力してください。(半角数字で入力してください。)"
                  />
                </div>
                <p class="text-sm text-black/40">
                  <FontAwesomeIcon icon="fa-solid fa-circle-info" />
                  数字、"-"または"_"で記入してください。
                </p>
              </div>
            </div>

            <div class="my-4 form-inner center">
              <div class="label-field label-right">
                <InputLabel value="住所" />
              </div>
              <div class="input-field">
                <div class="w-full p-input-icon-left">
                  <i class="pi pi-map"></i>
                  <InputText
                    v-model="form.address_1"
                    class="w-full"
                    placeholder="郵便番号入力時に自動入力されます。"
                  />
                </div>
              </div>
            </div>

            <div class="my-4 form-inner center">
              <div class="label-field label-right">
                <InputLabel value="住所(ビル以降)" />
              </div>
              <div class="input-field">
                <div class="w-full p-input-icon-left">
                  <i class="pi pi-map-marker"></i>
                  <InputText
                    v-model="form.address_2"
                    class="w-full"
                    placeholder="住所(ビル以降)を入力してください。"
                  />
                </div>
              </div>
            </div>

            <div class="my-4 form-inner center">
              <div class="label-field label-right">
                <InputLabel value="電話番号1" />
              </div>
              <div class="input-field">
                <div class="w-full p-input-icon-left">
                  <i class="pi pi-phone"></i>
                  <InputText
                    v-model="form.tel_1"
                    class="w-full"
                    placeholder="電話番号1を入力してください。"
                  />
                </div>
                <p class="text-sm text-black/40">
                  <FontAwesomeIcon icon="fa-solid fa-circle-info" />
                  数字、"-"または"_"で記入してください。
                </p>
              </div>
            </div>

            <div class="my-4 form-inner center">
              <div class="label-field label-right">
                <InputLabel value="電話番号2" />
              </div>
              <div class="input-field">
                <div class="w-full p-input-icon-left">
                  <i class="pi pi-phone"></i>
                  <InputText
                    v-model="form.tel_2"
                    class="w-full"
                    placeholder="電話番号2を入力してください。"
                  />
                </div>
                <p class="text-sm text-black/40">
                  <FontAwesomeIcon icon="fa-solid fa-circle-info" />
                  数字、"-"または"_"で記入してください。
                </p>
              </div>
            </div>

            <div class="my-4 form-inner center">
              <div class="label-field label-right">
                <InputLabel value="電話番号3" />
              </div>
              <div class="input-field">
                <div class="w-full p-input-icon-left">
                  <i class="pi pi-phone" />
                  <InputText
                    v-model="form.tel_3"
                    class="w-full"
                    placeholder="電話番号3を入力してください。"
                  />
                </div>
                <p class="text-sm text-black/40">
                  <FontAwesomeIcon icon="fa-solid fa-circle-info" />
                  数字、"-"または"_"で記入してください。
                </p>
              </div>
            </div>

            <div class="my-4 form-inner center">
              <div class="label-field label-right">
                <InputLabel value="月締め処理" />
              </div>
              <div class="input-field">
                <div class="w-full flex items-center gap-4">
                  <VueDatePicker
                    v-model="form.closeDate"
                    class="w-full"
                    placeholder="対象月を選択してください。"
                    monthPicker
                    locale="ja"
                    modelType="yyyy/MM/dd"
                    format="yyyy年MM月"
                    autoApply
                  />
                  <ToggleButton
                    v-model="form.closeStatus"
                    onLabel="締め処理の取り消"
                    offLabel="締め処理の行"
                    class="p-button-sm shrink-0 w-44 border-0 ring-0"
                    :class="form.closeStatus ? 'bg-red-500' : 'bg-green-500'"
                  />
                </div>
                <p clas="text-sm text-gray-00">
                  {{
                    form.closeStatus
                      ? "締め処理されました。"
                      : "締め処理が取り消されました。"
                  }}
                  <span class="text-xs text-red-600">
                    （変更後に保存ボタンを押してください）
                  </span>
                </p>
              </div>
            </div>
            <hr class="my-4" />
            <div class="flex justify-end mb-4">
              <Button
                type="submit"
                label="保存"
                icon="pi pi-save"
                severity="info"
                :disabled="form.processing"
                size="small"
              />
            </div>
          </form>
        </div>
      </MasterDetailBox>
    </MainContent>
  </AdminLayout>
</template>
