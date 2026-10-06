<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ArticleUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('article'));
    }

    public function rules(): array
    {
        $articleId = $this->route('article')->id;

        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:articles,slug,' . $articleId . '|regex:/^[a-z0-9\-]+$/',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string|min:50',
            'category_id' => 'required|exists:categories,id',
            'is_breaking' => 'nullable|boolean',
            'is_advertorial' => 'nullable|boolean',
            'scheduled_at' => 'nullable|date_format:Y-m-d H:i|after:now',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'og_image_id' => 'nullable|exists:library_media,id',
            'noindex' => 'nullable|boolean',
            'nofollow' => 'nullable|boolean',
        ];
    }
}
