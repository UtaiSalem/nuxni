<template>
  <div class="bg-white p-4 shadow rounded mb-4 border-l-4 border-blue-500">
      <h4 class="font-bold">Donation Request</h4>
      <p>Donor: {{ donate.donor?.name || 'Unknown' }}</p>
      <p>Points: {{ donate.point || 0 }}</p>
  </div>
</template>
<script setup>
defineProps({
    donate: Object
})
</script>
