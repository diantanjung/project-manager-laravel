<?php

namespace App\Http\Requests\Api\V1\Comments;

use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
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
            'content' => ['required', 'string'],
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
            'author_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $task = $this->route('task');

        if ($task instanceof Task) {
            $this->merge([
                'task_id' => (int) $task->getKey(),
            ]);
        } elseif (is_numeric($task)) {
            $this->merge([
                'task_id' => (int) $task,
            ]);
        }
    }
}
