<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import { useQuestionAnswersStore } from '@/stores/questionAnswers';

const props = defineProps({
    questions: {
        type: Array,
        required: true
    },
    quizId: {
        type: [Number, String],
        required: true
    },
    startQuizAt: Date,
    timeLimit: Number,
});

const emit = defineEmits(['submit-quiz']);

// Store
const questionAnswersStore = useQuestionAnswersStore();

// State
const currentQuestionIndex = ref(0);
const isLoading = ref(false);
const isSubmitting = ref(false);
const startQuizTime = ref(null);

// Current Question
const currentQuestion = computed(() => {
    return props.questions[currentQuestionIndex.value];
});

// Navigation
const canGoNext = computed(() => currentQuestionIndex.value < props.questions.length - 1);
const canGoPrev = computed(() => currentQuestionIndex.value > 0);

const nextQuestion = () => {
    if (canGoNext.value) {
        currentQuestionIndex.value++;
        scrollToTop();
    }
};

const prevQuestion = () => {
    if (canGoPrev.value) {
        currentQuestionIndex.value--;
        scrollToTop();
    }
};

const jumpToQuestion = (index) => {
    currentQuestionIndex.value = index;
    scrollToTop();
};

const quizTopRef = ref(null);

const scrollToTop = () => {
    if (quizTopRef.value) {
        // scrollIntoView with start usually snaps to top. 
        // We assume sticky headers take up space, so we rely on scroll-margin-top class in template
        quizTopRef.value.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

// Selection & Answering Logic
// We map selection locally to avoid instant store commit until confirmed/debounced if needed, 
// BUT for premium feel, auto-save or quick save on click is better.
// QuestionItem used a "Confirm" button strategy. We will reproduce that for reliability first,
// but style it better. Or IF already answered, show "Edit".

const selectedOptionId = ref(null);

// Sync selectedOptionId when current question changes
watch(currentQuestion, (newQ) => {
    if (newQ) {
        // If answered in store or prop
        const storeAnswer = questionAnswersStore.getAnswerForQuestion(props.quizId, newQ.id);
        if (storeAnswer) {
            selectedOptionId.value = storeAnswer.question_option_id || storeAnswer; // handle object or ID
        } else if (newQ.isAnsweredByAuth && newQ.authAnswerQuestion) {
            selectedOptionId.value = newQ.authAnswerQuestion.question_option_id || newQ.authAnswerQuestion;
        } else {
            selectedOptionId.value = null;
        }
    }
}, { immediate: true });

// Check if current question is answered
const isAnswered = computed(() => {
    return !!(currentQuestion.value?.isAnsweredByAuth || questionAnswersStore.isQuestionAnswered(props.quizId, currentQuestion.value?.id));
});

const isSubmittingAnswer = computed(() => {
    return questionAnswersStore.isQuestionSubmitting(props.quizId, currentQuestion.value?.id);
});

// Helper to determine if an option is selected
const isOptionSelected = (optionId) => selectedOptionId.value === optionId;

// Handle Option Click
const handleOptionSelect = async (optionId) => {
    if (isSubmittingAnswer.value) return;
    
    selectedOptionId.value = optionId;
    
    // Auto-save logic (Premium feel)
    // Or we keep "Confirm" button? User said "dull", so auto-save is more modern.
    // However, QuestionItem Logic had "Confirm" button.
    // Let's implement Auto-Save with a small debounce or immediate.
    await submitAnswer(optionId);
};

const submitAnswer = async (optionId) => {
    if (!currentQuestion.value) return;
    
    const qId = currentQuestion.value.id;
    const qableId = currentQuestion.value.questionable_id;
    
    try {
        questionAnswersStore.setQuestionSubmitting(props.quizId, qId, true);
        
        // Prepare payload
        const payload = {
            course_id: usePage().props.course?.data?.id, // Safety check
            answer_id: optionId,
            started_at: props.startQuizAt
        };

        // Check if first time or edit
        // Logic from QuestionItem:
        // if (!props.question.isAnsweredByAuth) -> POST
        // else -> PATCH
        
        let response;
        const isEdit = currentQuestion.value.isAnsweredByAuth || questionAnswersStore.isQuestionAnswered(props.quizId, qId);
        
        if (!isEdit) {
            response = await axios.post(`/quizs/${qableId}/questions/${qId}/answers`, payload);
        } else {
            // Need existing answer ID
             const existingAnswer = questionAnswersStore.getAnswerForQuestion(props.quizId, qId) || currentQuestion.value.authAnswerQuestion;
             const ansId = existingAnswer?.id || existingAnswer; // might be object or ID
             
             if (ansId) {
                response = await axios.patch(`/quizs/${qableId}/questions/${qId}/answers/${ansId}`, payload);
             } else {
                 // Fallback if ID missing but flagged as answered (shouldn't happen)
                 response = await axios.post(`/quizs/${qableId}/questions/${qId}/answers`, payload);
             }
        }

        if (response.data.success) {
             // Update local state props to reflect answered
             currentQuestion.value.isAnsweredByAuth = true;
             currentQuestion.value.authAnswerQuestion = response.data.authAnswerQuestion;
             
             // Update Store
             questionAnswersStore.setAnsweredQuestion(props.quizId, qId, response.data.authAnswerQuestion);
             
             // Toast? maybe too noisy. Just visual feedback on card.
        }

    } catch (error) {
        console.error('Answer submission error:', error);
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: 'บันทึกคำตอบไม่สำเร็จ',
            showConfirmButton: false,
            timer: 2000
        });
    } finally {
        questionAnswersStore.setQuestionSubmitting(props.quizId, qId, false);
    }
};

// Initialization
onMounted(() => {
    startQuizTime.value = new Date();
    // Initialize store
    if (props.questions) {
        props.questions.forEach(q => {
            if (q.isAnsweredByAuth && q.authAnswerQuestion) {
                 questionAnswersStore.setAnsweredQuestion(props.quizId, q.id, q.authAnswerQuestion);
            }
        });
    }
});

// Image Handling
const showImageModal = ref(false);
const activeImageUrl = ref('');

const openImage = (url) => {
    activeImageUrl.value = url;
    showImageModal.value = true;
};

// Progress
const progressPercent = computed(() => {
    if (!props.questions.length) return 0;
    const answered = props.questions.filter(q => 
        q.isAnsweredByAuth || questionAnswersStore.isQuestionAnswered(props.quizId, q.id)
    ).length;
    return Math.round((answered / props.questions.length) * 100);
});

</script>

<template>
    <div ref="quizTopRef" class="w-full max-w-5xl mx-auto pb-20 relative scroll-mt-32">
        
        <!-- Progress Bar (Sticky) -->
        <div class="sticky top-[70px] z-10 bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-sm rounded-xl mb-6 p-4 border border-gray-100 dark:border-gray-700">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-bold text-gray-700 dark:text-gray-200">
                    ข้อที่ {{ currentQuestionIndex + 1 }} <span class="text-gray-400 font-normal">/ {{ questions.length }}</span>
                </h3>
                <div class="text-sm font-medium" :class="progressPercent === 100 ? 'text-green-600' : 'text-blue-600'">
                    {{ progressPercent }}% สำเร็จ
                </div>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 h-2.5 rounded-full transition-all duration-500 ease-out" 
                     :style="{ width: `${progressPercent}%` }">
                </div>
            </div>
        </div>

        <!-- Question Card -->
        <div v-if="currentQuestion" 
             class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700 transition-all duration-300">
             
            <!-- Question Header -->
            <div class="p-8 md:p-10 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-lg">
                        {{ currentQuestionIndex + 1 }}
                    </div>
                    <div class="w-full">
                        <div class="flex justify-between items-start mb-4">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                                {{ currentQuestion.points }} คะแนน
                            </span>
                            <span v-if="isAnswered" class="flex items-center gap-1 text-green-600 text-sm font-bold animate-pulse">
                                <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5" />
                                ตอบแล้ว
                            </span>
                        </div>
                        <h2 class="text-xl md:text-2xl font-bold text-gray-800 dark:text-gray-100 leading-relaxed">
                            {{ currentQuestion.text }}
                        </h2>
                        
                        <!-- Question Images -->
                        <div v-if="currentQuestion.images && currentQuestion.images.length > 0" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="img in currentQuestion.images" :key="img.id" 
                                 class="relative group rounded-xl overflow-hidden border border-gray-200 dark:border-gray-600 aspect-video cursor-zoom-in shadow-sm hover:shadow-md transition-all"
                                 @click="openImage(img.url)">
                                <img :src="img.url" 
                                     @error="$event.target.src = 'https://placehold.co/600x400?text=Image+Not+Found'"
                                     class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" 
                                     alt="Question Image" />
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                                    <Icon icon="fluent:zoom-in-24-regular" class="text-white w-8 h-8 drop-shadow-md" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Options Grid -->
            <div class="p-8 md:p-10 bg-white dark:bg-gray-800">
                <div class="grid grid-cols-1 gap-4">
                    <button v-for="(option, idx) in currentQuestion.options" :key="option.id"
                        @click="handleOptionSelect(option.id)"
                        :disabled="isSubmittingAnswer"
                        class="relative group flex items-center gap-4 p-5 rounded-2xl border-2 text-left transition-all duration-200 w-full hover:shadow-lg focus:outline-none"
                        :class="[
                            isOptionSelected(option.id) 
                            ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20 shadow-md ring-2 ring-indigo-500/20' 
                            : 'border-gray-200 dark:border-gray-700 hover:border-indigo-300 bg-white dark:bg-gray-800 dark:hover:border-indigo-700'
                        ]"
                    >
                        <!-- Radio Indicator -->
                        <div class="flex-shrink-0 w-8 h-8 rounded-full border-2 flex items-center justify-center transition-all"
                             :class="[
                                isOptionSelected(option.id)
                                ? 'border-indigo-600 bg-indigo-600'
                                : 'border-gray-300 group-hover:border-indigo-400'
                             ]">
                            <Icon v-if="isOptionSelected(option.id)" icon="fluent:checkmark-16-filled" class="text-white w-5 h-5" />
                            <span v-else class="text-gray-400 font-medium text-sm">{{ idx + 1 }}</span>
                        </div>

                        <!-- Option Content -->
                        <div class="flex-1">
                            <div class="text-lg font-medium text-gray-700 dark:text-gray-200 group-hover:text-indigo-700 dark:group-hover:text-indigo-300 transition-colors">
                                {{ option.text }}
                            </div>
                            <!-- Option Images -->
                            <div v-if="option.images && option.images.length > 0" class="mt-3 flex gap-2">
                                <div v-for="optImg in option.images" :key="optImg.id" 
                                     class="relative w-24 h-24 rounded-lg overflow-hidden border border-gray-200 shadow-sm hover:scale-105 transition-transform cursor-zoom-in"
                                     @click.stop="openImage(optImg.url)">
                                    <img :src="optImg.url" class="absolute inset-0 w-full h-full object-cover" />
                                </div>
                            </div>
                        </div>

                        <!-- Processing Spinner -->
                        <div v-if="isSubmittingAnswer && isOptionSelected(option.id)" class="absolute right-4 top-1/2 -translate-y-1/2">
                            <Icon icon="svg-spinners:ring-resize" class="w-6 h-6 text-indigo-600" />
                        </div>
                    </button>
                </div>
            </div>

            <!-- Footer Navigation -->
            <div class="p-6 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
                <button 
                    @click="prevQuestion" 
                    :disabled="!canGoPrev"
                    class="px-6 py-3 rounded-xl font-bold flex items-center gap-2 transition-all"
                    :class="canGoPrev ? 'text-gray-600 hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-700' : 'text-gray-300 cursor-not-allowed hidden md:flex'"
                >
                    <Icon icon="fluent:arrow-left-24-filled" class="w-5 h-5" />
                    ก่อนหน้า
                </button>

                <!-- Question Navigator (Dots) -->
                <div class="hidden md:flex gap-1.5 flex-wrap justify-center max-w-md">
                   <button v-for="(q, i) in questions" :key="q.id"
                        @click="jumpToQuestion(i)"
                        class="w-3 h-3 rounded-full transition-all duration-300"
                        :class="[
                            currentQuestionIndex === i 
                                ? 'bg-indigo-600 w-8' 
                                : (questionAnswersStore.isQuestionAnswered(quizId, q.id) || q.isAnsweredByAuth ? 'bg-green-400' : 'bg-gray-300 hover:bg-gray-400')
                        ]"
                        :title="`ข้อที่ ${i+1}`"
                   ></button>
                </div>

                <div class="flex gap-3">
                    <button 
                        v-if="canGoNext"
                        @click="nextQuestion" 
                        class="px-8 py-3 bg-gray-900 dark:bg-gray-700 text-white rounded-xl font-bold hover:bg-black dark:hover:bg-gray-600 shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all flex items-center gap-2"
                    >
                        ถัดไป
                        <Icon icon="fluent:arrow-right-24-filled" class="w-5 h-5" />
                    </button>
                    <!-- Submit Button (Last Question) -->
                    <button 
                        v-else
                        @click="$emit('submit-quiz')" 
                        class="px-8 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-bold hover:from-green-600 hover:to-emerald-700 shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all flex items-center gap-2"
                    >
                        ส่งคำตอบ
                        <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Image Modal -->
        <div v-if="showImageModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm" @click="showImageModal = false">
            <button class="absolute top-4 right-4 text-white/50 hover:text-white transition-colors">
                <Icon icon="fluent:dismiss-24-regular" class="w-8 h-8" />
            </button>
            <img :src="activeImageUrl" class="max-w-full max-h-[90vh] rounded-lg shadow-2xl animate-zoom-in" @click.stop />
        </div>

    </div>
</template>

<style scoped>
.animate-zoom-in {
    animation: zoomIn 0.3s ease-out forwards;
}

@keyframes zoomIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}
</style>
