<template>
  <!-- เนื้อหานโยบายความเป็นส่วนตัว (ใช้ทั้งหน้า PDPA และขั้นตอนยินยอมตอนเริ่มใช้งาน) -->
  <div class="space-y-2.5">
    <details
      v-for="(section, index) in SECTIONS"
      :key="section.title"
      class="group rounded-2xl bg-card shadow-sm"
      :class="bordered && 'border border-slate-100 shadow-none'"
      :open="openFirst && index === 0"
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
</template>

<script setup>
defineProps({
  openFirst: { type: Boolean, default: true },
  bordered: { type: Boolean, default: false },
})

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
