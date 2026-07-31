<?php

namespace App\Services;

use App\Models\AccountCreation;
use App\Models\CourseCreation;
use App\Models\CsvImportError;
use App\Models\CsvImportLog;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Section;
use App\Models\StudentSectionAllocation;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class CsvUserImportService
{
    private const HEADERS = [
        'faculty' => ['employee_id', 'name', 'email', 'department_code', 'password'],
        'student' => ['student_number', 'name', 'email', 'department_code', 'section_name', 'password'],
        'account_creation' => ['user_id', 'login_id', 'first_name', 'last_name', 'full_name', 'sortable_name', 'short_name', 'email', 'status'],
        'course_creation' => ['course_id', 'short_name', 'long_name', 'status', 'term_id'],
        'student_enrollment' => ['course_id', 'section_id', 'user_id', 'status', 'role'],
    ];

    public function import(UploadedFile $file, string $type, ?int $uploadedBy): CsvImportLog
    {
        $log = CsvImportLog::query()->create(['uploaded_by' => $uploadedBy, 'import_type' => $type, 'file_name' => $file->getClientOriginalName(), 'status' => 'processing']);
        $handle = fopen($file->getRealPath(), 'rb');
        if (! $handle) {
            throw new RuntimeException('The CSV file could not be opened.');
        }

        $headers = array_map(fn ($value) => strtolower(trim((string) $value)), fgetcsv($handle) ?: []);
        $detectedType = collect(self::HEADERS)
            ->first(fn (array $requiredHeaders) => array_diff($requiredHeaders, $headers) === []);
        if ($detectedType !== null) {
            $type = array_search($detectedType, self::HEADERS, true);
            $log->update(['import_type' => $type]);
        }
        $missing = array_diff(self::HEADERS[$type], $headers);
        if ($missing) {
            fclose($handle);
            $log->update(['status' => 'failed']);
            throw new RuntimeException('Missing CSV columns: '.implode(', ', $missing));
        }

        $seenEmails = [];
        $seenNumbers = [];
        $rowNumber = 1;
        $success = 0;
        $failed = 0;
        while (($values = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            $values = array_pad($values, count($headers), null);
            $row = array_map(fn ($value) => trim((string) $value), array_combine($headers, array_slice($values, 0, count($headers))));
            try {
                $identity = strtolower(match ($type) {
                    'faculty' => $row['employee_id'],
                    'student' => $row['student_number'],
                    'account_creation' => $row['user_id'],
                    'course_creation' => $row['course_id'],
                    'student_enrollment' => implode('|', [$row['user_id'], $row['course_id'], $row['section_id'], $row['role']]),
                });
                $email = strtolower($row['email'] ?? '');
                if (($email !== '' && isset($seenEmails[$email])) || isset($seenNumbers[$identity])) {
                    throw new RuntimeException('Duplicate email or ID inside this CSV file.');
                }
                if ($email !== '') {
                    $seenEmails[$email] = true;
                }
                $seenNumbers[$identity] = true;
                $this->createRow($row, $type, $uploadedBy);
                $success++;
            } catch (\Throwable $exception) {
                $failed++;
                $message = method_exists($exception, 'errors') ? collect($exception->errors())->flatten()->implode(' ') : $exception->getMessage();
                CsvImportError::query()->create(['csv_import_log_id' => $log->id, 'row_number' => $rowNumber, 'error_message' => $message, 'raw_data' => $row, 'created_at' => now()]);
            }
        }
        fclose($handle);
        $log->update(['total_rows' => $success + $failed, 'successful_rows' => $success, 'failed_rows' => $failed, 'status' => $failed ? ($success ? 'completed_with_errors' : 'failed') : 'completed']);

        return $log->fresh('errors');
    }

    private function createRow(array $row, string $type, ?int $actorId): void
    {
        if ($type === 'student_enrollment') {
            $row['section_id'] = $row['section_id'] === '' ? null : $row['section_id'];
            $row['role'] = strtolower($row['role']);

            Validator::make($row, [
                'course_id' => ['required', 'string', 'max:255'],
                'section_id' => ['nullable', 'string', 'max:255'],
                'user_id' => ['required', 'string', 'max:255'],
                'status' => ['required', 'string', 'max:50'],
                'role' => ['required', 'string', 'max:50'],
            ])->validate();

            StudentEnrollment::query()->updateOrCreate(
                collect($row)->only(['course_id', 'section_id', 'user_id', 'role'])->all(),
                ['status' => $row['status']],
            );

            return;
        }

        if ($type === 'course_creation') {
            Validator::make($row, [
                'course_id' => ['required', 'string', 'max:255', 'unique:course_creations,course_id'],
                'short_name' => ['required', 'string', 'max:255'],
                'long_name' => ['required', 'string', 'max:255'],
                'status' => ['required', 'string', 'max:50'],
                'term_id' => ['required', 'string', 'max:255'],
            ])->validate();

            CourseCreation::query()->create($row);

            return;
        }

        if ($type === 'account_creation') {
            $existingAccount = AccountCreation::query()->where('user_id', $row['user_id'])->first();

            Validator::make($row, [
                'user_id' => ['required', 'string', 'max:255'],
                'login_id' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('account_creations', 'login_id')->ignore($existingAccount?->id),
                ],
                'first_name' => ['nullable', 'string', 'max:255'],
                'last_name' => ['nullable', 'string', 'max:255'],
                'full_name' => ['required', 'string', 'max:255'],
                'sortable_name' => ['nullable', 'string', 'max:255'],
                'short_name' => ['nullable', 'string', 'max:255'],
                'email' => ['nullable', 'email:rfc', 'max:255'],
                'status' => ['required', 'string', 'max:50'],
            ])->validate();

            AccountCreation::query()->updateOrCreate(
                ['user_id' => $row['user_id']],
                collect($row)->except('user_id')->all(),
            );

            return;
        }

        $department = Department::query()->where('code', $row['department_code'])->first();
        $rules = ['name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'], 'department_code' => ['required', Rule::exists('departments', 'code')], 'password' => ['required', 'string', 'min:8']];
        if ($type === 'faculty') {
            $rules['employee_id'] = ['required', 'string', 'max:50', 'unique:faculty,employee_id'];
        } else {
            $rules['student_number'] = ['required', 'string', 'max:50', 'unique:users,student_number'];
            $rules['section_name'] = ['nullable', 'string', 'max:100'];
        }
        Validator::make($row, $rules)->validate();

        DB::transaction(function () use ($row, $type, $department, $actorId) {
            if ($type === 'faculty') {
                if (Faculty::withTrashed()->where('email', $row['email'])->exists()) {
                    throw new RuntimeException('Faculty email already exists.');
                }
                $user = User::query()->create(['name' => $row['name'], 'email' => $row['email'], 'password' => $row['password'], 'role' => User::ROLE_FACULTY, 'department_id' => $department->id, 'status' => 'active']);
                Faculty::query()->create(['employee_id' => $row['employee_id'], 'user_id' => $user->id, 'faculty_name' => $row['name'], 'email' => $row['email'], 'department_id' => $department->id, 'status' => 'active']);

                return;
            }

            $section = null;
            if ($row['section_name'] !== '') {
                $section = Section::query()->where('section_name', $row['section_name'])->where('department_id', $department->id)->first();
                if (! $section) {
                    throw new RuntimeException('Section does not exist in the supplied department.');
                }
            }
            $student = User::query()->create(['student_number' => $row['student_number'], 'name' => $row['name'], 'email' => $row['email'], 'password' => $row['password'], 'role' => User::ROLE_STUDENT, 'department_id' => $department->id, 'section_id' => $section?->id, 'status' => 'active']);
            if ($section) {
                StudentSectionAllocation::query()->create(['user_id' => $student->id, 'section_id' => $section->id, 'changed_by' => $actorId, 'assigned_at' => now(), 'reason' => 'CSV import']);
            }
        });
    }
}
