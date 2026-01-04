<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/layouts/main.vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
    course: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object,
    activeTab: Number,
});
</script>

<template>
    <MainLayout :title="course ? course.name : 'Course'">
        <template #header>
            <div class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ course ? course.name : 'Course' }}
                    </h2>
                </div>
            </div>
        </template>

        <template #mainContent>
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <!-- Course Header / Banner Placeholder -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6 p-6" v-if="course">
                         <div class="flex items-center">
                            <div class="ml-4">
                                <div class="text-2xl font-bold">{{ course.name }}</div>
                                <div class="text-gray-500">{{ course.code }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Slot for Page Content -->
                    <slot name="courseContent" />
                </div>
            </div>
        </template>
    </MainLayout>
</template>
