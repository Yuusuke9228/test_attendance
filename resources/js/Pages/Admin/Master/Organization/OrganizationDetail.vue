<script setup>
const props = defineProps({
  organizationDetail: Object,
});
</script>
<template>
  <AdminLayout title="組織">
    <MainContent>
      <template #header>
        <FontAwesomeIcon icon="fa-solid fa-sitemap" />
        <h3>組織</h3>
      </template>
      <MasterDetailBox link="organization" :data="organizationDetail">
        <div class="w-full center input-form">
          <div class="my-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="親組織" />
            </div>
            <div class="input-field">
              {{ organizationDetail.organization_parent_name }}
            </div>
          </div>

          <div class="my-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="組織コード" />
            </div>
            <div class="input-field">
              {{ organizationDetail?.organization_code }}
            </div>
          </div>

          <div class="my-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="組織名" />
            </div>
            <div class="input-field">
              {{ organizationDetail?.organization_name }}
            </div>
          </div>

          <div class="my-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="郵便番号" />
            </div>
            <div class="input-field">
              {{ organizationDetail?.organization_zipcode }}
            </div>
          </div>

          <div class="my-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="住所" />
            </div>
            <div class="input-field">
              {{ organizationDetail?.organization_address }}
            </div>
          </div>

          <div class="my-4 form-inner center">
            <div class="label-field label-right">
              <InputLabel value="代表者名" />
            </div>
            <div class="input-field">
              {{ organizationDetail?.organization_master_name }}
            </div>
          </div>
        </div>
      </MasterDetailBox>
    </MainContent>
  </AdminLayout>
</template>
