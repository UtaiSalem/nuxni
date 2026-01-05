<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons\comments;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;
use App\Models\LessonComment;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Learn\Course\lessons\LessonCommentResource;

class LessonCommentController extends Controller
{
    //get all top-level comments resource collections for a lesson by lesson id and paginate them
    public function index(Lesson $lesson)
    {
        return LessonCommentResource::collection(
            $lesson->comments()
                ->whereNull('parent_id')
                ->latest()
                ->paginate(10)
        );
    }

    public function store(Lesson $lesson, Request $request)
    {
        $validatedData = $request->validate([
            'content' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'parent_id' => 'nullable|exists:lesson_comments,id',
        ]);
    
        $newComment = $lesson->comments()->create([
            'user_id' => auth()->id(),
            'content' => $validatedData['content'],
            'parent_id' => $validatedData['parent_id'] ?? null,
        ]);
    
        if($request->hasFile('images')) {
            $lesson_comment_images = $request->file('images');
            foreach ($lesson_comment_images as $image) {
                $fileName = $lesson->id . uniqid() . '.' . $image->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/courses/lessons/comments', $image, $fileName);
                $newComment->images()->create([
                    'lesson_id' => $lesson->id,
                    'filename' => $fileName,
                ]);
            }
        }
    
        $lesson->increment('comment_count', 1);
    
        return response()->json([
            'success' => true,
            'comment' => new LessonCommentResource(LessonComment::find($newComment->id)),
        ]);
    }

    // destroy a comment by comment id
    public function destroy(Lesson $lesson, LessonComment $comment)
    {
        // Delete images if any
        $images = $comment->lessonCommentImages;
        if ($images && $images->count() > 0) {
            foreach ($images as $image) {
                Storage::disk('public')->delete('images/courses/lessons/comments/'. $image->filename);
                $image->delete();
            }
        }
        
        // Detach likes and dislikes
        $comment->likeComment()->detach();
        $comment->dislikeComment()->detach();

        // Delete the comment (cascade delete for replies is handled in Model)
        $comment->delete();
        
        // Decrement comment count
        $lesson->decrement('comment_count', 1);

        return response()->json([
           'success' => true,
           'message' => 'ลบความคิดเห็นสำเร็จ',
        ]);
    }

}
