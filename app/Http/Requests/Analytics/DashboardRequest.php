<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

abstract class DashboardRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                'regex:/\S/',
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
                'regex:/\S/',
            ],
            'is_default' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * @return array{
     *     name: string,
     *     description: string|null,
     *     is_default: bool
     * }
     */
    public function toDashboardAttributes(): array
    {
        $description = $this->validated('description');

        $normalizedDescription = is_string($description)
            ? trim($description)
            : null;

        return [
            'name' => trim(
                (string) $this->validated('name'),
            ),
            'description' => $normalizedDescription === ''
                ? null
                : $normalizedDescription,
            'is_default' => $this->boolean('is_default'),
        ];
    }
}
