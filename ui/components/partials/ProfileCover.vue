<template>
  <div class="relative bg-white shadow rounded-lg overflow-hidden mb-4">
      <div class="h-64 bg-gray-300 w-full bg-cover bg-center" :style="{ backgroundImage: `url(${coverImage})` }"></div>
      <div class="absolute bottom-4 left-4 flex items-end">
          <img :src="logoImage" class="w-32 h-32 rounded-full border-4 border-white shadow-lg bg-white" />
          <div class="ml-4 mb-2">
              <h1 class="text-3xl font-bold text-white shadow-black drop-shadow-md">{{ coverHeader }}</h1>
              <p class="text-white drop-shadow-md">{{ coverSubheader }}</p>
          </div>
      </div>
  </div>
</template>
<script setup>
defineProps({
    coverImage: String,
    logoImage: String,
    coverHeader: String,
    coverSubheader: String,
    model: String,
    modelTable: String,
    modelableId: Number,
    modelableType: String,
    modelableRoute: String,
    modelData: Object,
    subModelData: Array,
    subModelNameTh: String
})
</script>
