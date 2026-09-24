<template>
  <v-dialog v-model="open" max-width="420" :persistent="loading">
    <v-card>
      <v-card-title class="pt-5">{{ title }}</v-card-title>
      <v-card-text>{{ message }}</v-card-text>
      <v-card-actions class="px-6 pb-5">
        <v-spacer />
        <v-btn variant="text" :disabled="loading" @click="open = false">{{ cancelText }}</v-btn>
        <v-btn :color="color" variant="flat" :loading="loading" @click="emit('confirm')">
          {{ confirmText }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
const open = defineModel({ type: Boolean, default: false })

defineProps({
  title: { type: String, default: 'ยืนยันการทำรายการ' },
  message: { type: String, default: '' },
  confirmText: { type: String, default: 'ยืนยัน' },
  cancelText: { type: String, default: 'ยกเลิก' },
  color: { type: String, default: 'primary' },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['confirm'])
</script>
