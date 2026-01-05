<template>
  <div class="bg-white p-4 shadow rounded mt-4">
      <h4 class="font-bold mb-2">Non-Grouped Members</h4>
      <ul>
          <li v-for="member in members" :key="member.id" class="border-b py-2">
              {{ member.name }}
          </li>
      </ul>
  </div>
</template>
<script setup>
defineProps({
    members: Array,
    groups: Array
})
</script>
