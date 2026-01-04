<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    groupName: String,
    members: Array,
    isCourseAdmin: Boolean,
});

const emit = defineEmits(['update:members']);

// Helper to format number safely
const fmt = (n) => typeof n === 'number' ? n : '-';
</script>

<template>
    <div class="overflow-x-auto bg-white rounded-lg shadow border border-gray-200">
        <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-lg text-gray-700">{{ groupName }}</h3>
            <span class="text-sm text-gray-500">จำนวน {{ members.length }} คน</span>
        </div>
        
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                <tr>
                    <th scope="col" class="px-4 py-3 sticky left-0 bg-gray-100 z-10 w-12">#</th>
                    <th scope="col" class="px-4 py-3 sticky left-12 bg-gray-100 z-10 min-w-[200px]">สมาชิก</th>
                    <th scope="col" class="px-4 py-3 text-center">แบบฝึกหัด<br/>บทเรียน</th>
                    <th scope="col" class="px-4 py-3 text-center">แบบทดสอบ<br/>บทเรียน</th>
                    <th scope="col" class="px-4 py-3 text-center">งาน<br/>รายวิชา</th>
                    <th scope="col" class="px-4 py-3 text-center">แบบทดสอบ<br/>รายวิชา</th>
                    <th scope="col" class="px-4 py-3 text-center font-bold text-blue-700 bg-blue-50">รวม</th>
                    <th scope="col" class="px-4 py-3 text-center font-bold text-blue-700 bg-blue-50">เกรด</th>
                    <th scope="col" class="px-4 py-3 text-center">สถานะ</th>
                    <th scope="col" class="px-4 py-3 text-center" v-if="isCourseAdmin">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(member, index) in members" :key="member.id" class="bg-white border-b hover:bg-gray-50">
                    <td class="px-4 py-3 sticky left-0 bg-white group-hover:bg-gray-50">{{ index + 1 }}</td>
                    <td class="px-4 py-3 sticky left-12 bg-white group-hover:bg-gray-50 font-medium text-gray-900 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <img :src="member.user?.avatar_url || 'https://ui-avatars.com/api/?name='+ member.member_name" class="w-8 h-8 rounded-full" alt="">
                            <div class="flex flex-col">
                                <span>{{ member.member_name }}</span>
                                <span class="text-xs text-gray-400">{{ member.member_code || '-' }}</span>
                            </div>
                        </div>
                    </td>
                    
                    <!-- Lesson Assignments -->
                    <td class="px-4 py-3 text-center">{{ fmt(member.scores?.lesson_assignments) }}</td>
                    
                    <!-- Lesson Quizzes -->
                    <td class="px-4 py-3 text-center">{{ fmt(member.scores?.lesson_quizzes) }}</td>
                    
                    <!-- Course Assignments -->
                    <td class="px-4 py-3 text-center">{{ fmt(member.scores?.course_assignments) }}</td>
                    
                    <!-- Course Quizzes -->
                    <td class="px-4 py-3 text-center">{{ fmt(member.scores?.course_quizzes) }}</td>
                    
                    <!-- Total -->
                    <td class="px-4 py-3 text-center font-bold text-blue-700 bg-blue-50">
                        {{ fmt(member.scores?.total_score) }}
                    </td>
                    
                    <!-- Grade -->
                    <td class="px-4 py-3 text-center font-bold text-lg" 
                        :class="{'text-green-600': member.scores?.grade >= 2, 'text-red-600': member.scores?.grade < 1}">
                        {{ member.scores?.grade_name || '-' }} 
                        <span class="text-xs text-gray-400 font-normal">({{ member.scores?.grade }})</span>
                    </td>
                    
                    <!-- Status -->
                    <td class="px-4 py-3 text-center">
                         <span v-if="member.course_member_status === 1" class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">ปกติ</span>
                         <span v-else class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">พักการเรียน</span>
                    </td>

                    <!-- Actions -->
                    <td class="px-4 py-3 text-center" v-if="isCourseAdmin">
                        <Link :href="route('course.admin.member.progress.show', member.id)" 
                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded text-xs px-3 py-1.5 focus:outline-none">
                            รายละเอียด
                        </Link>
                    </td>
                </tr>
                <tr v-if="members.length === 0">
                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                        ไม่พบข้อมูลสมาชิกในกลุ่มนี้
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
