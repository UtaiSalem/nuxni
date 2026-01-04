<template>
  <div class="bg-white p-4 shadow rounded-lg mb-4">
      <h4 class="font-bold">{{ advert.title }}</h4>
      <p>{{ advert.content }}</p>
      <div class="mt-2 text-sm text-gray-500">
          Sponsored Link
      </div>
  </div>
</template>
<script setup>
defineProps({
    advert: Object,
    index: Number
})
</script>
