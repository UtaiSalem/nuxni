<script setup>
import { ref, computed, onMounted } from 'vue';
// import { usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import QuestionImagesViewer from "@/PlearndComponents/learn/courses/questions/QuestionImagesViewer.vue";
import QuestionOptionForm from '@/PlearndComponents/learn/courses/questions/QuestionOptionForm.vue';
import VerticalDotsDropdownMenu from '@/PlearndComponents/accessories/VerticalDotsDropdownMenu.vue';
import EditQuestionModal from '@/PlearndComponents/learn/courses/questions/EditQuestionModal.vue';
import { useQuestionAnswersStore } from '@/stores/questionAnswers';

// Debounce function สำหรับป้องกันการคลิกซ้ำ
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

const props = defineProps({
    questionApiRoute: String,
    question: Object,
    indexNumber: Number,
    isCourseOwner: Boolean,
    startQuizAt: Date,
    quizId: Number, // Add quizId prop to identify which quiz this is
});

const emit = defineEmits([
    'delete-question', 
    'update-question',
]);

const selectedOption = ref(props.question.isAnsweredByAuth ? props.question.isAnsweredByAuth : null);
const correctAnswer = ref(props.question.correct_option_id ?? null);
const showConfirmAnswerButton = ref(props.question.isAnsweredByAuth ? false : true);
const canEditAnswer = ref(props.question.isAnsweredByAuth ? true : false);

const isLoading = ref(false);
const isSubmitting = ref(false);
const optionActionIndex = ref(null);
const isOptionImageLoading = ref(false);
const deletingOptionImageIndex = ref(null);

const showEditModal = ref(false);
const isOpen = ref(!props.isCourseOwner);
const showEditAnswerButton = computed(() => canEditAnswer.value && !showConfirmAnswerButton.value);

// ใช้ Pinia Store สำหรับจัดการ state ของคำตอบ
// ใช้ Pinia Store สำหรับจัดการ state ของคำตอบ
const questionAnswersStore = useQuestionAnswersStore();
const api = useApi();
const route = useRoute();
const authStore = useAuthStore();
const user = computed(() => authStore.user);

// Initialize store with current question state after component is mounted
onMounted(() => {
    if (props.question.isAnsweredByAuth && props.question.authAnswerQuestion && props.quizId) {
        questionAnswersStore.setAnsweredQuestion(props.quizId, props.question.id, props.question.authAnswerQuestion);
    }
});

const setCanEditAnswer = async () => {
    // If fines are 0 OR user is Course Owner, no need to confirm/pay
    if (props.question.pp_fine <= 0 || props.isCourseOwner) {
        canEditAnswer.value = false;
        showConfirmAnswerButton.value = true;
    } else { 
        // Use 'points' from authStore
        const currentPoints = user.value?.points || 0;

        if (currentPoints < props.question.pp_fine) {
            Swal.fire({
                title: 'แต้มสะสมไม่เพียงพอ',
                text: `คุณต้องมีแต้มสะสมมากกว่า ${props.question.pp_fine} แต้ม เพื่อแก้ไขคำตอบนี้`,
                icon: 'warning',
                confirmButtonText: 'ตกลง',
                confirmButtonColor: '#f59e0b',
                background: '#fff',
            });
        } else {
             Swal.fire({
                title: 'ยืนยันการแก้ไข',
                text: `คุณต้องการใช้ ${props.question.pp_fine} แต้มเพื่อแก้ไขคำตอบใช่หรือไม่?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ใช่, แก้ไขเลย',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Deduct from Store
                    authStore.deductPoints(props.question.pp_fine);
                    
                    canEditAnswer.value = false;
                    showConfirmAnswerButton.value = true;
                }
            });
        }
    }
};

// สร้าง debounced version ของ handleConfirmAnswer
const debouncedHandleConfirmAnswer = debounce(async function() {
    // ป้องกันการเรียกซ้ำหลายชั้นโดยใช้ store
    if (isLoading.value || isSubmitting.value || questionAnswersStore.isQuestionSubmitting(props.quizId, props.question.id)) {
        return;
    }
    
    try {
        let resultResp = null;
        isSubmitting.value = true;
        isLoading.value = true;
        
        // Set submitting state in store
        questionAnswersStore.setQuestionSubmitting(props.quizId, props.question.id, true);
        
        // Check if this is a first-time answer or an edit
        if (!props.question.isAnsweredByAuth) {
            // First-time answer
            resultResp = await api.post(`${props.questionApiRoute}/questions/${props.question.id}/answers`, {
                course_id: route.params.id,
                answer_id: selectedOption.value,
                started_at: props.startQuizAt
            });
        } else {
            // Edit existing answer
            if (user.value.pp < props.question.pp_fine) {
                Swal.fire('ขออภัย! แต้มสะสมของคุณไม่เพียงพอ',
                'ไม่สามารถแก้ไขคำตอบได้ <br /> ต้องสะสมแต้มเพิ่มเติม <br /> ก่อนทำการแก้ไขคำตอบ',
                'error'
                );
                showConfirmAnswerButton.value = false;
                canEditAnswer.value = false;
                return;
            }
            
            // Get the existing answer ID
            const existingAnswer = questionAnswersStore.getAnswerForQuestion(props.quizId, props.question.id);
            const userAnswerQuestionId = existingAnswer ? existingAnswer : (props.question.authAnswerQuestion ? props.question.authAnswerQuestion : null);

            if (!userAnswerQuestionId) {
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถแก้ไขคำตอบได้เนื่องจากไม่พบข้อมูลคำตอบเดิม', 'error');
                return;
            }

            resultResp = await api.patch(`${props.questionApiRoute}/questions/${props.question.id}/answers/${userAnswerQuestionId}`, {
                course_id: route.params.id,
                answer_id: selectedOption.value,
            });
        }

        if (resultResp.success) {
            props.question.authAnswerQuestion = resultResp.authAnswerQuestion
            props.question.isAnsweredByAuth = true;
            showConfirmAnswerButton.value = false;
            canEditAnswer.value = true;
            
            // Update store with the answer
            questionAnswersStore.setAnsweredQuestion(props.quizId, props.question.id, resultResp.authAnswerQuestion);
            
            Swal.fire('สำเร็จ', resultResp.message, 'success' );
        }

    } catch (error) {
        console.error('Error submitting answer:', error);
        
        // Handle 422 errors specifically for "Already Answered"
        if (error.statusCode === 422 || error.response?.status === 422) {
             const errorData = error.data || error.response?.data;
             const errorMessage = errorData?.message || 'เกิดข้อผิดพลาด';
             
             if (errorMessage === 'คุณได้ตอบคำถามนี้ไปแล้ว') {
                // Update state with existing answer
                props.question.authAnswerQuestion = errorData.existing_answer_id;
                props.question.isAnsweredByAuth = true;
                showConfirmAnswerButton.value = false;
                canEditAnswer.value = true;
                
                questionAnswersStore.setAnsweredQuestion(props.quizId, props.question.id, errorData.existing_answer_id);
                
                Swal.fire('แจ้งเตือน', errorMessage, 'warning');
                return; // Exit catch
             }
        }

        const errorMessage = error.response?.data?.message || error.message || error.data?.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่';
        
        // Store error in store
        questionAnswersStore.setQuestionError(props.quizId, props.question.id, errorMessage);
        
        Swal.fire('ล้มเหลว', errorMessage, 'error' );
    } finally {
        isLoading.value = false;
        isSubmitting.value = false;
        // Clear submitting state from store
        questionAnswersStore.setQuestionSubmitting(props.quizId, props.question.id, false);
    }
}, 500); // 500ms debounce delay

// ฟังก์ชันเดิมสำหรับการเรียกใช้งานอื่นๆ ถ้าจำเป็นการ
async function handleConfirmAnswer() {
    return debouncedHandleConfirmAnswer();
}

const handleAddNewOption = (newOption) => {
    props.question.options.push(newOption);
    props.question.options.sort((a, b) => a.id - b.id);
};


async function deleteOption(id, idx) {
    try {
        optionActionIndex.value = idx;
        await axios.delete(`/options/${id}`);
        props.question.options.splice(idx, 1);
    } catch (error) {
        console.error('Error deleting option:', error);
    } finally {
        optionActionIndex.value = null;
    }
}

async function setCorrectAnswer(aid) {
    if (aid) {
        correctAnswer.value = aid;
        await axios.patch(`/questions/${props.question.id}/set_correct_option`, { answer: aid });
    } else {
        Swal.fire('ยังไม่เลือกคำตอบ','กรุณาเลือกคำตอบ','error' );
    }
}

const handleAnswerChange = (answerId) => {
    selectedOption.value = answerId;
    // Don't store temporary selection in the main store
    // Only store when answer is confirmed to prevent state changes before confirmation
    // Clear any previous errors when user changes answer
    questionAnswersStore.clearQuestionError(props.quizId, props.question.id);
};

const toggleAnswer = () => {
    isOpen.value = !isOpen.value;
};

const handleUpdateQuestion = (updated_question) => {
    props.question.text = updated_question.text;
    props.question.points = updated_question.points;
    props.question.pp_fine = updated_question.pp_fine;

    emit('update-question', updated_question);
};

const handleDeleteQuestionImage = (questionImageIndex) => {
    props.question.images.splice(questionImageIndex, 1);
};

const handleDeleteQuestion = () => {
    Swal.fire({
        title: 'คุณแน่ใจหรือไม่?',
        text: "คุณไม่สามารถย้อนกลับการกระทำนี้ได้!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            emit('delete-question');
        }
    });
};

const deleteOptionImage = async (questionOptionIndex, optionImageIndex)=> {
    try {
        isOptionImageLoading.value = true;
        deletingOptionImageIndex.value = optionImageIndex;
        const imageId = props.question.options[questionOptionIndex].images[optionImageIndex].id;
        const delImgResp = await axios.delete(`/questions/${props.question.id}/images/${imageId}`);
        if (delImgResp.data.success) {
            props.question.images.splice(optionImageIndex, 1);
        } else {
            Swal.fire('ลบรูปภาพ', 'ลบรูปภาพไม่สำเร็จ', 'error');
        }      
    } catch (error) {
        // Handle error silently
    } finally {
        isOptionImageLoading.value = false;
        deletingOptionImageIndex.value = false;
    }
}
</script>

<template>
    <div>
        <div class="mx-auto space-y-4 p-2">
            <div class="transition-all duration-500">
                <div class="flex group/header">
                    <button type="button" @click="toggleAnswer"
                        class="rounded-xl flex items-start md:items-center justify-between w-full p-4 transition-all duration-300 border"
                        :class="[
                            question.isAnsweredByAuth 
                                ? 'bg-emerald-50 border-emerald-200 hover:bg-emerald-100/80 dark:bg-emerald-900/10 dark:border-emerald-800' 
                                : 'bg-white border-gray-100 hover:border-indigo-200 hover:shadow-indigo-500/10 hover:bg-indigo-50/30 dark:bg-gray-800 dark:border-gray-700'
                        ]">
                        
                        <div class="flex items-start gap-4 text-left w-full">
                            <!-- Question Number Badge -->
                            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg font-bold text-lg shadow-sm transition-colors"
                                 :class="[
                                    question.isAnsweredByAuth 
                                        ? 'bg-emerald-500 text-white' 
                                        : 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'
                                 ]">
                                {{ indexNumber + 1 }}
                            </div>
                            
                            <div class="flex-1 min-w-0 pt-1">
                                <!-- Question Text -->
                                <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100 leading-snug mb-2 pr-4">
                                    {{ question.text }}
                                </h4>
                                
                                <!-- Meta Badges -->
                                <div class="flex items-center gap-2 text-xs font-medium">
                                    <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        {{ question?.points }} คะแนน
                                    </span>
                                    <span v-if="question?.pp_fine" class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-800">
                                        มูลค่า {{ question?.pp_fine }} แต้ม
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col items-center justify-center h-full ml-2">
                             <Icon icon="fluent:chevron-down-24-filled" 
                                   class="w-5 h-5 text-gray-400 duration-300 transform group-hover/header:translate-y-0.5" 
                                   :class="{ 'rotate-180': isOpen }" />
                        </div>
                    </button>
                    
                    <div v-if="props.isCourseOwner" class="ml-2 pt-2">
                        <VerticalDotsDropdownMenu
                            @edit-model="showEditModal = true" 
                            @delete-model="handleDeleteQuestion" 
                        >
                            <template #editModel>
                                <span>แก้ไขคำถาม</span>
                            </template>
                            <template #deleteModel>
                                <span>ลบคำถาม</span>
                            </template>
                        </VerticalDotsDropdownMenu>
                    </div>
                </div>

                <div v-show="isOpen" class="transform transition duration-500 ">
                    <QuestionImagesViewer
                        :modelId="question.id"
                        :images="question.images"
                        :isCourseOwner="props.isCourseOwner"

                        @delete-image="(indx)=>handleDeleteQuestionImage(indx)"
                    />

                    <div class="mt-2">
                        <div class="grid space-y-3">
                            <div v-for="(option, qo_idx) in question.options" :key="option.id" class="relative group">
                                <label 
                                    :for="`${question.id}+${option.id}`"
                                    class="w-full flex items-start md:items-center gap-4 p-4 rounded-xl border-2 transition-all cursor-pointer relative overflow-hidden"
                                    :class="[
                                        selectedOption == option.id 
                                            ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/10 shadow-sm' 
                                            : 'border-gray-200 dark:border-gray-700 hover:border-indigo-200 dark:hover:border-indigo-800 hover:bg-gray-50 dark:hover:bg-gray-800/50'
                                    ]"
                                >
                                    <input type="radio" :id="`${question.id}+${option.id}`"
                                        :name="`${question.id}`" :value="option.id" v-model="selectedOption" :disabled="showEditAnswerButton"
                                        :checked="selectedOption == option.id" @change="handleAnswerChange(option.id)"
                                        class="hidden"
                                    />
                                    
                                    <!-- Custom Selection Indicator -->
                                    <div 
                                        class="w-6 h-6 rounded-full border-2 flex-shrink-0 flex items-center justify-center transition-colors mt-0.5 md:mt-0"
                                        :class="[
                                            selectedOption == option.id 
                                                ? 'border-indigo-500 bg-indigo-500 text-white' 
                                                : 'border-gray-300 dark:border-gray-600 group-hover:border-indigo-400'
                                        ]"
                                    >
                                        <Icon v-if="selectedOption == option.id" icon="fluent:checkmark-16-filled" class="w-4 h-4" />
                                    </div>
                                    
                                    <!-- Option Content -->
                                    <div class="flex-1 w-full min-w-0">
                                        <p class="text-base font-medium text-gray-700 dark:text-gray-200 leading-relaxed break-words">
                                            {{ option.text }}
                                        </p>
                                        
                                        <!-- Option Images (Ported Layout) -->
                                        <div v-if="option.images && option.images.length > 0" class="mt-3 flex flex-wrap gap-2">
                                            <div v-for="(opt_image, oi_index) in option.images" :key="opt_image.id || oi_index" class="relative group/img">
                                                <img 
                                                    :src="opt_image.full_url || opt_image.url" 
                                                    class="h-32 w-auto rounded-lg object-cover border border-gray-200 dark:border-gray-700 shadow-sm transition-transform hover:scale-105" 
                                                    alt="Option image" 
                                                />
                                                <button v-if="isCourseOwner" 
                                                        @click.prevent="deleteOptionImage(qo_idx, oi_index)" 
                                                        class="absolute top-1 right-1 bg-white/90 p-1 rounded-full text-red-500 opacity-0 group-hover/img:opacity-100 transition-opacity shadow-sm hover:scale-110">
                                                    <Icon icon="fluent:delete-16-filled" class="w-3 h-3" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delete Option Button (Admin only) -->
                                    <div v-if="props.isCourseOwner" class="ml-2">
                                        <button 
                                            @click.prevent="deleteOption(option.id, qo_idx)"
                                            :disabled="optionActionIndex === qo_idx"
                                            class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                            title="ลบตัวเลือก"
                                        >
                                            <Icon v-if="optionActionIndex === qo_idx" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                                            <Icon v-else icon="fluent:delete-20-regular" class="w-5 h-5" />
                                        </button>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="my-4 flex flex-col items-center space-y-3" v-if="!props.isCourseOwner">
                            <button type="button"
                                :id="`btn-confirm-answer${question.id}`"
                                v-if="showConfirmAnswerButton && question.options"
                                @click.prevent="handleConfirmAnswer()"
                                :disabled="!selectedOption || isLoading || isSubmitting || questionAnswersStore.isQuestionSubmitting(props.quizId, props.question.id)"
                                class="px-6 py-3 inline-flex justify-center items-center gap-2 rounded-lg font-semibold text-white shadow-lg transform transition-all duration-200 text-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
                                :class="[
                                    selectedOption && !isLoading && !isSubmitting
                                        ? 'bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 hover:scale-105 focus:ring-blue-500 dark:from-blue-600 dark:to-blue-800'
                                        : 'bg-gray-300 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed shadow-none'
                                ]">
                                <Icon v-if="isLoading || isSubmitting" icon="uiw:loading" class="animate-spin h-5 w-5"/>
                                <Icon v-else icon="heroicons:check-circle" class="w-5 h-5" />
                                <span v-if="!isLoading && !isSubmitting">ยืนยันคำตอบ</span>
                                <span v-else>กำลังบันทึก...</span>
                            </button>
                            
                            <!-- แสดงข้อความแจ้งเตือนเมื่อมีการพยายามตอบคำถามซ้ำ -->
                            <div v-if="props.question.isAnsweredByAuth && !showConfirmAnswerButton"
                                 class="w-full max-w-md p-3 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 rounded-lg text-sm flex items-center">
                                <Icon icon="heroicons:check-circle" class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" />
                                <span class="font-medium">คุณได้ตอบคำถามนี้ไปแล้ว</span>
                            </div>
                            
                            <button type="button"
                                :id="`btn-edit-answer${question.id}`"
                                v-if="canEditAnswer"
                                @click.prevent="setCanEditAnswer"
                                class="px-6 py-3 inline-flex justify-center items-center gap-2 rounded-lg font-semibold text-white shadow-lg transform transition-all duration-200 text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 hover:scale-105 focus:ring-amber-500">
                                <Icon icon="heroicons:pencil" class="w-5 h-5" />
                                <span>แก้ไขคำตอบ</span>
                                <span class="text-xs opacity-75" v-if="question.pp_fine > 0">(-{{ question.pp_fine }} แต้ม)</span>
                            </button>
                        </div>

                        <div class="flex justify-between items-center" v-if="props.isCourseOwner">
                            <QuestionOptionForm
                                :questionableType="question.questionable_type"
                                :questionableId="question.questionable_id" 
                                :question_id="question.id"
                                :options="question.options"
                                @add-new-option="(newOption) => handleAddNewOption(newOption)"
                            />
                        </div>

                        <div class="my-2 mr-[50px]" v-if="props.isCourseOwner">
                            <select :id="`select-question-correct-answer${question.id}`"
                                class="py-1.5 px-4 pr-9 block w-full rounded-full text-md text-gray-700 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400"
                                name="set-correct-answer-selection"
                                :class="[correctAnswer ? 'border-indigo-400 bg-green-200' : 'border-red-500 bg-red-200']"
                                @change="setCorrectAnswer(correctAnswer)" v-model="correctAnswer">
                                <option selected :value="false">คำตอบที่ถูกต้องคือ ข้อ</option>
                                <option v-for="(option, i) in question.options" :key="option.id"
                                    :value="option.id">
                                    {{ i + 1 }} {{ option.text }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <EditQuestionModal 
            v-if="props.isCourseOwner"
            :question
            :questionApiRoute
            :images="question.images"
            :isVisible="showEditModal"
            :isCourseOwner="props.isCourseOwner"

            @update-question="(updatedQuestion)=>handleUpdateQuestion(updatedQuestion)"
            @delete-question-image="(image_index)=>handleDeleteQuestionImage(image_index)"
            @close-modal="showEditModal = false"
        />

    </div>
</template>
