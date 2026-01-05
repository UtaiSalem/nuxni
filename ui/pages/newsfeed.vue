<script setup lang="ts">
definePageMeta({
  middleware: [
    function (to, from) {
      return navigateTo('/play/newsfeed')
    }
  ]
})
</script>

<template>
  <div>Redirecting...</div>
</template>
