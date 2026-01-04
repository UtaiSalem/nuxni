<template>
  <div class="bg-white p-4 shadow rounded mb-4">
      <div class="flex items-center mb-2">
          <div class="font-bold">{{ activity.actor?.name || 'User' }}</div>
      </div>
      <p>{{ activity.data?.content || 'Post content placeholder' }}</p>
  </div>
</template>
<script setup>
defineProps({
    activity: Object
})
</script>
