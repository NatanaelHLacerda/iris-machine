<?php

namespace App\Models;

use Database\Factories\LeadSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lead_id', 'payload', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
    'referrer', 'landing_path', 'ip_hash', 'user_agent',
])]
class LeadSubmission extends Model
{
    /** @use HasFactory<LeadSubmissionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
