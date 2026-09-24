<template>
  <div class="space-y-4">
    <PageHeader title="นโยบายความเป็นส่วนตัว" subtitle="ตาม พ.ร.บ.คุ้มครองข้อมูลส่วนบุคคล (PDPA)" :back-to="{ name: 'profile' }" />

    <section class="flex items-start gap-3 rounded-2xl bg-status-checkin/10 p-4">
      <AppIcon name="shield-check" :size="28" weight="duotone" class="text-status-checkin" />
      <p class="text-xs leading-relaxed text-ink">
        บริษัทเก็บและใช้ข้อมูลของคุณเท่าที่จำเป็นต่อการลงเวลาทำงาน การลา และการจ่ายเงินเดือนเท่านั้น
        <span class="text-ink-muted">ปรับปรุงล่าสุด {{ UPDATED_AT }}</span>
      </p>
    </section>

    <PrivacySections />

    <p class="px-2 text-center text-xs text-ink-muted">
      ต้องการใช้สิทธิหรือสอบถามเพิ่มเติม ติดต่อฝ่ายบุคคล (HR) {{ HR_CONTACT_PHONE }}
    </p>
  </div>
</template>

<script setup>
import PageHeader from '@/components/layout/PageHeader.vue'
import PrivacySections from '../components/PrivacySections.vue'
import { HR_CONTACT_PHONE, PDPA_UPDATED_AT as UPDATED_AT } from '@/utils/constants'
</script>

