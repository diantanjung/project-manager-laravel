<?php

namespace App\Http\Requests\Api\V1\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTaskRequest extends FormRequest
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
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', 'nullable', Rule::enum(TaskPriority::class)],
            'projectId' => ['sometimes', 'integer', Rule::exists('projects', 'id')],
            'creatorId' => ['sometimes', 'integer', Rule::exists('users', 'id')],
            'assigneeId' => ['sometimes', 'integer', Rule::exists('users', 'id')],
            'dueFrom' => ['sometimes', 'date'],
            'dueUntil' => ['sometimes', 'date', 'after_or_equal:dueFrom'],
            'hasDescription' => ['sometimes', 'boolean'],
            'sortBy' => ['sometimes', 'string', Rule::in([
                'id',
                'title',
                'status',
                'priority',
                'due_date',
                'position',
                'created_at',
                'updated_at',
            ])],
            'order' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('order')) {
            $this->merge([
                'order' => strtolower((string) $this->input('order')),
            ]);
        }

        if ($this->has('hasDescription')) {
            $hasDescription = $this->input('hasDescription');

            if (is_string($hasDescription) && in_array(strtolower($hasDescription), ['true', 'false'], true)) {
                $this->merge([
                    'hasDescription' => strtolower($hasDescription) === 'true',
                ]);
            }
        }
    }
}
