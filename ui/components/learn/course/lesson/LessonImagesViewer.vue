<template>
    <div v-if="images?.length" class="grid grid-cols-2 gap-2 mt-4">
        <img v-for="img in images" :key="img.id" :src="img.full_url" class="w-full h-auto rounded" />
    </div>
</template>
<script setup>
defineProps({
    model_id: Number,
    images: Array,
    edit: Boolean
})
</script>
