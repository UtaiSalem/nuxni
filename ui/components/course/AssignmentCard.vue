<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed, ref } from 'vue'
import RichTextViewer from '~/components/RichTextViewer.vue'

interface Props {
  assignment: any
  isCourseAdmin?: boolean
  courseId: string | number
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const emit = defineEmits<{
  'edit': [assignment: any]
  'delete': [assignmentId: number]
  'click': [assignment: any]
}>()

const showFullContent = ref(false)

// Format date
const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('th-TH', {
    dateStyle: 'medium',
    timeStyle: 'short'
  })
}

// Get status badge
const getStatusBadge = computed(() => {
  if (props.assignment.status === 1 || props.assignment.is_published) {
    return { 
      text: 'เผยแพร่',
      color: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
      icon: 'fluent:checkmark-circle-16-filled'
    }
  }
  return { 
    text: 'ร่าง',
    color: 'bg-gray-100 text-gray-700 dark:bg-gray-700/30 dark:text-gray-400',
    icon: 'fluent:drafts-16-regular'
  }
})

  const isOverdue = computed(() => {
  if (!props.assignment.due_date) return false
  return new Date(props.assignment.due_date) < new Date()
})

const isSubmitted = computed(() => {
    return props.assignment.answer_status === 'submitted' || props.assignment.answer_status === 'graded'
})

const showGrading = ref(false)

const toggleGrading = () => {
  showGrading.value = !showGrading.value
}
</script>

<script lang="ts">
import AssignmentGradingView from './AssignmentGradingView.vue'
import AssignmentSubmissionForm from './AssignmentSubmissionForm.vue'

export default {
    components: {
        AssignmentGradingView,
        AssignmentSubmissionForm
    }
}
</script>

<template>
  <article 
    class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden mb-6 border border-gray-200 dark:border-gray-700 group"
  >
    <!-- Header Section (Cover) -->
    <div class="relative">
       <!-- Gradient Cover -->
       <div class="relative h-32 bg-gradient-to-br from-violet-600 via-indigo-600 to-cyan-600 dark:from-violet-900 dark:via-indigo-900 dark:to-cyan-900 overflow-hidden rounded-t-2xl">
          <div class="absolute inset-0 bg-black/10"></div>
          
          <!-- Center Icon -->
          <div class="w-full h-full flex items-center justify-center">
            <Icon icon="fluent:clipboard-task-24-filled" class="w-16 h-16 text-white/20 animate-pulse" />
          </div>
       </div>

       <!-- Badges -->
       <div class="absolute top-4 left-4 flex flex-wrap gap-2">
          <!-- Status -->
          <span 
            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold shadow-lg backdrop-blur-md bg-white/90 dark:bg-gray-900/80 ring-1 ring-white/20 transition-transform hover:scale-105"
            :class="getStatusBadge.color"
          >
             <Icon :icon="getStatusBadge.icon" class="w-3.5 h-3.5" />
             {{ getStatusBadge.text }}
          </span>

          <!-- Points -->
          <span v-if="assignment.points" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-500/90 backdrop-blur-md text-white text-xs font-bold shadow-lg ring-1 ring-white/20 transition-transform hover:scale-105">
             <Icon icon="fluent:star-20-filled" class="w-3.5 h-3.5" />
             {{ assignment.points }} คะแนน
          </span>
       </div>

       <!-- Admin Actions -->
       <div v-if="isCourseAdmin" class="absolute top-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
          <button 
            @click.stop="emit('edit', assignment)"
            class="p-2 bg-white/90 dark:bg-gray-800/90 rounded-lg hover:bg-white dark:hover:bg-gray-800 text-blue-600 shadow-lg backdrop-blur hover:scale-105 transition-all"
            title="แก้ไข"
          >
            <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
          </button>
          <button 
            @click.stop="emit('delete', assignment.id)"
            class="p-2 bg-white/90 dark:bg-gray-800/90 rounded-lg hover:bg-white dark:hover:bg-gray-800 text-red-600 shadow-lg backdrop-blur hover:scale-105 transition-all"
            title="ลบ"
          >
            <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
          </button>
       </div>
    </div>

    <!-- Content Section -->
    <div class="p-6">
       <!-- Title & Meta -->
       <div class="mb-4">
          <button @click="emit('click', assignment)" class="text-left w-full group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
             <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 leading-tight">
                {{ assignment.title || assignment.name }}
             </h2>
          </button>
          
          <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mt-2">
             <div v-if="assignment.due_date" class="flex items-center gap-1.5" :class="isOverdue ? 'text-red-500 font-medium' : ''">
                <Icon icon="fluent:calendar-clock-24-regular" class="w-4 h-4" />
                <span>กำหนดส่ง: {{ formatDate(assignment.due_date) }}</span>
                <span v-if="isOverdue" class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full ml-1">เกินกำหนด</span>
             </div>
          </div>
       </div>

       <!-- Description -->
       <div v-if="assignment.description" class="prose prose-sm dark:prose-invert max-w-none mb-6">
          <RichTextViewer :content="assignment.description" />
       </div>

       <!-- Action Footer -->
       <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
          <!-- Student Answer Status -->
          <div v-if="assignment.answer_status !== undefined" class="flex items-center gap-2">
             <span class="text-sm font-medium text-gray-500">สถานะ:</span>
             <span 
                class="px-3 py-1 rounded-full text-xs font-bold border flex items-center gap-1.5"
                :class="assignment.answer_status === 'submitted' || assignment.answer_status === 'graded'
                    ? 'bg-emerald-50 text-emerald-600 border-emerald-100' 
                    : 'bg-orange-50 text-orange-500 border-orange-100'"
             >
                <Icon :icon="assignment.answer_status === 'submitted' || assignment.answer_status === 'graded' ? 'fluent:checkmark-circle-16-filled' : 'fluent:circle-16-regular'" />
                {{ (assignment.answer_status === 'submitted' || assignment.answer_status === 'graded') ? 'ส่งแล้ว' : 'ยังไม่ส่ง' }}
             </span>
          </div>
          <div v-else></div>

          <button 
             @click.stop="isCourseAdmin ? toggleGrading() : emit('click', assignment)"
            class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors shadow-md hover:shadow-lg font-medium text-sm"
          >
             <span v-if="isCourseAdmin">
                {{ showGrading ? 'ซ่อนคำตอบ' : 'ตรวจคำตอบ / ดูรายชื่อ' }}
             </span>
             <span v-else-if="isSubmitted">ดูรายละเอียด / แก้ไข</span>
             <span v-else>ดูรายละเอียด</span>
             
             <Icon :icon="showGrading ? 'fluent:chevron-up-24-filled' : 'fluent:chevron-down-24-filled'" class="w-4 h-4 ml-1" v-if="isCourseAdmin" />
             <Icon icon="fluent:arrow-right-24-filled" class="w-4 h-4" v-else />
          </button>
       </div>
       
       <!-- Admin Grading View (Collapsible) -->
       <div v-if="isCourseAdmin && showGrading" class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
           <AssignmentGradingView 
             :assignment="assignment"
             :courseId="courseId"
           />
       </div>

       <!-- Student Direct Submission Form -->
       <div v-if="!isCourseAdmin && !isSubmitted" class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
          <AssignmentSubmissionForm 
             :assignment="assignment"
             :courseId="courseId"
             @submitted="emit('click', assignment)" 
          />
       </div>
    </div>
  </article>
</template>
