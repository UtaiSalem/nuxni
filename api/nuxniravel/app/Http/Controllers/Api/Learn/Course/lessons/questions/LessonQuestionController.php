<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons\questions;

use App\Models\Lesson;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Learn\Course\questions\QuestionResource;

class LessonQuestionController extends \App\Http\Controllers\Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Lesson $lesson)
    {
        $questions = $lesson->questions()->with(['options', 'images'])->get();
        return QuestionResource::collection($questions);
    }

    public function store(Lesson $lesson, Request $request)
    {
        $question = $lesson->questions()->create([
            'course_id' => $lesson->course_id,
            'user_id' => auth()->id(),
            'text' => $request->text,
            'points' => $request->points,
            'type' => $request->type ?? 'multiple_choice',
            'explanation' => $request->explanation,
        ]);

        $lesson->course->increment('total_score', $request->points);

        if ($request->filled('options')) {
            foreach ($request->options as $index => $optionData) {
                // If options are sent as JSON string in FormData, decode might be needed, 
                // but Laravel usually handles array inputs if named correctly.
                // Assuming request->options is an array
                if (is_array($optionData)) {
                     $newOption = $question->options()->create([
                        'text' => $optionData['text'] ?? '',
                        'is_correct' => filter_var($optionData['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'explanation' => $optionData['explanation'] ?? null,
                    ]);

                    if ($request->hasFile("options.$index.image")) {
                        $image = $request->file("options.$index.image");
                        $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                        Storage::disk('public')->putFileAs('images/courses/lessons/quizzes/options', $image, $fileName);
                        $newOption->images()->create([
                            'filename' => $fileName
                        ]);
                    }
                }
            }
        }

        if($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                $image_url = Storage::disk('public')->putFileAs('images/courses/quizzes/questions', $image, $fileName);
                $question->images()->create([
                    'filename' => $fileName
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'question' => new QuestionResource($question->load('options', 'images'))
        ]);
    }

    public function show(Lesson $lesson, Question $question)
    {
        return new QuestionResource($question->load('options', 'images'));
    }

    public function update(Request $request, Lesson $lesson, Question $question)
    {
        $question->update([
            'text' => $request->text,
            'points' => $request->points,
            'explanation' => $request->explanation,
        ]);

        // Sync Options
        // Strategy: We can delete all and recreate, or update existing.
        // For simplicity in this iteration, if "options" is present, we replace them.
        // BUT existing ID tracking is better.
        
        if ($request->filled('options')) {
            // Keep track of processed IDs
            $processedIds = [];
            
            foreach ($request->options as $index => $optionData) {
                if (isset($optionData['id']) && $optionData['id']) {
                    // Update
                    $option = $question->options()->find($optionData['id']);
                    if ($option) {
                        $option->update([
                            'text' => $optionData['text'],
                            'is_correct' => filter_var($optionData['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'explanation' => $optionData['explanation'] ?? null,
                        ]);
                        $processedIds[] = $option->id;

                        // Check for new image
                        if ($request->hasFile("options.$index.image")) {
                            // Delete old images? Optional, but good practice if single image per option
                            // $option->images()->delete(); 
                            
                            $image = $request->file("options.$index.image");
                            $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                            Storage::disk('public')->putFileAs('images/courses/lessons/quizzes/options', $image, $fileName);
                            $option->images()->create([
                                'filename' => $fileName
                            ]);
                        }
                    }
                } else {
                    // Create
                    $newOption = $question->options()->create([
                        'text' => $optionData['text'] ?? '',
                        'is_correct' => filter_var($optionData['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'explanation' => $optionData['explanation'] ?? null,
                    ]);
                    $processedIds[] = $newOption->id;

                    if ($request->hasFile("options.$index.image")) {
                        $image = $request->file("options.$index.image");
                        $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                        Storage::disk('public')->putFileAs('images/courses/lessons/quizzes/options', $image, $fileName);
                        $newOption->images()->create([
                            'filename' => $fileName
                        ]);
                    }
                }
            }
            
            // Delete removed options
            $question->options()->whereNotIn('id', $processedIds)->delete();
        }

        // Handle new images
        if($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                $image_url = Storage::disk('public')->putFileAs('images/courses/quizzes/questions', $image, $fileName);
                $question->images()->create([
                    'filename' => $fileName
                ]);
            }
        }
        
        // Handle deleted images
        if ($request->filled('deleted_images')) {
             foreach ($request->deleted_images as $imgId) {
                 $img = $question->images()->find($imgId);
                 if ($img) {
                     Storage::disk('public')->delete($img->image_url);
                     $img->delete();
                 }
             }
        }

        return response()->json([
            'success' => true,
            'question' => new QuestionResource($question->load('options', 'images'))
        ]);
    }

    public function destroy(Lesson $lesson, Question $question)
    {
        // Delete images
        foreach ($question->images as $image) {
            Storage::disk('public')->delete($image->image_url);
        }
        $question->images()->delete();

        // Delete options and their images
        foreach ($question->options as $option) {
            foreach ($option->images as $image) {
                Storage::disk('public')->delete($image->image_url);
            }
            $option->images()->delete();
        }
        $question->options()->delete();
        
        $lesson->course->decrement('total_score', $question->points);
        $question->delete();
        
        return response()->json(['success' => true]);
    }
}
