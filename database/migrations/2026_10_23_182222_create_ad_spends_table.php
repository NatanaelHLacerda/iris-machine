<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_spends', function (Blueprint $table) {
            $table->id();
            $table->string('date', 10);
            $table->string('platform', 20)->default('meta');
            $table->string('campaign', 200);
            $table->string('adset', 200)->nullable();
            $table->string('ad', 200)->nullable();
            $table->string('campaign_key', 200);
            $table->string('adset_key', 200)->default('');
            $table->string('ad_key', 200)->default('');
            $table->unsignedInteger('spend_cents');
            $table->unsignedInteger('impressions')->nullable();
            $table->unsignedInteger('link_clicks')->nullable();
            $table->string('source', 10);
            $table->timestamps();

            $table->unique(['platform', 'date', 'campaign_key', 'adset_key', 'ad_key']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_spends');
    }
};
