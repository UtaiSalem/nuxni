<template>
    <div class="bg-white shadow rounded-lg p-2 mb-4 flex gap-2 overflow-x-auto">
        <button class="px-4 py-2 rounded bg-blue-100 text-blue-700 font-bold">Home</button>
        <button class="px-4 py-2 rounded hover:bg-gray-100">About</button>
        <button class="px-4 py-2 rounded hover:bg-gray-100">Courses</button>
        <button class="px-4 py-2 rounded hover:bg-gray-100">Members</button>
    </div>
</template>
<script setup>
defineProps({
    academy: Object,
    activeTab: Number
})
</script>
