<template>
  <div class="bg-white p-4 shadow rounded">
      <h3 class="font-bold mb-4 ml-4">Members Progress - {{ groupName }}</h3>
      <table class="w-full text-left">
        <thead>
          <tr>
            <th class="p-2">Name</th>
            <th class="p-2">Progress</th>
            <th class="p-2">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="member in members" :key="member.id" class="border-t">
            <td class="p-2">{{ member.member_name }}</td>
            <td class="p-2">{{ member.progress || 0 }}%</td>
            <td class="p-2">
              <button @click="$emit('view', member)" class="text-blue-500">View</button>
            </td>
          </tr>
        </tbody>
      </table>
  </div>
</template>
<script setup>
defineProps({
    groupName: String,
    members: Array,
    isCourseAdmin: Boolean
})
</script>
