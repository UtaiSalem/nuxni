<script setup lang="ts">
import CreateNewCourse from '~/pages/Learn/Course/CreateNewCourse.vue'

definePageMeta({
  middleware: 'auth',
  layout: false
})
</script>

<template>
  <CreateNewCourse />
</template>
