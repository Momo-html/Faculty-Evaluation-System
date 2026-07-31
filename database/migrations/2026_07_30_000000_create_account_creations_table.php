<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_creations', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->unique();
            $table->string('login_id')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name');
            $table->string('sortable_name')->nullable();
            $table->string('short_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('password')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_creations');
    }
};
