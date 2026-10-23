<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_entries', function (Blueprint $table) {
            $table->id();
            $table->string('date', 10)->index();
            $table->string('direction', 3);
            $table->string('category', 30);
            $table->string('description', 160);
            $table->unsignedInteger('amount_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_entries');
    }
};
