<script setup lang="ts">
import { Icon } from '@iconify/vue'

const props = withDefaults(defineProps<{
  rating: number
  size?: 'sm' | 'md' | 'lg'
  interactive?: boolean
  showValue?: boolean
  reviewsCount?: number
}>(), {
  size: 'md',
  interactive: false,
  showValue: false,
  reviewsCount: 0
})

const emit = defineEmits<{
  (e: 'rate', rating: number): void
}>()

const hoverRating = ref(0)

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm': return 'w-4 h-4'
    case 'lg': return 'w-7 h-7'
    default: return 'w-5 h-5'
  }
})

const textSizeClasses = computed(() => {
  switch (props.size) {
    case 'sm': return 'text-xs'
    case 'lg': return 'text-lg'
    default: return 'text-sm'
  }
})

const displayRating = computed(() => {
  if (props.interactive && hoverRating.value > 0) {
    return hoverRating.value
  }
  return props.rating
})

const getStarType = (index: number): 'full' | 'half' | 'empty' => {
  const currentRating = displayRating.value
  if (index <= Math.floor(currentRating)) {
    return 'full'
  }
  if (index === Math.ceil(currentRating) && currentRating % 1 >= 0.5) {
    return 'half'
  }
  return 'empty'
}

const getStarIcon = (type: 'full' | 'half' | 'empty') => {
  switch (type) {
    case 'full': return 'fluent:star-24-filled'
    case 'half': return 'fluent:star-half-24-regular'
    default: return 'fluent:star-24-regular'
  }
}

const handleClick = (index: number) => {
  if (props.interactive) {
    emit('rate', index)
  }
}

const handleMouseEnter = (index: number) => {
  if (props.interactive) {
    hoverRating.value = index
  }
}

const handleMouseLeave = () => {
  if (props.interactive) {
    hoverRating.value = 0
  }
}
</script>

<template>
  <div class="flex items-center gap-1">
    <div 
      class="flex items-center"
      :class="{ 'cursor-pointer': interactive }"
      @mouseleave="handleMouseLeave"
    >
      <button
        v-for="index in 5"
        :key="index"
        type="button"
        :disabled="!interactive"
        class="p-0.5 transition-transform"
        :class="{ 
          'hover:scale-110': interactive,
          'cursor-default': !interactive
        }"
        @click="handleClick(index)"
        @mouseenter="handleMouseEnter(index)"
      >
        <Icon
          :icon="getStarIcon(getStarType(index))"
          :class="[
            sizeClasses,
            getStarType(index) === 'empty' 
              ? 'text-gray-300 dark:text-gray-600' 
              : 'text-yellow-400'
          ]"
        />
      </button>
    </div>
    
    <!-- Rating Value -->
    <span 
      v-if="showValue" 
      :class="[textSizeClasses, 'font-semibold text-gray-700 dark:text-gray-300 ml-1']"
    >
      {{ rating > 0 ? rating.toFixed(1) : '-' }}
    </span>
    
    <!-- Reviews Count -->
    <span 
      v-if="reviewsCount > 0" 
      :class="[textSizeClasses, 'text-gray-500 dark:text-gray-400']"
    >
      ({{ reviewsCount }})
    </span>
  </div>
</template>
