<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 animate-pulse">
    <!-- Header with Avatar and Radial Progress -->
    <div class="flex items-start gap-3">
      <!-- Avatar Skeleton -->
      <div class="w-12 h-12 rounded-full bg-gray-300 dark:bg-gray-600"></div>
      
      <!-- Name/Username Skeleton -->
      <div class="flex-1 min-w-0 space-y-2">
        <div class="h-4 w-3/4 rounded bg-gray-300 dark:bg-gray-600"></div>
        <div class="h-3 w-1/2 rounded bg-gray-300 dark:bg-gray-600"></div>
        <div class="h-2 w-2/3 rounded bg-gray-200 dark:bg-gray-700 mt-1"></div>
      </div>
      
      <!-- Radial Progress Skeleton -->
      <div class="w-16 h-16 rounded-full bg-gray-200 dark:bg-gray-700 flex-shrink-0"></div>
    </div>
    
    <!-- Progress Bars Skeleton -->
    <div class="mt-4 space-y-3">
      <!-- Bar 1 -->
      <div>
        <div class="flex justify-between mb-1">
          <div class="h-3 w-16 rounded bg-gray-200 dark:bg-gray-700"></div>
          <div class="h-3 w-8 rounded bg-gray-200 dark:bg-gray-700"></div>
        </div>
        <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700"></div>
      </div>
      
      <!-- Bar 2 -->
      <div>
        <div class="flex justify-between mb-1">
          <div class="h-3 w-20 rounded bg-gray-200 dark:bg-gray-700"></div>
          <div class="h-3 w-8 rounded bg-gray-200 dark:bg-gray-700"></div>
        </div>
        <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700"></div>
      </div>
      
      <!-- Bar 3 -->
      <div>
        <div class="flex justify-between mb-1">
          <div class="h-3 w-16 rounded bg-gray-200 dark:bg-gray-700"></div>
          <div class="h-3 w-8 rounded bg-gray-200 dark:bg-gray-700"></div>
        </div>
        <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700"></div>
      </div>
    </div>
    
    <!-- Stats Row Skeleton -->
    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 grid grid-cols-3 gap-2">
      <div class="text-center space-y-1">
        <div class="h-5 w-10 mx-auto rounded bg-gray-200 dark:bg-gray-700"></div>
        <div class="h-2 w-12 mx-auto rounded bg-gray-200 dark:bg-gray-700"></div>
      </div>
      <div class="text-center space-y-1">
        <div class="h-5 w-10 mx-auto rounded bg-gray-200 dark:bg-gray-700"></div>
        <div class="h-2 w-14 mx-auto rounded bg-gray-200 dark:bg-gray-700"></div>
      </div>
      <div class="text-center space-y-1">
        <div class="h-5 w-10 mx-auto rounded bg-gray-200 dark:bg-gray-700"></div>
        <div class="h-2 w-12 mx-auto rounded bg-gray-200 dark:bg-gray-700"></div>
      </div>
    </div>
  </div>
</template>
