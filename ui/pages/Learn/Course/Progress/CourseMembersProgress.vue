<script setup>
import { ref, computed } from 'vue';
import { useApi } from '~/composables/useApi';

import CourseLayout from '@/Layouts/CourseLayout.vue';
import StaggeredFade from '@/components/accessories/StaggeredFade.vue';
import MembersProgress from '@/components/learn/course/progress/MembersProgress.vue';
import MyProgressDetails from '@/components/learn/course/MyProgressDetails.vue';
import Modal from '@/components/Modal.vue';
import { Icon } from '@iconify/vue';

const api = useApi();

const props = defineProps({
    course: Object,
    groups: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object,
    members: Object,
    assignments: Object,
    quizzes: Object,
    courseMembersProgress: Object
});

// Find the index of the group matching last_accessed_group_tab ID
const getInitialGroupTab = () => {
    const lastAccessedGroupId = props.courseMemberOfAuth?.last_accessed_group_tab;
    if (!lastAccessedGroupId) return 0;
    
    const groups = props.groups?.data || [];
    const index = groups.findIndex(group => group.id === lastAccessedGroupId);
    return index >= 0 ? index : 0;
};

const activeGroupTab = ref(getInitialGroupTab());

async function setActiveGroupTab(tabIndex) {
    activeGroupTab.value = tabIndex;

    // Get the actual group ID from the index
    const groups = props.groups?.data || [];
    if (tabIndex < groups.length && props.courseMemberOfAuth) {
        const groupId = groups[tabIndex].id;
        try {
            const resp = await api.post(`/api/courses/${props.course.data.id}/members/${props.courseMemberOfAuth.id}/set-active-group-tab`, {group_tab: groupId});
            if (resp.success) {
                props.courseMemberOfAuth.last_accessed_group_tab = groupId;
            }
        } catch (error) {
            console.error('Error saving group tab:', error);
        }
    }
}

const unGroupedMembers = computed(() => {
    const members = props.members?.data || [];
    const courseOwnerId = props.course?.data?.user?.id;
    return members.filter(member => !member.group).filter(member => member.user?.id !== courseOwnerId);
});

/* Dashboard Stats Logic */
const memberStats = computed(() => {
    const allMembers = props.members?.data || [];
    if (!allMembers.length) return { average: 0, max: 0, min: 0, passCount: 0, failCount: 0, passRate: 0 };
    
    const scores = allMembers.map(m => m.total_score || 0);
    const sum = scores.reduce((a, b) => a + b, 0);
    const avg = (sum / scores.length) || 0;
    const max = Math.max(...scores);
    const min = Math.min(...scores);
    
    // Pass count (assuming 50% threshold like in Details page)
    const threshold = (props.course?.data?.total_score || 100) / 2;
    const passCount = scores.filter(s => s >= threshold).length;
    
    return {
        average: avg.toFixed(1),
        max,
        min,
        passCount,
        failCount: scores.length - passCount,
        passRate: ((passCount / scores.length) * 100).toFixed(1)
    };
});

const searchQuery = ref('');
const filteredActiveGroupMembers = computed(() => {
    const groups = props.groups?.data || [];
    let members = [];
    
    if (activeGroupTab.value < groups.length) {
        members = groups[activeGroupTab.value]?.members || [];
    } else {
        members = unGroupedMembers.value;
    }

    // Merge detailed scores from courseMembersProgress prop
    if (props.courseMembersProgress) {
        members = members.map(member => {
             const progressData = props.courseMembersProgress.find(p => p.member.id === member.id);
             if (progressData && progressData.scores) {
                 return { ...member, scores: progressData.scores };
             }
             return member;
        });
    }
    
    if (!searchQuery.value) return members;
    
    const query = searchQuery.value.toLowerCase();
    return members.filter(m => 
        (m.member_name && m.member_name.toLowerCase().includes(query)) ||
        (m.member_code && m.member_code.toLowerCase().includes(query))
    );
});

// Computed helper for template
const groupsData = computed(() => props.groups?.data || []);

// Modal State
const showMemberModal = ref(false);
const selectedMember = ref(null);

const openMemberModal = (member) => {
    selectedMember.value = member;
    showMemberModal.value = true;
};
</script>

<template>
    <div>
        <CourseLayout
            :course="props.course"
            :lessons="props.lessons"
            :groups="props.groups"
            :isCourseAdmin="props.isCourseAdmin"
            :courseMemberOfAuth="props.courseMemberOfAuth"
            :activeTab="10"
        >
            <template #courseContent>
                <div v-if="props.isCourseAdmin" class=" md:-ml-4 md:mr-4">
                    
                    <!-- Dashboard Summary -->
                    <div class="mb-6 px-4">
                        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                             <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
                                <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                ภาพรวมผลการเรียน (Dashboard)
                            </h2>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                    <p class="text-sm text-gray-600">คะแนนเฉลี่ย</p>
                                    <p class="text-2xl font-bold text-blue-700">{{ memberStats.average }}</p>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                                    <p class="text-sm text-gray-600">ผ่านเกณฑ์</p>
                                    <p class="text-2xl font-bold text-green-700">{{ memberStats.passCount }} <span class="text-sm text-gray-500">({{ memberStats.passRate }}%)</span></p>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
                                    <p class="text-sm text-gray-600">คะแนนสูงสุด</p>
                                    <p class="text-2xl font-bold text-purple-700">{{ memberStats.max }}</p>
                                </div>
                                <div class="bg-red-50 p-4 rounded-lg border border-red-100">
                                    <p class="text-sm text-gray-600">คะแนนต่ำสุด</p>
                                    <p class="text-2xl font-bold text-red-700">{{ memberStats.min }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search Filter -->
                    <div class="px-4 mb-4">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input 
                                v-model="searchQuery"
                                type="text" 
                                class="w-full py-2 pl-10 pr-4 text-gray-700 bg-white border rounded-lg focus:border-blue-400 focus:outline-none focus:ring focus:ring-blue-300 focus:ring-opacity-40" 
                                placeholder="ค้นหาสมาชิกด้วยชื่อ หรือ รหัสนักศึกษา..."
                            />
                        </div>
                    </div>

                    <section class="" aria-multiselectable="false">
                        <ul v-if="props.isCourseAdmin"
                            class="flex flex-wrap items-center border-b plearnd-card bg-gradient-to-r from-blue-50 via-green-50 to-yellow-50" role="tablist">
                            <li v-for="(group, index) in groupsData" :key="index" class="w-1/2 md:w-1/3 lg:w-1/4" role="presentation ">
                                <button @click.prevent="setActiveGroupTab(index)"
                                    class="inline-flex items-center justify-center w-full h-12 gap-2 px-6 mb-2 text-sm tracking-wide transition duration-200 border-b-2 rounded-t focus-visible:outline-none whitespace-nowrap font-medium shadow-sm hover:scale-105"
                                    :class="activeGroupTab === index
                                        ? 'border-blue-500 bg-gradient-to-r from-blue-200 via-green-100 to-yellow-100 text-blue-900 font-bold shadow-lg'
                                        : 'border-transparent bg-white text-gray-600 hover:bg-blue-100 hover:text-blue-700'"
                                    id="tab-label-1a" role="tab" aria-setsize="3" aria-posinset="1" tabindex="0"
                                    aria-controls="tab-panel-1a" aria-selected="true">
                                    <span>{{ group.name + ' (' + (group.members?.length || 0) + ')'  }}</span>
                                </button>
                            </li>
                            <li v-if="unGroupedMembers.length > 0" class="w-1/2 md:w-1/3 lg:w-1/4" role="presentation ">
                                <button @click.prevent="setActiveGroupTab(groupsData.length)"
                                    class="inline-flex items-center justify-center w-full h-12 gap-2 px-6 mb-2 text-sm tracking-wide transition duration-200 border-b-2 rounded-t focus-visible:outline-none whitespace-nowrap font-medium shadow-sm hover:scale-105"
                                    :class="activeGroupTab === groupsData.length
                                        ? 'border-blue-500 bg-gradient-to-r from-blue-200 via-green-100 to-yellow-100 text-blue-900 font-bold shadow-lg'
                                        : 'border-transparent bg-white text-gray-600 hover:bg-blue-100 hover:text-blue-700'"
                                    id="tab-label-1a" role="tab" aria-setsize="3" aria-posinset="1" tabindex="0"
                                    aria-controls="tab-panel-1a" aria-selected="true">
                                    <span>{{ 'ไม่มีกลุ่ม'+ ' (' + unGroupedMembers.length + ')' }}</span>
                                </button>
                            </li>
                        </ul>
                    </section>
                    <section class="">
                        <div class="bg-gradient-to-br from-blue-50 via-green-50 to-yellow-50 mt-4 p-4 rounded-xl shadow-lg border border-blue-100" id="tab-panel-1a" aria-hidden="false"
                            role="tabpanel" aria-labelledby="tab-label-1a" tabindex="-1">
                            <staggered-fade :duration="50" tag="ul" class="flex flex-col w-full ">
                                <MembersProgress 
                                :groupName="groupsData[activeGroupTab]?.name || 'ไม่มีกลุ่ม'"
                                :members="filteredActiveGroupMembers"
                                :isCourseAdmin="props.isCourseAdmin"
                                @view="openMemberModal"
                                />
                            </staggered-fade>
                        </div>
                    </section>
                </div>
                
                <!-- Student View -->
                <div v-else class="p-4">
                     <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <Icon icon="fluent:chart-person-24-filled" class="text-blue-600" />
                        ผลการเรียนของฉัน (My Progress)
                     </h2>
                    <MyProgressDetails 
                        v-if="props.courseMemberOfAuth"
                        :courseId="props.course.data.id" 
                        :memberId="props.courseMemberOfAuth.id" 
                    />
                    <div v-else class="text-center text-gray-500 py-10">
                        ไม่พบข้อมูลสมาชิกของท่านในรายวิชานี้
                    </div>
                </div>
            </template>
        </CourseLayout>

        <!-- Admin View Member Details Modal -->
        <Modal :show="showMemberModal" @close="showMemberModal = false" maxWidth="4xl">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                            {{ selectedMember?.member_name?.charAt(0) || 'U' }}
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 dark:text-white">
                                ผลการเรียนของ {{ selectedMember?.member_name }}
                            </h3>
                            <p class="text-sm text-gray-500">{{ selectedMember?.member_code || '-' }}</p>
                        </div>
                    </div>
                    <button @click="showMemberModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors text-gray-500">
                        <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
                    </button>
                </div>
                
                <div class="max-h-[80vh] overflow-y-auto">
                    <MyProgressDetails 
                        v-if="selectedMember"
                        :courseId="props.course.data.id" 
                        :memberId="selectedMember.id"
                        :key="selectedMember.id"
                    />
                </div>
            </div>
        </Modal>
    </div>
</template>
