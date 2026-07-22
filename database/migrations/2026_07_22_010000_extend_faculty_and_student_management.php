<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faculty', function (Blueprint $table) {
            $table->string('employee_id')->nullable()->unique()->after('id');
            $table->foreignId('user_id')->nullable()->unique()->after('employee_id')->constrained()->nullOnDelete();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->softDeletes();
        });

        Schema::create('student_section_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('ended_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_section_allocations');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('section_id');
            $table->dropSoftDeletes();
        });

        Schema::table('faculty', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropUnique(['employee_id']);
            $table->dropColumn('employee_id');
            $table->dropSoftDeletes();
        });
    }
};
