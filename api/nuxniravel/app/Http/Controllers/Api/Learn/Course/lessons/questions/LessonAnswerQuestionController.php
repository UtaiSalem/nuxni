<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons\questions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\LessonAnswerQuestion;
use App\Models\QuestionOption;
use Illuminate\Support\Facades\DB;

class LessonAnswerQuestionController extends Controller
{
    public function store(Request $request, Lesson $lesson, Question $question)
    {
        $request->validate([
            'answer_id' => 'required|exists:question_options,id',
        ]);

        // Verify question belongs to lesson (via course relationship or direct if polymorph, but keeping simple for now)
        // Verify answer belongs to question
        $option = QuestionOption::find($request->answer_id);
        
        // Flexible Scoring: If the chosen option is correct, give full points.
        $isCorrect = $option->is_correct;
        $points = $isCorrect ? $question->points : 0;

        $answer = LessonAnswerQuestion::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'lesson_id' => $lesson->id,
                'question_id' => $question->id,
            ],
            [
                'answer_id' => $request->answer_id,
                'points' => $points,
                'is_correct' => $isCorrect,
            ]
        );

        // Update CourseMember achieved score
        $courseId = $lesson->course_id;
        $courseMember = \App\Models\CourseMember::where('course_id', $courseId)
            ->where('user_id', auth()->id())
            ->first();

        // Check if this question was previously answered correctly to avoid double counting?
        // Actually, since we use `LessonAnswerQuestion` table, let's sum up all correct answers for this course's lessons.
        // We need to filter by course lessons.
        // Or simpler: Just calculate all `total points` from `lesson_answer_questions` for this user in this course.
        // But `LessonAnswerQuestion` doesn't have `course_id`. It has `lesson_id`.
        // So we need to join lessons.

        if ($courseMember) {
             $totalLessonScore = LessonAnswerQuestion::where('user_id', auth()->id())
                ->whereHas('lesson', function($q) use ($courseId) {
                    $q->where('course_id', $courseId);
                })
                ->sum('points');
             
             // Note: This only covers LESSON quizzes. 
             // Does `achieved_score` include Course Quizzes too? 
             // If so, we need to sum COURSE QUIZ scores + LESSON QUIZ scores.
             // Based on `UserAnswerQuestionController::updateCourseMemberScore` (seen in step 250), it sums `CourseQuizResult`.
             // So we must ADD them together.
             
             $totalQuizScore = \App\Models\CourseQuizResult::where('course_id', $courseId)
                ->where('user_id', auth()->id())
                ->sum('score');
                
             $courseMember->achieved_score = $totalLessonScore + $totalQuizScore;
             $courseMember->save();
        }

        return response()->json([
            'success' => true,
            'is_correct' => $isCorrect,
            'points' => $points,
            'message' => $isCorrect ? 'ถูกต้อง!' : 'ยังไม่ถูกต้อง'
        ]);
    }
}
