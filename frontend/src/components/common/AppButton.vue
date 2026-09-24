<template>
  <v-btn v-bind="$attrs" :color="color" :variant="variant" :loading="loading">
    <AppIcon v-if="icon" :name="icon" class="mr-1.5" />
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
