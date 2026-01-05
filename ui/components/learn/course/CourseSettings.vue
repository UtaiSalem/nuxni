<template>
  <div class="bg-white p-6 shadow rounded-lg">
      <h3 class="text-xl font-bold mb-4">Course Settings</h3>
      <form class="space-y-4">
          <input type="text" :value="course.name" class="w-full border rounded p-2" />
          <button class="bg-blue-600 text-white px-4 py-2 rounded">Save Settings</button>
      </form>
  </div>
</template>
<script setup>
defineProps({
    course: Object,
    isCourseAdmin: Boolean
})
</script>
