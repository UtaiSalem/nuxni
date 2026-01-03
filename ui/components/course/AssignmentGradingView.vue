<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  assignment: any
  courseId: number | string
}>()

const api = useApi()
const swal = useSweetAlert()
const { getAvatarUrl } = useAvatar()

// State
const allAnswers = ref<any[]>([])
const groups = ref<any[]>([])
const selectedGroup = ref<number | null>(null)
const isFetchingAnswers = ref(false)
const isLoadingGroups = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const hasLoadedAnswers = ref(false)
const totalAnswers = ref(0) // New total count state

const fetchGroups = async () => {
    try {
        const res = await api.get(`/api/courses/${props.courseId}/groups`)
        groups.value = res.groups || []
    } catch (e) {
        console.error(e)
        groups.value = []
    }
}

const fetchAllAnswers = async (page = 1, reset = false) => {
  isFetchingAnswers.value = true
  try {
    const response = await api.get(`/api/assignments/${props.assignment.id}/answers`, {
        params: {
            page: page,
            group_id: selectedGroup.value || 'all'
        }
    })
    
    // Map response.data
    const newAnswers = (response.data || []).map((a: any) => ({
      ...a,
      points: a.points, 
      originalPoints: a.points,
      isUpdating: false,
      isExpanded: a.points === null // Auto collapse if graded
    }))

    if (reset) {
        allAnswers.value = newAnswers
    } else {
        allAnswers.value = [...allAnswers.value, ...newAnswers]
    }
    
    currentPage.value = response.meta.current_page
    lastPage.value = response.meta.last_page
    // Update total count from metadata
    totalAnswers.value = response.meta.total
    hasLoadedAnswers.value = true
    
  } catch (error) {
    console.error('Failed to fetch answers:', error)
  } finally {
    isFetchingAnswers.value = false
  }
}

// Watch group
watch(selectedGroup, () => {
    if (hasLoadedAnswers.value) {
        fetchAllAnswers(1, true)
    }
})

// Grading Logic
const updateGrade = async (answer: any) => {
  try {
    answer.isUpdating = true
    await api.post(`/api/assignments/${props.assignment.id}/answers/${answer.id}/set-points`, {
      points: answer.points,
      course_id: props.courseId
    })
    
    answer.originalPoints = answer.points
    answer.isUpdating = false
    answer.isExpanded = false
    swal.toast('บันทึกคะแนนเรียบร้อย', 'success')
  } catch (error) {
    console.error('Grading error:', error)
    swal.toast('บันทึกคะแนนไม่สำเร็จ', 'error')
    answer.isUpdating = false
  }
}

const cancelGrade = (answer: any) => {
  answer.points = answer.originalPoints
}

// Helpers
const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('th-TH', {
    dateStyle: 'medium',
    timeStyle: 'short'
  })
}

const isLate = (answer: any) => {
    if (!props.assignment.due_date) return false
    return new Date(answer.created_at) > new Date(props.assignment.due_date)
}

const userAvatar = (user: any) => getAvatarUrl(user)

const getAnswerCardClass = (answer: any) => {
   if (answer.points === null) return 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700'
   const passing = props.assignment.passing_score ?? 0
   return answer.points >= passing 
      ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800'
      : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'
}

onMounted(() => {
    fetchGroups()
    // Initial fetch?
    fetchAllAnswers(1, true)
    window.addEventListener('scroll', checkScroll)
})

onUnmounted(() => {
    window.removeEventListener('scroll', checkScroll)
})

// Back to Top
const showBackToTop = ref(false)

const checkScroll = () => {
    showBackToTop.value = window.scrollY > 300
}

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>

<template>
    <div class="space-y-4">
        <!-- Header / Filter -->
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
               <Icon icon="fluent:people-community-24-regular" class="w-5 h-5" />
               การส่งงาน ({{ hasLoadedAnswers ? totalAnswers : (props.assignment.answer_count || 0) }})
            </h2>

             <select 
               v-if="groups && groups.length > 0"
               v-model="selectedGroup" 
               class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm focus:ring-2 focus:ring-orange-500 outline-none"
            >
               <option :value="null">ทั้งหมด (All)</option>
               <option v-for="group in groups" :key="group.id" :value="group.id">
                  {{ group.name }}
               </option>
            </select>
        </div>

        <div v-if="allAnswers.length === 0 && !isFetchingAnswers" class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 text-center text-gray-500 border border-dashed border-gray-300 dark:border-gray-700 text-sm">
            {{ selectedGroup ? 'ไม่มีงานที่ส่งในกลุ่มนี้' : 'ยังไม่มีนักเรียนส่งงาน' }}
        </div>
        
        <div v-if="isFetchingAnswers && allAnswers.length === 0" class="flex justify-center py-8">
             <Icon icon="eos-icons:loading" class="w-8 h-8 text-orange-500" />
        </div>

        <div v-else class="grid gap-3">
             <div v-for="answer in allAnswers" :key="answer.id" 
                   class="rounded-xl p-4 border shadow-sm transition-shadow hover:shadow-md"
                   :class="getAnswerCardClass(answer)"
              >
                  <div class="flex items-start gap-3">
                     <img :src="userAvatar(answer.student)" class="w-10 h-10 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-700" />
                     <div class="flex-1 min-w-0">
                        <div class="flex justify-between">
                           <div>
                              <h3 class="font-bold text-gray-900 dark:text-white text-sm">{{ answer.member_name || answer.student?.username || 'Unknown Student' }}</h3>
                              <p class="text-xs flex items-center gap-1" :class="isLate(answer) ? 'text-red-500 font-bold' : 'text-green-600'">
                                 {{ formatDate(answer.created_at) }}
                                 <span v-if="isLate(answer)">(Late)</span>
                              </p>
                           </div>
                           <div class="text-right flex items-center gap-2">
                               <button 
                                 @click="answer.isExpanded = !answer.isExpanded"
                                 class="p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-400"
                               >
                                  <Icon :icon="answer.isExpanded ? 'fluent:chevron-up-24-regular' : 'fluent:chevron-down-24-regular'" class="w-5 h-5" />
                               </button>

                               <div class="text-xl font-bold" :class="answer.points ? 'text-green-600' : 'text-gray-300'">
                                  {{ answer.points ?? '-' }} <span class="text-xs font-normal text-gray-400">/ {{ assignment.points }}</span>
                               </div>
                           </div>
                        </div>

                        <div v-show="answer.isExpanded">
                             <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap border border-gray-100 dark:border-gray-800">
                                {{ answer.content }}
                                <div v-if="answer.images?.length" class="mt-3 flex flex-wrap gap-2">
                                   <img v-for="img in answer.images" :key="img.id" :src="img.full_url || img.image_url" class="w-12 h-12 object-cover rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer" />
                                </div>
                             </div>
                        </div>

                       <!-- Grading Slider (Compact) -->
                       <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center gap-3">
                           <input 
                               type="number" 
                               v-model.number="answer.points"
                               :min="0" 
                               :max="assignment.points"
                               class="w-16 px-2 py-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-center font-bold text-sm focus:ring-2 focus:ring-orange-500 outline-none"
                           />
                           <input 
                               type="range" 
                               v-model.number="answer.points"
                               :min="0" 
                               :max="assignment.points"
                               class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-orange-500"
                           />
                           <button 
                               @click="updateGrade(answer)"
                               :disabled="answer.isUpdating || answer.points === answer.originalPoints"
                               class="px-3 py-1 text-xs font-bold text-white bg-orange-500 rounded-lg hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                           >
                               {{ answer.isUpdating ? '...' : 'บันทึก' }}
                           </button>
                        </div>
                     </div>
                  </div>
              </div>

               <!-- Pagination -->
               <div v-if="currentPage < lastPage" class="flex justify-center mt-4">
                    <button 
                        @click="fetchAllAnswers(currentPage + 1)" 
                        :disabled="isFetchingAnswers"
                        class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 font-medium disabled:opacity-50 flex items-center gap-2"
                    >
                        {{ isFetchingAnswers ? 'กำลังโหลด...' : 'โหลดเพิ่ม' }}
                    </button>
               </div>
        </div>

        <!-- Back to Top Button -->
        <transition 
           enter-active-class="transition ease-out duration-300"
           enter-from-class="opacity-0 translate-y-10"
           enter-to-class="opacity-100 translate-y-0"
           leave-active-class="transition ease-in duration-300"
           leave-from-class="opacity-100 translate-y-0"
           leave-to-class="opacity-0 translate-y-10"
        >
             <button 
               v-show="showBackToTop"
               @click="scrollToTop"
               class="fixed bottom-6 right-6 p-3 bg-orange-500 text-white rounded-full shadow-lg hover:bg-orange-600 transition-all z-50 animate-bounce-slow"
               title="Back to Top"
             >
               <Icon icon="fluent:arrow-up-24-filled" class="w-6 h-6" />
             </button>
        </transition>
    </div>
</template>
