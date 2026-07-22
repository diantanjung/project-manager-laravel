<?php

namespace App\Http\Requests\Api\V1\Comments;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['sometimes', 'required', 'string'],
            'task_id' => ['sometimes', 'required', 'integer', 'exists:tasks,id'],
            'author_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
        ];
    }
}
