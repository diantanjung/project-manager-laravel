<?php

namespace App\Http\Requests\Api\V1\Attachments;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
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
            'file_name' => ['required', 'string', 'max:255'],
            'file_url' => ['required', 'string', 'url', 'max:2048'],
            'file_size' => ['nullable', 'integer', 'min:0'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
            'uploader_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $task = $this->route('task');

        if ($task !== null) {
            $this->merge([
                'task_id' => (int) (is_object($task) ? $task->getKey() : $task),
            ]);
        }
    }
}
