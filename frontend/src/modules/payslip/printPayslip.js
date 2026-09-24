import { APP_NAME } from '@/utils/constants'
import { formatDayMonth, formatMoney, formatMonth } from '@/utils/formatters'

const escape = (text = '') =>
  String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c])

const rows = (items) =>
  items
    .map(
      (i) => `<tr><td>${escape(i.label)}${i.note ? `<small>${escape(i.note)}</small>` : ''}</td><td>${formatMoney(i.amount)}</td></tr>`,
    )
    .join('')

/**
 * เปิดสลิปแบบจัดหน้าสำหรับพิมพ์ในหน้าต่างใหม่ แล้วสั่งพิมพ์
 * (ผู้ใช้เลือก "บันทึกเป็น PDF" ได้จากหน้าต่างพิมพ์ของเครื่อง)
 */
export function printPayslip(slip) {
  const win = window.open('', '_blank')
  if (!win) return false

  win.document.write(`<!doctype html>
<html lang="th"><head><meta charset="utf-8">
<title>สลิปเงินเดือน ${escape(formatMonth(slip.month))} - ${escape(slip.employee.name)}</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;800&family=Prompt:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  @page { size: A4; margin: 18mm; }
  * { box-sizing: border-box; }
  body { font-family: 'Prompt', sans-serif; color: #0f172a; font-size: 13px; margin: 0; }
  .num { font-family: 'Plus Jakarta Sans', 'Prompt', sans-serif; }
  header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #10b981; padding-bottom: 12px; }
  h1 { font-size: 20px; margin: 0; }
  .muted { color: #64748b; }
  .info { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 24px; margin: 16px 0; }
  .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
  table { width: 100%; border-collapse: collapse; }
  th { text-align: left; background: #f1f5f9; padding: 8px 10px; font-weight: 600; }
  td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
  td:last-child, th:last-child { text-align: right; font-family: 'Plus Jakarta Sans', 'Prompt', sans-serif; }
  td small { display: block; color: #64748b; font-size: 11px; }
  tfoot td { font-weight: 600; border-bottom: none; }
  .net { margin-top: 20px; padding: 16px 20px; border-radius: 12px; background: #ecfdf5; display: flex; justify-content: space-between; align-items: center; }
  .net strong { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 26px; color: #059669; }
  footer { margin-top: 28px; font-size: 11px; color: #94a3b8; text-align: center; }
</style></head><body>
<header>
  <div><h1>สลิปเงินเดือน</h1><div class="muted">ประจำเดือน ${escape(formatMonth(slip.month))}</div></div>
  <div style="text-align:right"><strong>${APP_NAME}</strong><div class="muted">วันที่จ่าย ${escape(formatDayMonth(slip.payDate, true))}</div></div>
</header>
<div class="info">
  <div><span class="muted">ชื่อ-สกุล:</span> ${escape(slip.employee.name)}</div>
  <div><span class="muted">รหัสพนักงาน:</span> <span class="num">${escape(slip.employee.employeeCode)}</span></div>
  <div><span class="muted">ตำแหน่ง:</span> ${escape(slip.employee.position)}</div>
  <div><span class="muted">แผนก:</span> ${escape(slip.employee.department)}</div>
  <div><span class="muted">โอนเข้า:</span> ${escape(slip.bank.name)} <span class="num">${escape(slip.bank.account)}</span></div>
</div>
<div class="grid">
  <table><thead><tr><th>รายได้</th><th>บาท</th></tr></thead>
    <tbody>${rows(slip.earnings)}</tbody>
    <tfoot><tr><td>รวมรายได้</td><td>${formatMoney(slip.totalEarnings)}</td></tr></tfoot></table>
  <table><thead><tr><th>รายการหัก</th><th>บาท</th></tr></thead>
    <tbody>${rows(slip.deductions)}</tbody>
    <tfoot><tr><td>รวมรายการหัก</td><td>${formatMoney(slip.totalDeductions)}</td></tr></tfoot></table>
</div>
<div class="net"><span>เงินได้สุทธิ</span><strong>฿ ${formatMoney(slip.netPay)}</strong></div>
<footer>เอกสารนี้ออกโดยระบบอิเล็กทรอนิกส์ ไม่ต้องมีลายมือชื่อ</footer>
<script>document.fonts.ready.then(() => setTimeout(() => window.print(), 300))</script>
</body></html>`)
  win.document.close()
  return true
}
