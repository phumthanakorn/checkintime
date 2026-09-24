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

    <!-- หัวข้อแบบกดเปิด/ปิด -->
    <div class="space-y-2.5">
      <details
        v-for="(section, index) in SECTIONS"
        :key="section.title"
        class="group rounded-2xl bg-card shadow-sm"
        :open="index === 0"
      >
        <summary class="flex cursor-pointer list-none items-center gap-3 p-4">
          <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100">
            <AppIcon :name="section.icon" :size="18" weight="duotone" class="text-status-done" />
          </span>
          <span class="flex-1 text-sm font-semibold text-ink">{{ section.title }}</span>
          <AppIcon name="caret-down" :size="18" class="text-ink-muted transition-transform group-open:rotate-180" />
        </summary>
        <ul class="space-y-1.5 px-4 pb-4 pl-16 text-xs leading-relaxed text-ink-muted">
          <li v-for="item in section.items" :key="item" class="list-disc">{{ item }}</li>
        </ul>
      </details>
    </div>

    <p class="px-2 text-center text-xs text-ink-muted">
      ต้องการใช้สิทธิหรือสอบถามเพิ่มเติม ติดต่อฝ่ายบุคคล (HR) {{ HR_CONTACT_PHONE }}
    </p>
  </div>
</template>

<script setup>
import PageHeader from '@/components/layout/PageHeader.vue'
import { HR_CONTACT_PHONE } from '@/utils/constants'

const UPDATED_AT = '1 ก.ย. 2569'

const SECTIONS = [
  {
    title: 'ข้อมูลที่เราเก็บ',
    icon: 'id-card',
    items: [
      'ข้อมูลพนักงาน: ชื่อ-สกุล รหัสพนักงาน ตำแหน่ง แผนก วันเริ่มงาน',
      'ข้อมูลติดต่อ: เบอร์โทรศัพท์ อีเมล ที่อยู่ และผู้ติดต่อกรณีฉุกเฉิน',
      'ข้อมูลการทำงาน: เวลาเข้า-ออกงาน การลา คำขอลงเวลาย้อนหลัง และไฟล์แนบ',
      'ข้อมูลเงินเดือน: รายได้ รายการหัก และบัญชีธนาคารที่รับเงิน',
    ],
  },
  {
    title: 'ตำแหน่งที่ตั้ง (GPS)',
    icon: 'map-pin',
    items: [
      'ใช้ตำแหน่งเฉพาะตอนกดเข้างาน-ออกงาน เพื่อยืนยันว่าอยู่ในพื้นที่ทำงาน',
      'ไม่มีการติดตามตำแหน่งตลอดเวลา หรือเมื่อไม่ได้ใช้งานแอป',
      'คุณปฏิเสธการเข้าถึงตำแหน่งได้จากการตั้งค่าของโทรศัพท์',
    ],
  },
  {
    title: 'วัตถุประสงค์การใช้ข้อมูล',
    icon: 'file-text',
    items: [
      'บันทึกเวลาทำงานและคำนวณค่าจ้าง ค่าล่วงเวลา และวันลา',
      'พิจารณาอนุมัติคำขอลาและคำขอลงเวลาย้อนหลัง',
      'ติดต่อคุณหรือผู้ติดต่อฉุกเฉินเมื่อจำเป็น',
      'ปฏิบัติตามกฎหมายแรงงาน ภาษี และประกันสังคม',
    ],
  },
  {
    title: 'ระยะเวลาการเก็บรักษา',
    icon: 'clock',
    items: [
      'เก็บตลอดระยะเวลาการเป็นพนักงาน',
      'หลังพ้นสภาพ เก็บต่อไม่เกิน 10 ปี ตามที่กฎหมายกำหนด แล้วลบหรือทำให้ไม่สามารถระบุตัวตนได้',
    ],
  },
  {
    title: 'สิทธิของคุณ',
    icon: 'shield-check',
    items: [
      'ขอเข้าถึงและขอรับสำเนาข้อมูลของคุณ',
      'ขอแก้ไขข้อมูลให้ถูกต้อง (ข้อมูลติดต่อแก้ไขเองได้ที่เมนู “ข้อมูลส่วนตัว”)',
      'ขอให้ลบ ระงับ หรือคัดค้านการใช้ข้อมูล ในกรณีที่กฎหมายกำหนด',
      'ร้องเรียนต่อสำนักงานคณะกรรมการคุ้มครองข้อมูลส่วนบุคคล',
    ],
  },
]
</script>

<style scoped>
summary::-webkit-details-marker {
  display: none;
}
</style>
