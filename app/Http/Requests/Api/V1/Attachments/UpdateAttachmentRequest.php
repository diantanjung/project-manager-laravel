<?php

namespace App\Http\Requests\Api\V1\Attachments;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAttachmentRequest extends FormRequest
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
            'file_name' => ['sometimes', 'required', 'string', 'max:255'],
            'file_url' => ['sometimes', 'required', 'string', 'url', 'max:2048'],
            'file_size' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'mime_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'task_id' => ['sometimes', 'required', 'integer', 'exists:tasks,id'],
            'uploader_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
        ];
    }
}
