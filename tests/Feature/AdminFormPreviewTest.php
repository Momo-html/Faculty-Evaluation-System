<?php

namespace Tests\Feature;

use App\Models\EvaluationForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFormPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_preview_saved_form_without_student_submission_route(): void
    {
        $admin = User::factory()->admin()->create();
        $form = $this->createPreviewForm($admin);

        $response = $this->actingAs($admin)->get(route('admin.forms.preview-student', $form));

        $response
            ->assertOk()
            ->assertSee('Student View Preview')
            ->assertSee('Preview Mode')
            ->assertSee('2026-2027')
            ->assertSee('Teaching')
            ->assertSee('Explains lessons clearly')
            ->assertSee('Select the strongest teaching area')
            ->assertSee('Share one improvement suggestion')
            ->assertSee('Submit Disabled in Preview Mode')
            ->assertDontSee(route('eval.submit'), false);
    }

    public function test_student_and_superadmin_cannot_access_admin_preview_route(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $superadmin = User::factory()->superadmin()->create();
        $form = $this->createPreviewForm($admin);

        $this->actingAs($student)
            ->get(route('admin.forms.preview-student', $form))
            ->assertForbidden();

        $this->actingAs($superadmin)
            ->get(route('admin.forms.preview-student', $form))
            ->assertForbidden();
    }

    private function createPreviewForm(User $admin): EvaluationForm
    {
        $form = EvaluationForm::query()->create([
            'title' => 'Faculty Evaluation Preview',
            'description' => 'Preview test form',
            'school_year' => '2026-2027',
            'semester' => '1st Semester',
            'open_at' => now()->addWeek(),
            'close_at' => now()->addWeeks(2),
            'is_active' => false,
            'created_by' => $admin->id,
        ]);

        $form->questions()->createMany([
            [
                'question_text' => 'Explains lessons clearly',
                'question_type' => 'rating',
                'category' => 'Teaching',
                'options' => ['scale_min' => 1, 'scale_max' => 5],
                'order_number' => 1,
                'is_required' => true,
            ],
            [
                'question_text' => 'Select the strongest teaching area',
                'question_type' => 'multiple_choice',
                'category' => 'Teaching',
                'options' => ['Clarity', 'Engagement', 'Fairness'],
                'order_number' => 2,
                'is_required' => true,
            ],
            [
                'question_text' => 'Share one improvement suggestion',
                'question_type' => 'text',
                'category' => 'Comments',
                'options' => [],
                'order_number' => 3,
                'is_required' => false,
            ],
        ]);

        return $form;
    }
}
