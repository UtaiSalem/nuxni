<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'
import Swal from 'sweetalert2'

const route = useRoute()
const courseId = route.params.id
const quizId = route.params.quizId
const api = useApi()

definePageMeta({
  middleware: ['auth', async (to) => {
      const courseStore = useCourseStore()
      if (!courseStore.currentCourse || courseStore.currentCourse.id != to.params.id) {
          try {
             await courseStore.fetchCourse(to.params.id as string)
          } catch (e) {
             console.error('Middleware fetch course error', e)
             return abortNavigation('Course not found')
          }
      }
      
      if (!courseStore.isCourseAdmin) {
          return navigateTo(`/courses/${to.params.id}`)
      }
  }]
})

const activeTab = ref('settings')
const isLoading = ref(false)
const isSaving = ref(false)
const errors = ref<string[]>([])

// Form Data
const form = reactive({
  title: '',
  description: '',
  start_date: new Date(),
  end_date: new Date(),
  time_limit: 60,
  passing_score: 50,
  is_active: true,
  shuffle_questions: false
})

const quiz = ref<any>(null)
const questions = ref<any[]>([])

// Fetch Data
const fetchData = async () => {
    isLoading.value = true
    try {
        const res = await api.get(`/api/courses/${courseId}/quizzes/${quizId}`)
        quiz.value = res.quiz
        
        // Populate Form
        form.title = quiz.value.title
        form.description = quiz.value.description
        form.start_date = new Date(quiz.value.start_date)
        form.end_date = new Date(quiz.value.end_date)
        form.time_limit = quiz.value.time_limit
        form.passing_score = quiz.value.passing_score
        form.is_active = !!quiz.value.is_active
        form.shuffle_questions = !!quiz.value.shuffle_questions

        // Questions
        questions.value = quiz.value.questions || []
    } catch (err) {
        console.error(err)
        Swal.fire('Error', 'Failed to load quiz data', 'error')
    } finally {
        isLoading.value = false
    }
}

onMounted(() => {
    fetchData()
})

// Validation
const isFormValid = computed(() => {
  return form.title.trim() !== '' && 
         form.time_limit > 0 && 
         form.passing_score >= 0 && 
         form.passing_score <= 100 &&
         (!form.start_date || !form.end_date || new Date(form.end_date) > new Date(form.start_date))
})

// Update Settings
const handleUpdate = async () => {
  if (!isFormValid.value || isSaving.value) return
  
  isSaving.value = true
  errors.value = []

  try {
    const payload = {
        title: form.title,
        description: form.description,
        time_limit: form.time_limit,
        passing_score: form.passing_score,
        is_active: form.is_active,
        shuffle_questions: form.shuffle_questions,
        start_date: form.start_date,
        end_date: form.end_date,
    }

    const res = await api.put(`/api/courses/${courseId}/quizzes/${quizId}`, payload)
    
    // Refresh data
    quiz.value = res.quiz || res.data?.quiz
    Swal.fire({
        icon: 'success',
        title: 'บันทึกสำเร็จ',
        timer: 1500,
        showConfirmButton: false
    })
  } catch (err: any) {
    console.error(err)
    if (err.data?.errors) {
      errors.value = Object.values(err.data.errors).flat() as string[]
    } else {
      errors.value = ['เกิดข้อผิดพลาดในการบันทึก']
    }
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: errors.value[0]
    })
  } finally {
    isSaving.value = false
  }
}

// Question Management
const questionModal = ref(false)
const editingQuestion = ref<any>(null)
const questionForm = reactive({
    text: '',
    points: 1,
    options: [
        { text: '', is_correct: false },
        { text: '', is_correct: false },
        { text: '', is_correct: false },
        { text: '', is_correct: false }
    ]
})

const openAddQuestion = () => {
    editingQuestion.value = null
    questionForm.text = ''
    questionForm.points = 1
    questionForm.options = [
        { text: '', is_correct: false },
        { text: '', is_correct: false },
        { text: '', is_correct: false },
        { text: '', is_correct: false }
    ]
    questionModal.value = true
}

const openEditQuestion = (q: any) => {
    editingQuestion.value = q
    questionForm.text = q.text
    questionForm.points = q.points
    // Mapping options if available, else default
    if (q.options && q.options.length) {
        questionForm.options = q.options.map((opt: any) => ({
            id: opt.id,
            text: opt.text,
            is_correct: !!opt.is_correct
        }))
    } else {
        questionForm.options = [
            { text: '', is_correct: false },
            { text: '', is_correct: false },
            { text: '', is_correct: false },
            { text: '', is_correct: false }
        ]
    }
    questionModal.value = true
}

const setCorrectOption = (index: number) => {
    // Single choice for now, unless multiple choice allowed later
    questionForm.options.forEach((opt, i) => {
        opt.is_correct = i === index
    })
}

const saveQuestion = async () => {
    // Basic validation
    if (!questionForm.text || !questionForm.options.some(o => o.is_correct)) {
        Swal.fire('กรุณากรอกข้อมูล', 'ต้องมีคำถามและเฉลยอย่างน้อย 1 ข้อ', 'warning')
        return
    }

    try {
        if (editingQuestion.value) {
            // Update logic here (omitted for now as backend might need specific logic)
             Swal.fire('Info', 'User update logic not fully implemented in this demo', 'info')
        } else {
            // Create Question
            const qRes = await api.post(`/api/courses/${courseId}/quizzes/${quizId}/questions`, {
                text: questionForm.text,
                points: questionForm.points
            })

            if (qRes.success && qRes.question) {
                const newQ = qRes.question
                
                // Create Options
                // Filter out empty options
                const validOptions = questionForm.options.filter(o => o.text.trim() !== '')
                
                for (const opt of validOptions) {
                    await api.post(`/api/questions/${newQ.id}/options`, {
                        text: opt.text,
                        is_correct: opt.is_correct ? 1 : 0
                    })
                }

                // Reload Data
                await fetchData()
                questionModal.value = false
                Swal.fire('Success', 'เพิ่มคำถามเรียบร้อย', 'success')
            }
        }
    } catch (err) {
        console.error(err)
        Swal.fire('Error', 'ไม่สามารถบันทึกคำถามได้', 'error')
    }
}

const deleteQuestion = async (qId: number) => {
    const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    })
    
    if (result.isConfirmed) {
        try {
            await api.delete(`/api/courses/${courseId}/quizzes/${quizId}/questions/${qId}`) // Verify route
            // The route might be different, commonly destroyed via QuestionController if generic
            // Or CourseQuizQuestionController destroy method.
            // Let's assume standard resource route: DELETE /courses/{course}/quizzes/{quiz}/questions/{question}
            // Check routes/learn/course.php: Route::resource(..., CourseQuizQuestionController::class) -> destroys at /{question}
            
            await fetchData()
        } catch (err) {
             // Fallback to generic question delete if specific route fails
             try {
                 await api.delete(`/api/questions/${qId}`)
                 await fetchData()
             } catch (e) {
                 Swal.fire('Error', 'ลบไม่สำเร็จ', 'error')
             }
        }
    }
}

</script>

<template>
  <div class="container mx-auto px-4 py-6 max-w-4xl">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-6">
      <button 
        @click="navigateTo(`/courses/${courseId}/quizzes/${quizId}`)"
        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 transition-colors"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-6 h-6" />
      </button>
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">แก้ไขแบบทดสอบ</h1>
        <p class="text-sm text-gray-500">{{ quiz?.title }}</p>
      </div>
    </div>

    <div v-if="isLoading" class="flex justify-center p-12">
      <Icon icon="svg-spinners:3-dots-fade" class="w-10 h-10 text-gray-400" />
    </div>

    <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        
        <!-- Tabs -->
        <div class="flex border-b border-gray-200 dark:border-gray-700">
            <button 
                @click="activeTab = 'settings'"
                :class="['px-6 py-3 font-medium text-sm focus:outline-none transition-colors relative', activeTab === 'settings' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-500 hover:text-gray-900 dark:hover:text-gray-300']"
            >
                ตั้งค่าทั่วไป
                <div v-if="activeTab === 'settings'" class="absolute bottom-0 left-0 w-full h-0.5 bg-purple-600 dark:bg-purple-400"></div>
            </button>
            <button 
                @click="activeTab = 'questions'"
                :class="['px-6 py-3 font-medium text-sm focus:outline-none transition-colors relative', activeTab === 'questions' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-500 hover:text-gray-900 dark:hover:text-gray-300']"
            >
                ข้อสอบ ({{ questions.length }})
                <div v-if="activeTab === 'questions'" class="absolute bottom-0 left-0 w-full h-0.5 bg-purple-600 dark:bg-purple-400"></div>
            </button>
        </div>

        <!-- Settings Tab -->
        <div v-show="activeTab === 'settings'" class="p-6 space-y-6">
             <!-- Same form content as create.vue -->
             <!-- Basic Info -->
            <div class="grid gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อแบบทดสอบ <span class="text-red-500">*</span></label>
                <input v-model="form.title" type="text" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                <textarea v-model="form.description" rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
            </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700" />

            <div class="grid md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <h3 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                    <Icon icon="fluent:timer-24-regular" class="w-5 h-5 text-gray-400" />
                    การตั้งค่าเวลาและคะแนน
                </h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เวลา (นาที) <span class="text-red-500">*</span></label>
                    <input v-model.number="form.time_limit" type="number" min="1" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เกณฑ์ผ่าน (%) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input v-model.number="form.passing_score" type="number" min="0" max="100" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent pr-8" />
                        <span class="absolute right-3 top-2.5 text-gray-500">%</span>
                    </div>
                </div>
            </div>
            <div class="space-y-4">
                <h3 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                    <Icon icon="fluent:calendar-ltr-24-regular" class="w-5 h-5 text-gray-400" />
                    กำหนดการ
                </h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เริ่ม</label>
                    <VueDatePicker v-model="form.start_date" :format="'dd/MM/yyyy HH:mm'" auto-apply :teleport="true" input-class-name="bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สิ้นสุด</label>
                    <VueDatePicker v-model="form.end_date" :format="'dd/MM/yyyy HH:mm'" auto-apply :teleport="true" />
                </div>
            </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700" />

            <div class="flex flex-col sm:flex-row gap-6">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative flex items-center">
                    <input type="checkbox" v-model="form.shuffle_questions" class="peer sr-only">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-300">สลับข้อคำถาม</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative flex items-center">
                    <input type="checkbox" v-model="form.is_active" class="peer sr-only">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-300">เปิดใช้งาน</span>
                </label>
            </div>

            <div class="flex justify-end pt-4">
                <button 
                  @click="handleUpdate"
                  class="px-6 py-2 rounded-lg bg-purple-600 text-white font-medium hover:bg-purple-700 disabled:opacity-50 transition-colors"
                  :disabled="!isFormValid || isSaving"
                >
                  <span v-if="isSaving">กำลังบันทึก...</span>
                  <span v-else>บันทึกการเปลี่ยนแปลง</span>
                </button>
            </div>
        </div>

        <!-- Questions Tab -->
        <div v-show="activeTab === 'questions'" class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-gray-900 dark:text-white">รายการคำถาม</h3>
                <button 
                  @click="openAddQuestion"
                  class="px-4 py-2 rounded-lg bg-purple-100 text-purple-600 hover:bg-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:hover:bg-purple-900/50 transition-colors flex items-center gap-2 font-medium"
                >
                  <Icon icon="fluent:add-circle-24-regular" class="w-5 h-5" />
                  เพิ่มข้อสอบ
                </button>
            </div>

            <div v-if="questions.length === 0" class="text-center py-12 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                <Icon icon="fluent:quiz-new-24-regular" class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                <p class="text-gray-500">ยังไม่มีข้อสอบในชุดนี้</p>
                <button @click="openAddQuestion" class="text-purple-600 hover:underline mt-2">เพิ่มข้อสอบแรก</button>
            </div>

            <div v-else class="space-y-4">
                <div v-for="(q, index) in questions" :key="q.id" class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex gap-3">
                            <div class="w-8 h-8 rounded-full bg-white dark:bg-gray-800 flex items-center justify-center font-bold text-gray-500 text-sm shadow-sm">
                                {{ index + 1 }}
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ q.text }}</h4>
                                <div class="space-y-1">
                                    <div v-for="opt in q.options" :key="opt.id" class="flex items-center gap-2 text-sm">
                                        <Icon 
                                            :icon="opt.is_correct ? 'fluent:checkmark-circle-24-filled' : 'fluent:circle-24-regular'" 
                                            :class="opt.is_correct ? 'text-green-500' : 'text-gray-400'"
                                        />
                                        <span :class="opt.is_correct ? 'text-green-700 dark:text-green-400 font-medium' : 'text-gray-600 dark:text-gray-400'">
                                            {{ opt.text }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="text-xs font-bold px-2 py-1 bg-gray-200 dark:bg-gray-600 rounded text-gray-600 dark:text-gray-300 mr-2">{{ q.points }} คะแนน</span>
                            <!-- <button @click="openEditQuestion(q)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-full transition-colors">
                                <Icon icon="fluent:edit-20-regular" />
                            </button> -->
                            <button @click="deleteQuestion(q.id)" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-full transition-colors">
                                <Icon icon="fluent:delete-20-regular" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Question Modal -->
    <div v-if="questionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl overflow-hidden max-h-[90vh] flex flex-col">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="font-bold text-lg text-gray-900 dark:text-white">
                    {{ editingQuestion ? 'แก้ไขข้อสอบ' : 'เพิ่มข้อสอบใหม่' }}
                </h3>
                <button @click="questionModal = false" class="text-gray-400 hover:text-gray-500">
                    <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1 space-y-6">
                <div>
                     <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำถาม</label>
                     <textarea v-model="questionForm.text" rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                </div>
                
                <div>
                     <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คะแนน</label>
                     <input v-model.number="questionForm.points" type="number" min="1" class="w-24 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ตัวเลือก (คลิกที่วงกลมเพื่อเลือกข้อที่ถูก)</label>
                    <div class="space-y-3">
                        <div v-for="(opt, i) in questionForm.options" :key="i" class="flex items-center gap-3">
                            <button @click="setCorrectOption(i)" class="focus:outline-none">
                                <Icon 
                                    :icon="opt.is_correct ? 'fluent:radio-button-24-filled' : 'fluent:radio-button-24-regular'" 
                                    class="w-6 h-6 transition-colors"
                                    :class="opt.is_correct ? 'text-green-500' : 'text-gray-400 hover:text-gray-600'" 
                                />
                            </button>
                            <input 
                                v-model="opt.text" 
                                type="text" 
                                :placeholder="`ตัวเลือกที่ ${i + 1}`"
                                class="flex-1 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-3">
                <button @click="questionModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">ยกเลิก</button>
                <button @click="saveQuestion" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium">บันทึก</button>
            </div>
        </div>
    </div>

  </div>
</template>
