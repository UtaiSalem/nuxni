<template>
    <div class="p-4 border rounded shadow bg-white mt-4">
        <h3 class="font-bold">Create New Assignment</h3>
        <p class="text-gray-500">Assignment creation form placeholder.</p>
    </div>
</template>
<script setup>
defineProps({
    assignmentableType: String,
    assignmentableId: Number,
    assignmentNameTh: String,
    assignmentApiRoute: String
})
</script>
