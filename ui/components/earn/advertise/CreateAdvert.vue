<template>
  <div class="bg-white p-6 shadow rounded-lg">
      <h2 class="text-xl font-bold mb-4">Create Advertisement</h2>
      <form class="space-y-4">
          <input type="text" placeholder="Ad Title" class="w-full border rounded p-2" />
          <textarea placeholder="Ad Content" class="w-full border rounded p-2"></textarea>
          <button class="bg-green-600 text-white px-4 py-2 rounded">Submit Advertisement</button>
      </form>
  </div>
</template>
<script setup>
defineProps({
    Supporter: Object
})
</script>
