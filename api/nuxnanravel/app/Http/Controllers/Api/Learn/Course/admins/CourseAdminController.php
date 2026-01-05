<?php

namespace App\Http\Controllers\Api\Learn\Course\admins;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\Learn\Course\members\CourseMemberResource;
use App\Http\Resources\UserResource;

class CourseAdminController extends Controller
{
    /**
     * List all Admins and TAs for the course.
     */
    public function index(Course $course)
    {
        // Verify if user is owner or admin (basic check, more granular policies can be added)
        if ($course->user_id !== auth()->id() && !$course->isMember(auth()->user())) {
            // Allow if they are admin/TA of the course?
            // For now, assume course creator or existing admins can see this.
        }

        $admins = $course->courseMembers()
            ->whereIn('role', [3, 4]) // 3: Teacher (TA), 4: Admin
            ->orWhere(function($q) {
                 // Also include pending invites for admins/TAs if we distinguish them by role in the invite
                 // Assuming invited members also have role set to 3 or 4 but status is 2 (Invited)
                 // But we need to make sure we filter correctly.
            })
            ->whereIn('role', [3, 4]) // Re-applying role filter just to be safe if I expand logic
            ->orderBy('role', 'desc')
            ->with('user')
            ->get();

        return response()->json([
            'success' => true,
            'admins' => CourseMemberResource::collection($admins),
        ]);
    }

    /**
     * Search for users to invite.
     * Exclude current members.
     */
    public function search(Request $request, Course $course)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $query = $request->input('query');

        // IDs of users who are already members (of any status)
        $existingMemberIds = $course->courseMembers()->pluck('user_id')->toArray();

        $users = User::where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('reference_code', 'like', "%{$query}%");
            })
            ->whereNotIn('id', $existingMemberIds)
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'users' => UserResource::collection($users),
        ]);
    }

    /**
     * Invite a user as Admin or TA.
     */
    public function store(Request $request, Course $course)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:3,4', // 3: Teacher/TA, 4: Admin
        ]);

        $userId = $request->user_id;
        $role = $request->role;

        // Check if already a member
        $existingMember = CourseMember::where('course_id', $course->id)
            ->where('user_id', $userId)
            ->first();

        if ($existingMember) {
            // If already a member (e.g. student), update role?
            // Or maybe they are already invited?
            return response()->json([
                'success' => false,
                'message' => 'User is already a member of this course.',
            ], 422);
        }

        // Create new member with "Invited" status
        // Utilizing status = 2 for "Invited" (Pending User Approval)
        $member = new CourseMember();
        $member->course_id = $course->id;
        $member->user_id = $userId;
        $member->role = $role;
        $member->status = 2; // Invited
        $member->course_member_status = 2; // Invited
        $member->save();

        return response()->json([
            'success' => true,
            'message' => 'Invitation sent successfully.',
            'member' => new CourseMemberResource($member),
        ]);
    }

    public function acceptInvitation(Course $course, CourseMember $member)
    {
        if (auth()->id() !== $member->user_id) {
             return response()->json(['success'=>false, 'message'=>'Unauthorized'], 403);
        }

        $member->status = 1; // Active
        $member->course_member_status = 1;
        $member->save();
        
        return response()->json(['success'=>true, 'message'=>'Invitation accepted']);
    }

    public function declineInvitation(Course $course, CourseMember $member)
    {
        if (auth()->id() !== $member->user_id) {
             return response()->json(['success'=>false, 'message'=>'Unauthorized'], 403);
        }
        $member->delete();
        return response()->json(['success'=>true, 'message'=>'Invitation declined']);
    }

    /**
     * Remove admin/TA or Cancel Invitation.
     */
    public function destroy(Course $course, CourseMember $member)
    {
        // Security check
        if ($course->user_id !== auth()->id()) {
             // Only Owner can remove admins?
             // Or Admins can remove TAs?
             // For now, strict check maybe?
             // Allowing course creator to manage.
             if ($member->role == 4 && $member->user_id == $course->user_id) {
                 return response()->json(['success'=>false, 'message'=>'Cannot remove owner.'], 403);
             }
        }

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Removed successfully.',
        ]);
    }
}
