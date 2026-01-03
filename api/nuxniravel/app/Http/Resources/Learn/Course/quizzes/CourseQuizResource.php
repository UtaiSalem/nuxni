<?php

namespace App\Http\Resources\Learn\Course\quizzes;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Learn\Course\questions\QuestionResource;

class CourseQuizResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $member_achieved_score  = $this->userResults()->where('user_id', auth()->id())->where('course_id', $this->course_id)->first();
        // $course_members_score was actually intended to be the single user score according to user?
        // But the variable name 'course_members_score' implies plural or sum?
        // User said: "course_members_score is the score of that student... not array"
        
        $course_members_score = $member_achieved_score ? $member_achieved_score->score : 0;
        
        // For Admin view, we need the list
        $student_results   = $this->userResults()->where('course_id', $this->course_id)->with('user')->get();
        
        $questions              = $this->shuffle_questions === 1 ? $this->questions->shuffle() : $this->questions;
        return [
            'id'                => $this->id,
            'title'             => $this->title,
            'description'       => $this->description,
            'image'             => $this->image ? url($this->image) : null,
            'user'              => $this->user,
            'start_date'        => $this->start_date,
            'end_date'          => $this->end_date,
            'time_limit'        => $this->time_limit,
            'total_score'       => $this->total_score,
            'passing_score'     => $this->passing_score,
            'is_active'         => $this->is_active,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'questions_count'   => $this->questions->count(),
            'questions'         => QuestionResource::collection($questions),
            'member_achieved_score' => $member_achieved_score ? $member_achieved_score->score : 0,
            'course_members_score' => $course_members_score, // Now a single value/score
            'student_results'   => $student_results, // For Admin View (New Key)
            'current_result'    => $member_achieved_score,
            // 'start_date'        => $this->start_date->format('Y-m-d H:i:s'), // Format start_date
            // 'end_date'          => $this->end_date->format('Y-m-d H:i:s'), // Format end_date
        ];
    }
}
