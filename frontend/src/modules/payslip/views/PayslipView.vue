<template>
  <div class="space-y-5">
    <PageHeader title="สลิปเงินเดือน" :back-to="{ name: 'profile' }" />
    <EmptyState icon="mdi-file-document-outline" title="กำลังพัฒนา" description="หน้าดูและดาวน์โหลดสลิปเงินเดือน" />
  </div>
</template>

<script setup>
import PageHeader from '@/components/layout/PageHeader.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
</script>
