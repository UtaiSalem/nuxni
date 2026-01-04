<template>
  <div class="bg-gray-200 h-64 w-full relative">
     <div class="h-full w-full bg-cover bg-center" :style="{ backgroundImage: `url(${cover})` }"></div>
     <div class="absolute -bottom-12 left-4 flex items-end">
        <img :src="logo" class="w-24 h-24 rounded-full border-4 border-white shadow" />
        <div class="ml-4 mb-4">
            <h1 class="text-2xl font-bold bg-white/80 px-2 rounded">{{ name }}</h1>
            <p class="text-gray-700 bg-white/80 px-2 rounded">{{ code }}</p>
        </div>
     </div>
  </div>
</template>

<script setup>
defineProps({
    logo: String,
    cover: String,
    name: String,
    code: String
})
</script>
