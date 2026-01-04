<template>
  <div class="bg-white p-4 shadow rounded mb-4">
      <textarea class="w-full border rounded p-2" placeholder="Write something to the academy..."></textarea>
      <div class="flex justify-end mt-2">
          <button class="bg-blue-500 text-white px-4 py-2 rounded">Post</button>
      </div>
  </div>
</template>
<script setup>
defineProps({
    academy_id: Number
})
</script>
