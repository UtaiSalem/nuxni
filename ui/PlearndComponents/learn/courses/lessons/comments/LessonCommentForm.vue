<script setup>
import { ref, reactive, computed } from 'vue';
import { useObjectUrl } from '@vueuse/core';
import { Icon } from '@iconify/vue';

const props = defineProps({
    lessonId: Number,
    lessonUrl: String,
});

const emit = defineEmits(['add-new-comment']);

const api = useApi();
const authStore = useAuthStore();
const { getAvatarUrl } = useAvatar();

const imageInput = ref(null);
const previewImages = reactive([]);
const isSubmitting = ref(false);

const commentForm = reactive({
    lessonId: props.lessonId,
    content: '',
    images: [],
});

const currentUserAvatar = computed(() => getAvatarUrl(authStore.user));
const isFormValid = computed(() => commentForm.content.length > 0 || commentForm.images.length > 0);

const submitLessonComment = async () => {
    if (!isFormValid.value) return;

    try {
        isSubmitting.value = true;
        const formData = new FormData();
        formData.append('content', commentForm.content);
        commentForm.images.forEach(image => formData.append('images[]', image));

        const response = await api.post(`/api/lessons/${props.lessonId}/comments`, formData);

        if (response.success) {
            emit('add-new-comment', response.comment);
            resetForm();
        }
    } catch (error) {
        console.error('Error submitting comment:', error);
    } finally {
        isSubmitting.value = false;
    }
};

const resetForm = () => {
    commentForm.content = '';
    commentForm.images = [];
    previewImages.splice(0);
};

const openImageSelector = () => imageInput.value.click();

const handleImageSelection = (event) => {
    Array.from(event.target.files).forEach(image => {
        previewImages.push({ file: image, url: useObjectUrl(image) });
        commentForm.images.push(image);
    });
};

const removePreviewImage = (index) => {
    previewImages.splice(index, 1);
    commentForm.images.splice(index, 1);
};

</script>

<template>
    <div class="mb-4">
        <form @submit.prevent="submitLessonComment" :id="`lesson-comment-form-${lessonId}`" class="relative flex items-start gap-3">
            <img :src="currentUserAvatar" class="w-10 h-10 p-[2px] rounded-full ring-1 ring-blue-600 dark:ring-gray-500" alt="">
            <div class="flex-1 relative">
                <textarea 
                    :id="`lesson-comment-input-${lessonId}`" 
                    class="text-sm p-3 w-full min-h-[56px] dark:text-gray-300 dark:bg-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 border border-gray-300 dark:border-gray-600 resize-none"
                    v-model="commentForm.content" 
                    placeholder="แสดงความคิดเห็น..." 
                    rows="2"
                ></textarea>
                <div class="flex gap-4 absolute bottom-3 right-3">
                    <input ref="imageInput" @change="handleImageSelection" type="file" multiple class="hidden" accept="image/*" />
                    <button type="button" @click.prevent="openImageSelector" class="text-violet-600 hover:text-violet-700">
                        <Icon icon="fluent:image-24-regular" class="w-5 h-5" />
                    </button>
                </div>
            </div>
            <button 
                type="submit" 
                :disabled="!isFormValid || isSubmitting" 
                name="comment-submit-button" 
                :id="`lesson-comment-submit-button-${lessonId}`" 
                :class="isFormValid ? 'hover:scale-110 text-blue-500 border-blue-500': 'cursor-not-allowed text-gray-500 border-gray-500'"
                class="p-2 rounded-lg border transition-transform">
                <Icon v-if="!isSubmitting"
                    icon="fluent:send-24-filled" 
                    class="h-5 w-5"
                />
                <Icon v-else
                    icon="eos-icons:bubble-loading"
                    class="h-5 w-5"
                />
            </button>
        </form>
        <div class="flex" v-if="previewImages.length">
            <div class="w-10 h-10 p-[2px]"></div>
            <div class="mt-2 ml-4 w-full grid grid-cols-2 md:grid-cols-4 gap-2">
                <div v-for="(image, index) in previewImages" :key="image.url" class="relative">
                    <div class="relative overflow-hidden p-0.5 border rounded-md">
                        <img :src="image.url" class="rounded-lg w-full h-24 object-cover" />
                        <button @click.prevent="removePreviewImage(index)"
                            class="absolute w-6 h-6 flex justify-center items-center top-1 right-1 rounded-full cursor-pointer bg-gray-100 p-1">
                            <Icon icon="fa-solid:trash-alt" class="w-3 h-3 text-red-500" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
