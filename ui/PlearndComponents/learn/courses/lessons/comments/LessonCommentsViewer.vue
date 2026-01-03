<script setup>
import { ref, computed, onMounted } from 'vue';
import { Icon } from '@iconify/vue';
import LessonCommentForm from '~/PlearndComponents/learn/courses/lessons/comments/LessonCommentForm.vue';
import LessonCommentItem from '~/PlearndComponents/learn/courses/lessons/comments/LessonCommentItem.vue';

const props = defineProps({
    lesson: {
        type: Object,
        required: true
    },
    isCourseAdmin: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['new-comment-added']);

const api = useApi();
const route = useRoute();

const initComments = ref(props.lesson.comments || []);
const lessonComments = ref([]);
const showAllComments = computed(() => props.lesson.comment_count > 3);
const showCommentsModal = ref(false);
const isLoading = ref(false);
const nextPageUrl = ref(null);

// Check if we're on the lesson detail page
const isLessonDetailPage = computed(() => {
    return route.path.includes('/lessons/') && route.params.lessonId;
});

const displayComments = computed(() => {
    return isLessonDetailPage.value ? lessonComments.value : initComments.value;
});

const showCommentsModalHandler = async () => {
    showCommentsModal.value = true;
    if (lessonComments.value.length === 0) {
        nextPageUrl.value = `/api/lessons/${props.lesson.id}/comments`;
        await fetchMoreComments();
    }
}

const clearCommentsModal = () => {
    showCommentsModal.value = false;
    lessonComments.value = [];
    nextPageUrl.value = null;
};

const fetchMoreComments = async () => {
    if (isLoading.value || !nextPageUrl.value) return;
    try {
        isLoading.value = true;
        const commentsResp = await api.get(nextPageUrl.value);
        if (commentsResp.data) {
            lessonComments.value.push(...commentsResp.data);
            nextPageUrl.value = commentsResp.links?.next || null;
        }
    } catch (error) {
        console.error('Error fetching comments:', error);
    } finally {
        isLoading.value = false;
    }
};

const handleDeleteComment = (index) => {
    if (isLessonDetailPage.value) {
        lessonComments.value.splice(index, 1);
    } else {
        initComments.value.splice(index, 1);
    }
    if (props.lesson.comment_count > 0) {
        props.lesson.comment_count--;
    }
}

const handleNewComment = (comment) => {
    if (isLessonDetailPage.value) {
        lessonComments.value.unshift(comment);
    } else {
        initComments.value.unshift(comment);
    }
    props.lesson.comment_count++;
    emit('new-comment-added', comment);
}

onMounted(() => {
    if (isLessonDetailPage.value) {
        initComments.value = [];
        nextPageUrl.value = `/api/lessons/${props.lesson.id}/comments`;
        fetchMoreComments();
    }
});
</script>

<template>
    <div class="m-2">
        <LessonCommentForm 
            :lessonId="props.lesson.id"
            :lessonUrl="props.lesson.url"
            @add-new-comment="handleNewComment"
        />

        <LessonCommentItem 
            v-for="(comment, index) in displayComments" 
            :key="comment.id"
            :comment="comment"
            :lessonId="props.lesson.id"
            :is-course-admin="isCourseAdmin"
            @delete-lesson-comment="handleDeleteComment(index)"
        />

        <div v-if="showAllComments && !isLessonDetailPage" class="flex flex-col w-full my-2">
            <button @click.prevent="showCommentsModalHandler" type="button" class="ml-auto mr-2 text-sm text-blue-600 hover:underline">
                ...ดูความคิดเห็นเพิ่มเติม ({{ lesson.comment_count }})...
            </button>
        </div>

        <div v-if="isLessonDetailPage && isLoading" class="flex justify-center py-4">
            <Icon icon="eos-icons:bubble-loading" class="w-8 h-8 text-blue-500" />
        </div>

        <!-- Comments Modal -->
        <div v-if="showCommentsModal && !isLessonDetailPage" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-xl bg-white dark:bg-gray-800 rounded-lg mx-4 max-h-[90vh] flex flex-col">
                <!-- Modal Header -->
                <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
                    <h3 class="text-lg font-semibold dark:text-white">ความคิดเห็นทั้งหมด</h3>
                    <button type="button" @click="clearCommentsModal" class="text-gray-500 hover:text-red-500 transition-colors">
                        <Icon icon="heroicons:x-mark-20-solid" class="w-6 h-6" />
                    </button>
                </div>
                
                <!-- Modal Body -->
                <div class="p-4 overflow-y-auto flex-1">
                    <LessonCommentItem 
                        v-for="(comment, index) in lessonComments" 
                        :key="comment.id"
                        :comment="comment"
                        :lessonId="props.lesson.id"
                        :is-course-admin="isCourseAdmin"
                        @delete-lesson-comment="handleDeleteComment(index)"
                    />
                    
                    <div v-if="isLoading" class="flex justify-center py-4">
                        <Icon icon="eos-icons:bubble-loading" class="w-8 h-8 text-blue-500" />
                    </div>
                    
                    <button 
                        v-if="nextPageUrl && !isLoading" 
                        @click="fetchMoreComments"
                        class="w-full py-2 text-blue-600 hover:underline text-sm"
                    >
                        โหลดความคิดเห็นเพิ่มเติม
                    </button>
                    
                    <p v-if="!nextPageUrl && lessonComments.length > 0" class="text-center text-gray-500 py-2 text-sm">
                        ไม่มีความคิดเห็นเพิ่มเติม
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
