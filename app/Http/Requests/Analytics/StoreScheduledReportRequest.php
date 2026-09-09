<?php

namespace App\Http\Requests\Analytics;

use App\Analytics\Exports\ExportFormat;
use App\Analytics\Scheduling\ReportScheduleFrequency;
use App\Models\SavedReport;
use App\Models\User;
use DateInvalidTimeZoneException;
use DateTimeZone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreScheduledReportRequest extends FormRequest
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
                'schedule',
                $savedReport
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
                'regex:/\S/u',
            ],
            'format' => [
                'required',
                Rule::enum(ExportFormat::class),
            ],
            'frequency' => [
                'required',
                Rule::enum(ReportScheduleFrequency::class),
            ],
            'run_time' => [
                'required',
                'date_format:H:i',
            ],
            'timezone' => [
                'required',
                'string',
                'max:64',
                Rule::in(DateTimeZone::listIdentifiers()),
            ],
            'weekday' => [
                'nullable',
                'integer',
                'between:1,7',
                'required_if:frequency,weekly',
                'prohibited_unless:frequency,weekly',
            ],
            'day_of_month' => [
                'nullable',
                'integer',
                'between:1,28',
                'required_if:frequency,monthly',
                'prohibited_unless:frequency,monthly',
            ],
        ];
    }

    public function exportFormat(): ExportFormat
    {
        return ExportFormat::from(
            (string) $this->validated('format'),
        );
    }

    public function frequency(): ReportScheduleFrequency
    {
        return ReportScheduleFrequency::from(
            (string) $this->validated('frequency'),
        );
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    public function timezone(): DateTimeZone
    {
        return new DateTimeZone(
            (string) $this->validated('timezone'),
        );
    }

    public function weekday(): ?int
    {
        $weekday = $this->validated('weekday');

        return $weekday === null
            ? null
            : (int) $weekday;
    }

    public function dayOfMonth(): ?int
    {
        $day = $this->validated('day_of_month');

        return $day === null
            ? null
            : (int) $day;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name'))
                ? trim($this->input('name'))
                : $this->input('name'),
            'weekday' => $this->input('weekday') === ''
                ? null
                : $this->input('weekday'),
            'day_of_month' => $this->input('day_of_month') === ''
                ? null
                : $this->input('day_of_month'),
        ]);
    }
}
