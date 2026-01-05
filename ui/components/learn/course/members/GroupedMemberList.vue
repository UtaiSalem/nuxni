<template>
  <div class="space-y-4">
      <div v-for="group in groups" :key="group.id" class="bg-white p-4 shadow rounded">
          <h4 class="font-bold">{{ group.name }}</h4>
          <p>{{ group.members?.length || 0 }} Members</p>
      </div>
  </div>
</template>
<script setup>
defineProps({
    groups: Array
})
</script>
