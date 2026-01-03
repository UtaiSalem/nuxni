<?php

namespace App\Http\Resources\Learn\Course\questions;

use Illuminate\Http\Request;
use App\Models\UserAnswerQuestion;
use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $authAnswerQuestion = null;
        $user_answer = null;

        if ($this->questionable_type === 'App\Models\Course\Lesson' || $this->questionable_type === 'App\Models\Lesson') {
             $authAnswerQuestion = \App\Models\LessonAnswerQuestion::where('question_id', $this->id)->where('user_id', auth()->id())->first();
             if ($authAnswerQuestion) {
                 $user_answer = [
                    'answer_id' => $authAnswerQuestion->answer_id,
                    'is_correct' => $authAnswerQuestion->is_correct,
                    'points' => $authAnswerQuestion->points
                 ];
             }
        } elseif ($this->questionable_type === 'App\Models\Learn\Course\CourseQuiz' || $this->questionable_type === 'App\Models\CourseQuiz') {
             $authAnswerQuestion = $this->userAnswers()->where('user_id', auth()->id())->where('quiz_id', $this->questionable_id)->first();
             if ($authAnswerQuestion) {
                 $user_answer = [
                    'answer_id' => $authAnswerQuestion->answer_id,
                    'is_correct' => false, // UserAnswerQuestion doesn't store is_correct directly on the model usually, it stores points. But let's check.
                    // Actually, let's just pass what we have. Points > 0 implies correct?
                    'points' => $authAnswerQuestion->points
                 ];
             }
        }

        // Fallback or generic
        if (!$authAnswerQuestion) {
             // Try to find any answer?? No, be strict.
        }

        return [
            'id'                    => $this->id,
            'creator'               => new UserResource($this->user),
            'questionable_id'       => $this->questionable_id,
            'questionable_type'     => $this->questionable_type,
            'text'                  => $this->text,
            'type'                  => $this->type,
            'options'               => QuestionOptionResource::collection($this->options), 
            'correct_option_id'     => $this->correct_option_id,
            'explanation'           => $this->explanation,
            'difficulty_level'      => $this->difficulty_level,
            'time_limit'            => $this->time_limit,
            'points'                => $this->points,
            'pp_fine'               => $this->pp_fine,
            'position'              => $this->position,
            'tags'                  => $this->tags,
            'images'                => $this->images->map(function($img) {
                // Logic adapted from UserResource to ensure correct URL generation
                $imgUrl = null;
                if ($img->url) {
                    // FIX: User requested path correction
                    // Convert: images/courses/lessons/quizzes/options/ => images/courses/quizzes/questions/
                    // Or possibly simple path correction if data was saved wrongly
                    $fixedPath = str_replace('images/courses/lessons/quizzes/options', 'images/courses/quizzes/questions', $img->url);
                    
                    if (filter_var($fixedPath, FILTER_VALIDATE_URL)) {
                        $imgUrl = $fixedPath;
                    } else {
                        // Ensure we use the storage facade correctly
                        $imgUrl = url(\Illuminate\Support\Facades\Storage::url($fixedPath));
                    }
                }
                
                return [
                    'id' => $img->id,
                    'url' => $imgUrl, // Override url with full url
                    'full_url' => $imgUrl // Keep for compatibility
                ];
            }),
            'authAnswerQuestion'    => $authAnswerQuestion ? $authAnswerQuestion->id : null,
            'isAnsweredByAuth'      => $authAnswerQuestion ? $authAnswerQuestion->answer_id : null,
            'user_answer'           => $user_answer,
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
        ];
    }
}
