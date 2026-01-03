<script setup>
import { ref, computed } from 'vue';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import CommentSettingMenu from './CommentSettingMenu.vue';

const props = defineProps({
    comment: Object,
    lessonId: Number,
    isCourseAdmin: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['delete-lesson-comment']);

// Use auth store instead of Inertia's usePage
const authStore = useAuthStore();
const api = useApi();

const refComment = ref(props.comment);
const authUser = computed(() => authStore.user);
const isLoading = ref(false);
const isDislikeLoading = ref(false);
const isDeleting = ref(false);
const isCommentOwner = computed(() => authUser.value?.id === props.comment?.user?.id);

const handleLikesComment = async () => {
    if (isCommentOwner.value) return;
    if (refComment.value.isDislikedByAuth) return;
    if (isLoading.value) return;
    if (isDislikeLoading.value) return;
    if (isDeleting.value) return;
    
    try {
        isLoading.value = true;
        let likeResp = await api.post(`/api/lessons/${props.lessonId}/comments/${props.comment.id}/like`);
        if (likeResp.success) {
            refComment.value.isLikedByAuth = !refComment.value.isLikedByAuth;
            if (refComment.value.isLikedByAuth) {
                refComment.value.likes++;
            } else {
                refComment.value.likes--;
            }
        }
    } catch (error) {
        console.error('Failed to like comment:', error);
    } finally {
        isLoading.value = false;
    }
};

const handleDislikesComment = async () => {
    if (isCommentOwner.value) return;
    if (refComment.value.isLikedByAuth) return;
    if (isLoading.value) return;
    if (isDislikeLoading.value) return;
    if (isDeleting.value) return;
    
    try {
        isDislikeLoading.value = true;
        let dislikeResp = await api.post(`/api/lessons/${props.lessonId}/comments/${props.comment.id}/dislike`);
        if (dislikeResp.success) {
            refComment.value.isDislikedByAuth = !refComment.value.isDislikedByAuth;
            if (refComment.value.isDislikedByAuth) {
                refComment.value.dislikes++;
            } else {
                refComment.value.dislikes--;
            }
        }
    } catch (error) {
        console.error('Failed to dislike comment:', error);
    } finally {
        isDislikeLoading.value = false;
    }
};

const handleDeleteComment = () => {
    try {
        isDeleting.value = true;
        Swal.fire({
            title: 'ลบความคิดเห็น',
            text: "คุณแน่ใจหรือไม่ที่จะลบความคิดเห็นนี้ออกจากบทเรียน?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#7c3aed',
            cancelButtonColor: '#f87171',
            confirmButtonText: 'ยืนยันการลบ'
        }).then(async (result) => {
            if (result.isConfirmed) {
                let delResp = await api.delete(`/api/lessons/${props.comment.lesson_id}/comments/${props.comment.id}`);
                if (delResp.success) {
                    emit('delete-lesson-comment');
                    isDeleting.value = false;
                    Swal.fire(
                        'ลบความคิดเห็นสำเร็จ',
                        'ความคิดเห็นถูกลบออกแล้ว',
                        'success'
                    )
                }
            } else {
                isDeleting.value = false;
            }
        });
    } catch (error) {
        isDeleting.value = false;
    }
};
</script>

<template>
    <div class="flex gap-3 w-full justify-start border p-2 my-2 rounded-md">
        <img :src="comment.user?.avatar" class="w-9 h-9 p-[2px] rounded-full ring-1 ring-blue-600 dark:ring-gray-500"
            alt="">
        <div class="w-full">
            <div class="flex justify-between items-end">
                <div class="flex items-center space-x-2">
                    <h6 class="mb-0">{{ comment.user?.name || comment.user?.username }}</h6>
                    <span class="text-gray-600 text-sm">{{ comment.create_at }}</span>
                </div>
                <CommentSettingMenu @delete-comment="handleDeleteComment" v-if="isCommentOwner || isCourseAdmin" />
            </div>

            <div v-if="comment.content" class="mt-2">
                <p class="text-sm bg-gray-200 dark:bg-gray-700 w-full text-gray-700 dark:text-gray-300 rounded-lg p-3 whitespace-pre-wrap">
                    {{ comment.content }}
                </p>
            </div>

            <div v-if="comment.images && comment.images.length > 0" class="flex flex-wrap gap-2 mt-2">
                <div v-for="image in comment.images" :key="image.id" class="relative">
                    <img :src="image.url || image.full_url" class="w-24 h-24 object-cover rounded-lg border" alt="">
                </div>
            </div>

            <div class="flex items-center gap-4 text-xs mt-2">
                <button @click.prevent="handleLikesComment" :disabled="refComment.isDislikedByAuth || isCommentOwner"
                    class="flex items-center space-x-1 px-2 py-1 rounded-md text-green-500 disabled:text-gray-600">
                    <div class="flex justify-around items-center space-x-2"
                        :class="isCommentOwner || refComment.isDislikedByAuth ? '' : 'hover:scale-125'">
                        <span v-if="isLoading">
                            <Icon icon="eos-icons:bubble-loading" class="h-5 w-5" />
                        </span>
                        <div v-else>
                            <span v-if="refComment.isLikedByAuth">
                                <Icon icon="icon-park-solid:like" class="w-5 h-5" />
                            </span>
                            <span v-else>
                                <Icon icon="icon-park-outline:like" class="w-5 h-5" />
                            </span>
                        </div>
                    </div>
                    <span>{{ refComment.likes || 0 }}</span>
                </button>

                <button @click.prevent="handleDislikesComment" :disabled="refComment.isLikedByAuth || isCommentOwner"
                    class="flex text-center space-x-1 px-2 py-1 rounded-md text-red-500 disabled:text-gray-600">
                    <div class="flex justify-around items-center space-x-2"
                        :class="[isCommentOwner || refComment.isLikedByAuth ? '' : 'hover:scale-125']">
                        <span v-if="isDislikeLoading">
                            <Icon icon="eos-icons:bubble-loading" class="h-5 w-5" />
                        </span>
                        <div v-else>
                            <span v-if="refComment.isDislikedByAuth">
                                <Icon icon="heroicons-solid:thumb-down" class="w-5 h-5" />
                            </span>
                            <span v-else>
                                <Icon icon="heroicons-outline:thumb-down" class="w-5 h-5" />
                            </span>
                        </div>
                    </div>
                    <span>{{ refComment.dislikes || 0 }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
