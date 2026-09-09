<?php

namespace App\Http\Requests\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSource;
use App\Analytics\Datasets\DatasetKey;
use App\Models\AnalyticsAuditEvent;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class AuditEventIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && Gate::forUser($user)->allows(
                'viewAny',
                AnalyticsAuditEvent::class,
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
            'action' => [
                'nullable',
                Rule::enum(AuditAction::class),
            ],
            'outcome' => [
                'nullable',
                Rule::enum(AuditOutcome::class),
            ],
            'source' => [
                'nullable',
                Rule::enum(AuditSource::class),
            ],
            'dataset' => [
                'nullable',
                Rule::enum(DatasetKey::class),
            ],
            'actor_employee_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'from' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:from',
            ],
        ];
    }
}
