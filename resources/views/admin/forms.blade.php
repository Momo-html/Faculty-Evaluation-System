@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/forms.css') }}">
@endpush

@section('content')
    <div id="forms" class="builder-page">
        <section class="builder-hero">
            <div>
                <p class="builder-eyebrow">Evaluation Engine</p>
                <h1>Evaluation Form Builder</h1>
                <p class="builder-subtitle">Create active evaluation forms, manage dynamic questions, and control what students can answer.</p>
            </div>
            <div class="builder-hero-actions">
                <button class="builder-btn builder-btn-primary" type="button" onclick="showCreateForm()">Create New Form</button>
            </div>
        </section>

        @if(session('success'))
            <div class="builder-alert builder-alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="builder-alert builder-alert-danger">{{ $errors->first() }}</div>
        @endif

        <section class="builder-stats">
            <article class="builder-stat">
                <span class="builder-stat-label">Total Forms</span>
                <strong>{{ $builderStats['total'] ?? 0 }}</strong>
                <small>All saved evaluation forms</small>
            </article>
            <article class="builder-stat">
                <span class="builder-stat-label">Active Forms</span>
                <strong>{{ $builderStats['active'] ?? 0 }}</strong>
                <small>Visible during schedule</small>
            </article>
            <article class="builder-stat">
                <span class="builder-stat-label">Draft / Closed</span>
                <strong>{{ $builderStats['closed'] ?? 0 }}</strong>
                <small>Inactive or ended forms</small>
            </article>
            <article class="builder-stat">
                <span class="builder-stat-label">Total Questions</span>
                <strong>{{ $builderStats['questions'] ?? 0 }}</strong>
                <small>Questions across forms</small>
            </article>
        </section>

        <section class="builder-panel" id="tableSection">
            <div class="builder-panel-header">
                <div>
                    <h2>Evaluation Forms</h2>
                    <p>Review schedules, status, question count, and response activity.</p>
                </div>
            </div>

            <div class="builder-table-wrap">
                <table class="builder-table">
                    <thead>
                        <tr>
                            <th>Form</th>
                            <th>School Year</th>
                            <th>Semester</th>
                            <th>Schedule</th>
                            <th>Status</th>
                            <th>Questions</th>
                            <th>Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($allForms as $f)
                            @php
                                $status = $f->trashed()
                                    ? 'Archived'
                                    : ($f->is_active
                                        ? (optional($f->open_at)->isFuture() ? 'Scheduled' : (optional($f->close_at)->isPast() ? 'Closed' : 'Active'))
                                        : 'Draft');
                                $statusClass = strtolower($status);
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $f->title }}</strong>
                                    <span>{{ $f->description ?: 'No description provided' }}</span>
                                </td>
                                <td>{{ $f->school_year }}</td>
                                <td>{{ $f->semester }}</td>
                                <td>
                                    <span>Open: {{ $f->open_at ? $f->open_at->format('M d, Y h:i A') : 'Not set' }}</span>
                                    <span>Close: {{ $f->close_at ? $f->close_at->format('M d, Y h:i A') : 'Not set' }}</span>
                                </td>
                                <td><span class="builder-badge {{ $statusClass }}">{{ $status }}</span></td>
                                <td>{{ $f->questions_count }} questions<br><span>{{ $f->responses_count }} responses</span></td>
                                <td>{{ $f->updated_at?->format('M d, Y') }}</td>
                                <td>
                                    <div class="builder-actions">
                                        <button class="builder-btn builder-btn-small" type="button" onclick="loadFormForEdit({{ $f->id }})">Edit Builder</button>
                                        <a class="builder-btn builder-btn-small builder-btn-secondary" href="{{ route('admin.forms.preview-student', $f) }}">Preview</a>
                                        <button class="builder-btn builder-btn-small {{ $f->is_active ? 'builder-btn-warning' : 'builder-btn-secondary' }}" type="button" onclick="toggleFormStatus({{ $f->id }})">
                                            {{ $f->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                        <button class="builder-btn builder-btn-small builder-btn-danger" type="button" onclick="deleteForm({{ $f->id }})">Archive</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="builder-empty">
                                        <strong>No evaluation forms yet</strong>
                                        <p>Create your first form, add questions, and activate it for students.</p>
                                        <button class="builder-btn builder-btn-primary" type="button" onclick="showCreateForm()">Create Form</button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="builder-pagination">
                {{ $allForms->links() }}
            </div>
        </section>

        <section id="builderSection" class="builder-workspace" hidden>
            <div class="builder-workspace-header">
                <div>
                    <p class="builder-eyebrow">Builder Workspace</p>
                    <h2 id="builderTitle">Create Evaluation Form</h2>
                </div>
                <div class="builder-toolbar">
                    <button type="button" class="builder-btn builder-btn-secondary" onclick="previewStudentView()">Preview Student View</button>
                    <button id="deleteBtn" type="button" class="builder-btn builder-btn-danger" onclick="deleteForm()" hidden>Archive Form</button>
                    <button type="button" class="builder-btn builder-btn-secondary" onclick="closeBuilder()">Cancel</button>
                </div>
            </div>

            <input type="hidden" id="currentFormId">

            <div class="builder-grid">
                <div class="builder-main">
                    <article class="builder-card">
                        <div class="builder-card-heading">
                            <h3>Form Details</h3>
                            <p>These dates control when students can see and submit this evaluation.</p>
                        </div>
                        <div class="builder-form-grid">
                            <label>
                                <span>Form Title</span>
                                <input type="text" id="title" placeholder="Faculty Evaluation Form">
                            </label>
                            <label>
                                <span>School Year</span>
                                <input type="text" id="sy" placeholder="2025-2026" required>
                            </label>
                            <label>
                                <span>Semester</span>
                                <select id="sem" required>
                                    <option value="1st Semester">1st Semester</option>
                                    <option value="2nd Semester">2nd Semester</option>
                                    <option value="Summer">Summer</option>
                                </select>
                            </label>
                            <label>
                                <span>Open Date and Time</span>
                                <input type="datetime-local" id="openAt" required>
                            </label>
                            <label>
                                <span>Close Date and Time</span>
                                <input type="datetime-local" id="closeAt" required>
                            </label>
                            <label class="builder-toggle-row">
                                <input type="checkbox" id="isActive" checked>
                                <span class="builder-toggle-visual" aria-hidden="true"></span>
                                <span>Active and visible during schedule</span>
                            </label>
                            <label class="builder-full">
                                <span>Description</span>
                                <textarea id="description" placeholder="Optional instructions for this evaluation period"></textarea>
                            </label>
                        </div>
                    </article>

                    <article class="builder-card">
                        <div class="builder-card-heading split">
                            <div>
                                <h3>Question Builder</h3>
                                <p>Add rating, multiple choice, and comment questions in the order students will see them.</p>
                            </div>
                            <button type="button" class="builder-btn builder-btn-primary" onclick="addQuestion()">Add Question</button>
                        </div>
                        <div id="formCanvas" class="question-list"></div>
                    </article>
                </div>

                <aside class="builder-preview">
                    <div class="preview-card">
                        <div class="preview-card-header">
                            <span class="preview-label">Student Preview</span>
                            <span class="preview-mini-badge">Preview Only</span>
                        </div>
                        <h3 id="previewTitle">Faculty Evaluation Form</h3>
                        <p id="previewMeta">School year and semester will appear here.</p>
                        <div id="previewQuestions" class="preview-questions"></div>
                    </div>
                </aside>
            </div>

            <div class="builder-savebar">
                <div>
                    <strong>Ready to save?</strong>
                    <span>Validation runs on both browser and Laravel backend.</span>
                </div>
                <button type="button" class="builder-btn builder-btn-primary" onclick="saveForm()">Save Form</button>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin/forms.js') }}"></script>
@endpush
