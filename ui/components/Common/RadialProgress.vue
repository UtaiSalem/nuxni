<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps({
  percentage: {
    type: Number,
    required: true,
    default: 0
  },
  size: {
    type: Number,
    default: 60
  },
  strokeWidth: {
    type: Number,
    default: 6
  },
  color: {
    type: String,
    default: 'text-purple-600'
  },
  trackColor: {
    type: String,
    default: 'text-gray-200 dark:text-gray-700'
  }
})

const radius = computed(() => (props.size / 2) - (props.strokeWidth / 2))
const circumference = computed(() => 2 * Math.PI * radius.value)
const offset = computed(() => circumference.value - (props.percentage / 100) * circumference.value)
</script>

<template>
  <div class="relative flex items-center justify-center" :style="{ width: size + 'px', height: size + 'px' }">
    <!-- SVG Ring -->
    <svg class="transform -rotate-90 w-full h-full">
      <!-- Track -->
      <circle
        :cx="size / 2"
        :cy="size / 2"
        :r="radius"
        :stroke-width="strokeWidth"
        fill="transparent"
        :class="trackColor"
        stroke="currentColor"
      />
      <!-- Progress -->
      <circle
        :cx="size / 2"
        :cy="size / 2"
        :r="radius"
        :stroke-width="strokeWidth"
        fill="transparent"
        :class="color"
        stroke="currentColor"
        stroke-linecap="round"
        :stroke-dasharray="circumference"
        :stroke-dashoffset="offset"
        class="transition-all duration-1000 ease-out"
      />
    </svg>
    
    <!-- Inner Content Slot -->
    <div class="absolute inset-0 flex items-center justify-center">
      <slot></slot>
    </div>
  </div>
</template>
