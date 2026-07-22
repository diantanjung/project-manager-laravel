<?php

namespace App\Http\Requests\Api\V1\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', 'nullable', Rule::enum(TaskPriority::class)],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'creator_id' => ['required', 'integer', 'exists:users,id'],
            'assignee_id' => ['required', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'position' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
