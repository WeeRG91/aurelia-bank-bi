<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Contracts\Validation\ValidationRule;

abstract class SavedReportRequest extends ReportPreviewRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
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
            ...parent::rules(),
        ];
    }
}
