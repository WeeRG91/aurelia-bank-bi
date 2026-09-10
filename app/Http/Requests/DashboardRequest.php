<?php

namespace App\Http\Requests;

use App\Analytics\Dashboards\DashboardPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'period' => [
                'nullable',
                Rule::enum(DashboardPeriod::class),
            ],
        ];
    }

    public function period(): DashboardPeriod
    {
        $value = $this->validated('period');

        return is_string($value)
            ? DashboardPeriod::from($value)
            : DashboardPeriod::default();
    }
}
