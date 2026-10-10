<template>
  <span class="employee-avatar" :style="{ width: size + 'px', height: size + 'px', fontSize: Math.round(size * 0.36) + 'px' }" :aria-label="name || 'พนักงาน'" role="img">
    <img v-if="usableImage" :src="src" :alt="name || 'รูปพนักงาน'" @error="failed = true" />
    <span v-else aria-hidden="true">{{ initial }}</span>
  </span>
</template>
<script setup>
import { computed, ref, watch } from 'vue'
import { getInitials } from '@/utils/formatters'
const props = defineProps({ src: { type: String, default: null }, name: { type: String, default: '' }, size: { type: Number, default: 44 } })
const failed = ref(false)
watch(() => [props.src, props.name], () => { failed.value = false })
const usableImage = computed(() => props.src && !failed.value && !/(?:user_bg|default[-_]?avatar)\.(?:jpg|png|svg)(?:[?#]|$)/i.test(props.src))
const initial = computed(() => getInitials(props.name || '') || '?')
</script>
<style scoped>
.employee-avatar { display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden; border-radius:50%; background:#d1fae5; color:#047857; font-weight:700; line-height:1; vertical-align:middle; }
.employee-avatar img { display:block; width:100%; height:100%; object-fit:cover; }
</style>
