<?php

namespace App\Http\Requests\Analytics;

use App\Analytics\Exports\ExportFormat;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class ExportSavedReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $savedReport = $this->route('savedReport');

        return $user instanceof User
            && $savedReport instanceof SavedReport
            && Gate::forUser($user)->allows(
                'export',
                $savedReport,
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
            'format' => [
                'required',
                'string',
                Rule::enum(ExportFormat::class),
            ],
        ];
    }

    public function exportFormat(): ExportFormat
    {
        return ExportFormat::from(
            (string) $this->validated('format'),
        );
    }
}
