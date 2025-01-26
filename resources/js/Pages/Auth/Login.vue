<script setup>
import Checkbox from "@/Components/Checkbox.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

defineProps({
  canResetPassword: Boolean,
  status: String,
});

const form = useForm({
  code: "",
  password: "",
  remember: false,
});

const submit = () => {
  form.post(route("login"), {
    onFinish: () => form.reset("password"),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="ログイン" />

    <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
      {{ status }}
    </div>

    <form @submit.prevent="submit">
      <div>
        <InputLabel for="code" value="コード" />

        <div class="w-full p-input-icon-left">
          <i class="pi pi-user"></i>
          <InputText
            id="code"
            class="block w-full mt-1"
            v-model="form.code"
            required
            autofocus
            autocomplete="code"
            placeholder="ユーザーコードを入力してください。"
          />
        </div>
        <InputError class="mt-2" :message="form.errors.code" />
      </div>

      <div class="mt-4">
        <InputLabel for="password" value="パスワード" />
        <div class="w-full p-input-icon-left">
          <i class="pi pi-lock"></i>
          <InputText
            id="password"
            type="password"
            class="block w-full mt-1"
            v-model="form.password"
            required
            autocomplete="current-password"
            placeholder="パスワードを入力してください。"
          />
        </div>

        <InputError class="mt-2" :message="form.errors.password" />
      </div>

      <div class="mt-4">
        <label class="flex items-center">
          <Checkbox name="remember" v-model:checked="form.remember" />
          <span class="ml-2 text-sm text-gray-600">ログイン状態を保存する</span>
        </label>
      </div>

      <div class="mt-8 text-center">
        <Button
          type="submit"
          label="ログイン"
          icon="pi pi-sign-in"
          class="w-full p-button-sm"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        />
      </div>
      <div class="mt-4 text-center">
        <Link
          v-if="canResetPassword"
          :href="route('password.request')"
          class="text-sm rounded-md text-sky-400 hover:text-sky-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          パスワードをお忘れですか？
        </Link>
      </div>
    </form>
  </GuestLayout>
</template>
