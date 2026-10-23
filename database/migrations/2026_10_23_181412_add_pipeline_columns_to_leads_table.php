<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('status_changed_at')->nullable();
            $table->string('next_action', 160)->nullable();
            $table->timestamp('next_action_at')->nullable();
            $table->timestamp('meeting_at')->nullable();
            $table->string('lost_reason', 30)->nullable();
            $table->string('lost_note')->nullable();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['status_changed_at', 'next_action', 'next_action_at', 'meeting_at', 'lost_reason', 'lost_note']);
        });
    }
};
