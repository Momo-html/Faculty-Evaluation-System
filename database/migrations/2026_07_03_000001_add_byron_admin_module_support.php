<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_forms', function (Blueprint $table): void {
            $table->string('description')->nullable()->after('title');
            $table->softDeletes();
        });

        Schema::table('form_questions', function (Blueprint $table): void {
            $table->json('options')->nullable()->after('question_type');
            $table->softDeletes();
        });

        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->string('module')->nullable()->after('action')->index();
            $table->string('record_type')->nullable()->after('module');
            $table->unsignedBigInteger('record_id')->nullable()->after('record_type');
            $table->json('old_values')->nullable()->after('description');
            $table->json('new_values')->nullable()->after('old_values');
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general')->index();
            $table->text('description')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');

        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->dropColumn(['module', 'record_type', 'record_id', 'old_values', 'new_values']);
        });

        Schema::table('form_questions', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropColumn('options');
        });

        Schema::table('evaluation_forms', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropColumn('description');
        });
    }
};
