<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuestionImage extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    protected $appends = ['url', 'full_url'];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        if (isset($this->attributes['image_url']) && $this->attributes['image_url']) {
             return asset('storage/' . $this->attributes['image_url']);
        }

        $folder = 'images/courses/lessons/questions';
        if ($this->imageable_type === 'App\Models\QuestionOption' || $this->imageable_type === 'QuestionOption') {
            $folder = 'images/courses/lessons/questions/options';
        }

        return asset("storage/{$folder}/" . $this->filename);
    }

    public function getFullUrlAttribute(): string
    {
        return $this->url;
    }
}
