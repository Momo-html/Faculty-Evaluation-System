<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\EvaluationForm;
use App\Models\EvaluationResponse;
use App\Models\Faculty;
use App\Models\SubjectMapping;
use App\Models\User;
use App\Support\FrontendDemoData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        if (! Schema::hasTable('evaluation_forms')) {
            return view('admin.dashboard', FrontendDemoData::for('admin.dashboard'));
        }

        $activeForm = EvaluationForm::query()->where('is_active', true)->latest()->first();
        $totalResponses = EvaluationResponse::query()->whereNotNull('submitted_at')->count();
        $totalExpected = max(SubjectMapping::query()->count() * max(User::query()->where('role', User::ROLE_STUDENT)->where('status', 'active')->count(), 1), 1);
        $participationRate = $activeForm
            ? round((EvaluationResponse::query()->where('evaluation_form_id', $activeForm->id)->whereNotNull('submitted_at')->count() / $totalExpected) * 100, 1)
            : 0;

        $deptData = Department::query()
            ->withCount([
                'users as student_count' => fn ($query) => $query->where('role', User::ROLE_STUDENT),
                'faculty as faculty_count',
            ])
            ->get();

        $daily = EvaluationResponse::query()
            ->selectRaw('DATE(submitted_at) as day, COUNT(*) as total')
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('day')
            ->pluck('total', 'day');

        $dates = collect(range(6, 0))->map(fn (int $daysAgo) => Carbon::today()->subDays($daysAgo));
        $velocityLabels = $dates->map(fn (Carbon $date) => $date->format('D'))->all();
        $velocityData = $dates->map(fn (Carbon $date) => (int) ($daily[$date->toDateString()] ?? 0))->all();
        $dailyAverage = (int) round(collect($velocityData)->avg() ?: 0);

        $facultyReadiness = Faculty::query()
            ->with(['subjectMappings.responses'])
            ->orderBy('faculty_name')
            ->get()
            ->map(function (Faculty $faculty) use ($activeForm): object {
                $mappingIds = $faculty->subjectMappings->pluck('id');
                $received = EvaluationResponse::query()
                    ->whereIn('subject_mapping_id', $mappingIds)
                    ->when($activeForm, fn ($query) => $query->where('evaluation_form_id', $activeForm->id))
                    ->whereNotNull('submitted_at')
                    ->count();
                $expected = max($mappingIds->count(), 1);

                return (object) [
                    'id' => $faculty->id,
                    'faculty_name' => $faculty->faculty_name,
                    'total_received' => $received,
                    'total_expected' => $expected,
                    'rate' => min(100, round(($received / $expected) * 100)),
                ];
            });

        return view('admin.dashboard', [
            'deptData' => $deptData,
            'velocityLabels' => $velocityLabels,
            'velocityData' => $velocityData,
            'activeForm' => $activeForm,
            'lowParticipation' => $facultyReadiness->where('rate', '<', 60)->take(5)->map(fn (object $item): object => (object) [
                'section_name' => $item->faculty_name,
                'rate' => $item->rate,
            ]),
            'totalPopulation' => User::query()->where('role', User::ROLE_STUDENT)->count(),
            'totalFaculty' => Faculty::query()->count(),
            'totalForms' => EvaluationForm::query()->count(),
            'activeForms' => EvaluationForm::query()->where('is_active', true)->count(),
            'participationRate' => $participationRate,
            'totalResponses' => $totalResponses,
            'dailyAverage' => $dailyAverage,
            'daysUntilTarget' => $dailyAverage > 0 ? max(0, (int) ceil(($totalExpected - $totalResponses) / $dailyAverage)) : 'N/A',
            'projectedDate' => $dailyAverage > 0 ? now()->addDays(max(0, (int) ceil(($totalExpected - $totalResponses) / $dailyAverage)))->format('M d, Y') : 'Pending Data',
            'facultyReadiness' => $facultyReadiness,
            'recentActivities' => ActivityLog::query()->with('user')->latest('created_at')->take(5)->get(),
            'recentSubmissions' => EvaluationResponse::query()->with(['user', 'subjectMapping.faculty', 'subjectMapping.subject'])->latest('submitted_at')->take(5)->get(),
        ]);
    }
}
