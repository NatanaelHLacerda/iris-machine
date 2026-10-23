<?php

namespace App\Http\Requests\Crm;

use App\Enums\LeadStatus;
use App\Enums\LostReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadStatusRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(LeadStatus::class)],
            'lost_reason' => ['nullable', 'required_if:status,'.LeadStatus::Perdido->value, Rule::enum(LostReason::class)],
            'note' => ['nullable', 'string', 'max:255'],
            'meeting_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['lost_reason.required_if' => 'Informe o motivo da perda.'];
    }
}
