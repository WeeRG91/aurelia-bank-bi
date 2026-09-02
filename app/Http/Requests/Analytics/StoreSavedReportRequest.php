<?php

namespace App\Http\Requests\Analytics;

use App\Models\SavedReport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Gate;

final class StoreSavedReportRequest extends ReportPreviewRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return parent::authorize()
            && Gate::forUser($this->user())
                ->allows(
                    'create',
                    SavedReport::class
                );
    }

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
