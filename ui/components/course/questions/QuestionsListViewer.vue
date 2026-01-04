<template>
  <div class="space-y-4">
      <div v-for="q in questions" :key="q.id" class="p-4 bg-white shadow rounded">
          <p class="font-bold">{{ q.question_text }}</p>
          <!-- Placeholder options -->
          <div class="mt-2 text-gray-500">Options go here...</div>
      </div>
  </div>
</template>
<script setup>
defineProps({
    questions: Array,
    quizId: Number,
    quiz: Object,
    quizResult: Object
})
</script>
