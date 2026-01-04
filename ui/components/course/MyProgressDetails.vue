<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useApi } from '~/composables/useApi';
import { Icon } from '@iconify/vue';

const props = defineProps({
    courseId: { type: [String, Number], required: true },
    memberId: { type: [String, Number], required: true },
});

const api = useApi();
const loading = ref(true);
const data = ref(null);

const fetchData = async () => {
    loading.value = true;
    try {
        const res = await api.get(`/api/courses/${props.courseId}/members/${props.memberId}/progress`);
        data.value = {
            assignments: [],
            quizzes: [],
            lessons: [],
            ...res
        };
        
        // Populate form
        if (data.value.member) {
            form.value = {
                member_name: data.value.member.member_name || data.value.member.user?.name || '',
                member_code: data.value.member.member_code || '',
                order_number: data.value.member.order_number || '',
            };
        }
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);

const stats = computed(() => {
    if (!data.value) return {};
    const d = data.value;
    
    // Calculate total score
    const totalScore = (d.assignments?.reduce((sum, a) => sum + (a.score || 0), 0) || 0) +
                       (d.quizzes?.reduce((sum, q) => sum + (q.score || 0), 0) || 0);
    
    const maxScore = (d.assignments?.reduce((sum, a) => sum + (a.max_score || 0), 0) || 0) +
                     (d.quizzes?.reduce((sum, q) => sum + (q.max_score || 0), 0) || 0);

    return {
        totalScore,
        maxScore,
        grade: d.member?.grade_name || '-',
        completedLessons: d.lessons?.filter(l => l.completed).length || 0,
        totalLessons: d.lessons?.length || 0,
        completedAssignments: d.assignments?.filter(a => a.submitted).length || 0,
        totalAssignments: d.assignments?.length || 0,
        completedQuizzes: d.quizzes?.filter(q => q.completed).length || 0,
        totalQuizzes: d.quizzes?.length || 0,
        groupName: d.member?.group?.name || 'ไม่มีกลุ่ม',
    };
});

// Profile Editing
const form = ref({
    member_name: '',
    member_code: '',
    order_number: '',
});
const isSaving = ref(false);
const saveSuccess = ref(false);

const saveProfile = async () => {
    isSaving.value = true;
    saveSuccess.value = false;
    try {
        await api.patch(`/api/courses/${props.courseId}/members/${props.memberId}/update`, form.value);
        saveSuccess.value = true;
        setTimeout(() => saveSuccess.value = false, 3000);
        await fetchData(); // Refresh data
    } catch (e) {
        console.error(e);
        alert('บันทึกข้อมูลไม่สำเร็จ');
    } finally {
        isSaving.value = false;
    }
};

const getScoreColor = (score, max) => {
    if (!max) return 'text-gray-500';
    const pct = (score / max) * 100;
    if (pct >= 80) return 'text-green-600';
    if (pct >= 50) return 'text-blue-600';
    return 'text-red-600';
};

// Tabs
const activeTab = ref('lessons');
const tabs = [
    { id: 'lessons', label: 'บทเรียน', icon: 'fluent:book-open-24-filled' },
    { id: 'assignments', label: 'งานที่มอบหมาย', icon: 'fluent:document-text-24-filled' },
    { id: 'quizzes', label: 'แบบทดสอบ', icon: 'fluent:quiz-new-24-filled' },
];
</script>

<template>
    <div class="space-y-6">
        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
            <Icon icon="eos-icons:loading" class="w-10 h-10 text-blue-600" />
        </div>

        <div v-else-if="data" class="animate-fade-in">
             
             <!-- Profile Settings Card -->
             <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <div>
                         <h3 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                             <Icon icon="fluent:person-edit-24-filled" class="text-blue-500" />
                             ข้อมูลส่วนตัว
                         </h3>
                         <p class="text-sm text-gray-500">แก้ไขข้อมูลพื้นฐานของคุณในรายวิชานี้</p>
                    </div>
                    <div class="px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-medium">
                        กลุ่มเรียน: {{ stats.groupName }}
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เลขที่ (Order No.)</label>
                        <input v-model="form.order_number" type="number" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="ระบุเลขที่..." />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รหัสประจำตัว (Student ID)</label>
                        <input v-model="form.member_code" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="ระบุรหัสประจำตัว..." />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ-นามสกุล (Name)</label>
                        <input v-model="form.member_name" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="ระบุชื่อ-นามสกุล..." />
                    </div>
                </div>
                
                <div class="mt-6 flex items-center justify-end gap-3">
                     <span v-if="saveSuccess" class="text-green-600 text-sm flex items-center animate-fade-in">
                         <Icon icon="fluent:checkmark-circle-24-filled" class="mr-1" /> บันทึกเรียบร้อย
                     </span>
                     <button 
                        @click="saveProfile" 
                        :disabled="isSaving"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                        <Icon v-if="isSaving" icon="eos-icons:loading" class="mr-2 animate-spin" />
                        {{ isSaving ? 'กำลังบันทึก...' : 'บันทึกการเปลี่ยนแปลง' }}
                     </button>
                </div>
             </div>

             <!-- Header Stats -->
             <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="text-sm text-gray-500 mb-1">เกรดปัจจุบัน</div>
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ stats.grade }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="text-sm text-gray-500 mb-1">คะแนนรวม</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ stats.totalScore }} <span class="text-sm text-gray-400 font-normal">/ {{ stats.maxScore }}</span>
                    </div>
                </div>
                 <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="text-sm text-gray-500 mb-1">งานที่ส่งแล้ว</div>
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ stats.completedAssignments }} <span class="text-sm text-gray-400 font-normal">/ {{ stats.totalAssignments }}</span>
                    </div>
                </div>
                 <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="text-sm text-gray-500 mb-1">ทดสอบแล้ว</div>
                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                        {{ stats.completedQuizzes }} <span class="text-sm text-gray-400 font-normal">/ {{ stats.totalQuizzes }}</span>
                    </div>
                </div>
             </div>

             <!-- Tabs Navigation -->
             <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6 overflow-x-auto">
                 <button 
                    v-for="tab in tabs" 
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    class="px-4 py-3 text-sm font-medium flex items-center gap-2 whitespace-nowrap transition-colors border-b-2"
                    :class="activeTab === tab.id 
                        ? 'border-blue-600 text-blue-600 dark:text-blue-400' 
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                 >
                     <Icon :icon="tab.icon" class="w-5 h-5" />
                     {{ tab.label }}
                 </button>
             </div>

             <!-- Tab Content -->
             <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                 
                 <!-- Lessons Tab -->
                 <div v-if="activeTab === 'lessons'">
                     <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
                         <h3 class="font-semibold text-gray-800 dark:text-white">รายการบทเรียน</h3>
                         <span class="text-sm text-gray-500">{{ stats.completedLessons }}/{{ stats.totalLessons }}</span>
                     </div>
                     <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-if="data.lessons && data.lessons.length === 0" class="p-8 text-center text-gray-500">
                            ไม่มีบทเรียนในรายวิชานี้
                        </div>
                         <div v-for="lesson in data.lessons" :key="lesson.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                             <div class="flex justify-between items-center">
                                 <div class="flex items-center gap-3">
                                     <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                                         :class="lesson.completed ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400'">
                                         <Icon :icon="lesson.completed ? 'fluent:checkmark-24-filled' : 'fluent:circle-24-regular'" class="w-5 h-5" />
                                     </div>
                                     <div class="font-medium text-gray-900 dark:text-white">{{ lesson.title }}</div>
                                 </div>
                                 <span class="text-xs px-2 py-1 rounded-full"
                                     :class="lesson.completed ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'">
                                     {{ lesson.completed ? 'เรียนแล้ว' : 'ยังไม่เรียน' }}
                                 </span>
                             </div>
                         </div>
                     </div>
                 </div>

                 <!-- Assignments Tab -->
                 <div v-if="activeTab === 'assignments'">
                     <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
                         <h3 class="font-semibold text-gray-800 dark:text-white">รายการงานที่มอบหมาย</h3>
                         <span class="text-sm text-gray-500">{{ stats.completedAssignments }}/{{ stats.totalAssignments }}</span>
                     </div>
                     <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-if="data.assignments && data.assignments.length === 0" class="p-8 text-center text-gray-500">
                            ไม่มีงานที่มอบหมายในรายวิชานี้
                        </div>
                         <div v-for="assign in data.assignments" :key="assign.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                             <div class="flex justify-between items-start">
                                 <div>
                                     <div class="font-medium text-gray-900 dark:text-white">{{ assign.title }}</div>
                                     <div class="text-xs mt-1" :class="{
                                         'text-green-600': assign.submitted,
                                         'text-yellow-600': !assign.submitted
                                     }">
                                         {{ assign.submitted ? (assign.graded ? 'ตรวจแล้ว' : 'ส่งแล้ว') : 'ยังไม่ส่ง' }}
                                     </div>
                                     <div class="text-xs text-gray-400 mt-1" v-if="assign.submitted_at">
                                         ส่งเมื่อ: {{ new Date(assign.submitted_at).toLocaleDateString('th-TH') }}
                                     </div>
                                 </div>
                                 <div class="text-right">
                                     <div class="font-bold text-lg" :class="getScoreColor(assign.score, assign.max_score)">
                                         {{ assign.score !== null ? assign.score : '-' }}
                                     </div>
                                     <div class="text-xs text-gray-400">เต็ม {{ assign.max_score }}</div>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>

                 <!-- Quizzes Tab -->
                 <div v-if="activeTab === 'quizzes'">
                     <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
                         <h3 class="font-semibold text-gray-800 dark:text-white">รายการแบบทดสอบ</h3>
                         <span class="text-sm text-gray-500">{{ stats.completedQuizzes }}/{{ stats.totalQuizzes }}</span>
                     </div>
                     <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-if="data.quizzes && data.quizzes.length === 0" class="p-8 text-center text-gray-500">
                            ไม่มีแบบทดสอบในรายวิชานี้
                        </div>
                         <div v-for="quiz in data.quizzes" :key="quiz.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                             <div class="flex justify-between items-start">
                                 <div>
                                     <div class="font-medium text-gray-900 dark:text-white">{{ quiz.title }}</div>
                                     <div class="text-xs mt-1 flex items-center gap-2" :class="{
                                         'text-green-600': quiz.completed,
                                         'text-gray-500': !quiz.completed
                                     }">
                                         <span>{{ quiz.completed ? `ทำแล้ว (${quiz.attempt_count} ครั้ง)` : 'ยังไม่ทำ' }}</span>
                                         <span v-if="quiz.completed && quiz.passed" class="text-green-600 bg-green-100 px-1.5 py-0.5 rounded text-[10px]">ผ่าน</span>
                                         <span v-if="quiz.completed && !quiz.passed" class="text-red-600 bg-red-100 px-1.5 py-0.5 rounded text-[10px]">ไม่ผ่าน</span>
                                     </div>
                                     <div class="text-xs text-gray-400 mt-1" v-if="quiz.completed_at">
                                         ล่าสุด: {{ new Date(quiz.completed_at).toLocaleDateString('th-TH') }}
                                     </div>
                                 </div>
                                 <div class="text-right">
                                     <div class="font-bold text-lg" :class="getScoreColor(quiz.score, quiz.max_score)">
                                         {{ quiz.score !== null ? quiz.score : '-' }}
                                     </div>
                                     <div class="text-xs text-gray-400">เต็ม {{ quiz.max_score }}</div>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>

             </div>
        </div>
    </div>
</template>
