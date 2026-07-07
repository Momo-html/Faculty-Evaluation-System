<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('department_name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->after('password')->index();
            $table->string('student_number')->nullable()->unique()->after('role');
            $table->foreignId('department_id')->nullable()->after('student_number')->constrained()->nullOnDelete();
            $table->string('status')->default('active')->after('department_id')->index();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_name');
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('year_level')->nullable();
            $table->timestamps();
            $table->unique(['section_name', 'department_id', 'year_level']);
        });

        Schema::create('faculty', function (Blueprint $table) {
            $table->id();
            $table->string('faculty_name');
            $table->string('email')->nullable()->unique();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('subject_code')->unique();
            $table->string('subject_name');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('units')->default(0);
            $table->timestamps();
        });

        Schema::create('subject_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculty')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('school_year');
            $table->string('semester');
            $table->timestamps();
            $table->unique(['faculty_id', 'subject_id', 'section_id', 'school_year', 'semester'], 'subject_mapping_unique');
        });

        Schema::create('student_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_mapping_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'subject_mapping_id']);
        });

        Schema::create('csv_import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('import_type');
            $table->string('file_name');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('successful_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->string('status')->default('pending')->index();
            $table->timestamps();
        });

        Schema::create('csv_import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('csv_import_log_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->text('error_message');
            $table->json('raw_data')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('evaluation_forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('school_year');
            $table->string('semester');
            $table->timestamp('open_at')->nullable();
            $table->timestamp('close_at')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('form_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_form_id')->constrained()->cascadeOnDelete();
            $table->text('question_text');
            $table->string('question_type');
            $table->string('category')->nullable()->index();
            $table->unsignedInteger('order_number')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });

        Schema::create('evaluation_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_mapping_id')->constrained()->cascadeOnDelete();
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['evaluation_form_id', 'user_id', 'subject_mapping_id'], 'one_response_per_student_mapping_form');
        });

        Schema::create('evaluation_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_question_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating_value')->nullable();
            $table->text('text_answer')->nullable();
            $table->timestamps();
            $table->unique(['evaluation_response_id', 'form_question_id'], 'one_answer_per_question');
        });

        Schema::create('evaluation_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_mapping_id')->constrained()->cascadeOnDelete();
            $table->decimal('mean_score', 5, 2)->nullable();
            $table->unsignedInteger('respondent_count')->default(0);
            $table->unsignedInteger('total_students')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0);
            $table->string('adjectival_rating')->nullable();
            $table->timestamps();
            $table->unique(['evaluation_form_id', 'subject_mapping_id']);
        });

        Schema::create('category_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_mapping_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->decimal('mean_score', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['evaluation_form_id', 'subject_mapping_id', 'category'], 'category_summary_unique');
        });

        Schema::create('response_daily_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_form_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('response_count')->default(0);
            $table->unsignedInteger('cumulative_count')->default(0);
            $table->timestamps();
            $table->unique(['evaluation_form_id', 'date']);
        });

        Schema::create('prediction_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_form_id')->constrained()->cascadeOnDelete();
            $table->date('predicted_completion_date')->nullable();
            $table->decimal('current_completion_rate', 5, 2)->default(0);
            $table->decimal('average_daily_responses', 8, 2)->default(0);
            $table->unsignedInteger('remaining_responses')->default(0);
            $table->string('prediction_method')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pdf_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained('faculty')->cascadeOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('report_status')->default('generated')->index();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('report_email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pdf_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained('faculty')->cascadeOnDelete();
            $table->string('recipient_email');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('evaluation_form_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_name');
            $table->string('file_type');
            $table->string('export_type');
            $table->timestamp('exported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role')->nullable()->index();
            $table->timestamp('login_at')->nullable();
            $table->timestamp('logout_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('status')->index();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('backup_file_name');
            $table->string('backup_file_path');
            $table->string('backup_type');
            $table->string('status')->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('export_logs');
        Schema::dropIfExists('report_email_logs');
        Schema::dropIfExists('pdf_reports');
        Schema::dropIfExists('prediction_results');
        Schema::dropIfExists('response_daily_counts');
        Schema::dropIfExists('category_summaries');
        Schema::dropIfExists('evaluation_summaries');
        Schema::dropIfExists('evaluation_answers');
        Schema::dropIfExists('evaluation_responses');
        Schema::dropIfExists('form_questions');
        Schema::dropIfExists('evaluation_forms');
        Schema::dropIfExists('csv_import_errors');
        Schema::dropIfExists('csv_import_logs');
        Schema::dropIfExists('student_subjects');
        Schema::dropIfExists('subject_mappings');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('faculty');
        Schema::dropIfExists('sections');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn([
                'role',
                'student_number',
                'department_id',
                'status',
            ]);
        });

        Schema::dropIfExists('departments');
    }
};
