<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 160)->unique();
            $table->string('whatsapp', 30)->nullable();
            $table->string('level', 20);
            $table->string('plan_interest', 20)->nullable();
            $table->text('goal')->nullable();
            $table->string('status', 30)->default('novo');
            $table->string('channel', 20)->index();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable()->index();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('fbclid')->nullable();
            $table->string('referrer')->nullable();
            $table->string('landing_path')->nullable();
            $table->uuid('visitor_id')->nullable()->index();
            $table->timestamp('last_submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
