<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_matches_the_refined_design_table_list(): void
    {
        $tables = [
            'users',
            'departments',
            'sections',
            'faculty',
            'subjects',
            'subject_mappings',
            'student_subjects',
            'csv_import_logs',
            'csv_import_errors',
            'evaluation_forms',
            'form_questions',
            'evaluation_responses',
            'evaluation_answers',
            'evaluation_summaries',
            'category_summaries',
            'response_daily_counts',
            'prediction_results',
            'pdf_reports',
            'report_email_logs',
            'export_logs',
            'activity_logs',
            'login_logs',
            'backup_logs',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_key_rbac_and_evaluation_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'role',
            'student_number',
            'department_id',
            'status',
            'sso_provider',
            'sso_id',
        ]));

        $this->assertTrue(Schema::hasColumns('evaluation_responses', [
            'evaluation_form_id',
            'user_id',
            'subject_mapping_id',
            'overall_score',
            'submitted_at',
        ]));

        $this->assertTrue(Schema::hasColumns('pdf_reports', [
            'evaluation_form_id',
            'faculty_id',
            'generated_by',
            'file_name',
            'file_path',
            'report_status',
            'generated_at',
        ]));
    }
}
