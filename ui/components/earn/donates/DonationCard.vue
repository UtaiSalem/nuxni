<template>
  <div class="bg-white p-4 shadow rounded-lg mb-4">
      <div class="flex items-center">
          <img :src="donate.donor?.avatar || '/storage/images/default_avatar.png'" class="w-12 h-12 rounded-full mr-4" />
          <div>
              <h4 class="font-bold">{{ donate.donor?.name || 'Unknown Donor' }}</h4>
              <p class="text-sm text-gray-600">Donated Points</p>
          </div>
      </div>
      <div class="mt-4">
          <button class="bg-blue-500 text-white px-4 py-2 rounded" @click="$emit('getDonateRequest')">Receive Donation</button>
      </div>
  </div>
</template>
<script setup>
defineProps({
    donate: Object,
    isProcessing: Boolean
})
</script>
