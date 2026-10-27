<?php

namespace App\Models;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'email', 'whatsapp', 'level', 'plan', 'goal'])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;
}
