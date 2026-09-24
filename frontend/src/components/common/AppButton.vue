<template>
  <v-btn v-bind="$attrs" :color="color" :variant="variant" :loading="loading">
    <v-icon v-if="icon" start :icon="icon" />
    <slot />
  </v-btn>
</template>

<script setup>
defineOptions({ inheritAttrs: false })

defineProps({
  color: { type: String, default: 'primary' },
  variant: { type: String, default: 'flat' },
  icon: { type: String, default: '' },
  loading: { type: Boolean, default: false },
})
</script>
