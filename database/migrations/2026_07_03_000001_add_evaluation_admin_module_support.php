<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('evaluation_forms', 'description')) {
            Schema::table('evaluation_forms', function (Blueprint $table): void {
                $table->string('description')->nullable()->after('title');
            });
        }

        if (! Schema::hasColumn('evaluation_forms', 'deleted_at')) {
            Schema::table('evaluation_forms', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('form_questions', 'options')) {
            Schema::table('form_questions', function (Blueprint $table): void {
                $table->json('options')->nullable()->after('question_type');
            });
        }

        if (! Schema::hasColumn('form_questions', 'deleted_at')) {
            Schema::table('form_questions', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }

        $activityColumns = [
            'module' => fn (Blueprint $table) => $table->string('module')->nullable()->after('action')->index(),
            'record_type' => fn (Blueprint $table) => $table->string('record_type')->nullable()->after('module'),
            'record_id' => fn (Blueprint $table) => $table->unsignedBigInteger('record_id')->nullable()->after('record_type'),
            'old_values' => fn (Blueprint $table) => $table->json('old_values')->nullable()->after('description'),
            'new_values' => fn (Blueprint $table) => $table->json('new_values')->nullable()->after('old_values'),
        ];

        foreach ($activityColumns as $column => $definition) {
            if (! Schema::hasColumn('activity_logs', $column)) {
                Schema::table('activity_logs', $definition);
            }
        }

        if (! Schema::hasTable('settings')) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');

        $activityColumns = ['module', 'record_type', 'record_id', 'old_values', 'new_values'];
        $existingActivityColumns = array_filter(
            $activityColumns,
            fn (string $column): bool => Schema::hasColumn('activity_logs', $column),
        );

        if ($existingActivityColumns !== []) {
            Schema::table('activity_logs', function (Blueprint $table) use ($existingActivityColumns): void {
                $table->dropColumn($existingActivityColumns);
            });
        }

        if (Schema::hasColumn('form_questions', 'deleted_at')) {
            Schema::table('form_questions', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('form_questions', 'options')) {
            Schema::table('form_questions', function (Blueprint $table): void {
                $table->dropColumn('options');
            });
        }

        if (Schema::hasColumn('evaluation_forms', 'deleted_at')) {
            Schema::table('evaluation_forms', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('evaluation_forms', 'description')) {
            Schema::table('evaluation_forms', function (Blueprint $table): void {
                $table->dropColumn('description');
            });
        }
    }
};
