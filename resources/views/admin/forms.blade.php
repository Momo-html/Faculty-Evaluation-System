@extends('layouts.admin')

@section('content')
    <div id="forms" class="page-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="margin:0; color: var(--feu-green);">Evaluation Management</h2>
            <button class="btn-primary" onclick="showCreateForm()">+ Create New Evaluation Period</button>
        </div>

        <div class="card" id="tableSection">
            <table style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 2px solid #eee;">
                        <th style="padding: 12px;">School Year</th>
                        <th style="padding: 12px;">Semester</th>
                        <th style="padding: 12px;">Schedule</th>
                        <th style="padding: 12px;">Status</th>
                        <th style="text-align:right; padding: 12px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allForms as $f)
                        <tr style="border-bottom: 1px solid #f9f9f9;">
                            <td style="padding: 12px;"><b>{{ $f->school_year }}</b></td>
                            <td style="padding: 12px;">{{ $f->semester }}</td>
                            <td style="padding: 12px;">
                                <small style="color: #666;">
                                    <b>Open:</b> {{ date('M d, Y h:i A', strtotime($f->open_at)) }}<br>
                                    <b>Close:</b> {{ date('M d, Y h:i A', strtotime($f->close_at)) }}
                                </small>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge {{ $f->is_active ? 'pos' : 'neu' }}">
                                    {{ $f->is_active ? 'Active' : 'Closed' }}
                                </span>
                            </td>
                            <td style="text-align:right; padding: 12px;">
                                <button class="btn-small" onclick="loadFormForEdit({{ $f->id }})"
                                    style="padding: 5px 12px;">Edit</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Builder Section -->
        <div id="builderSection" style="display:none; margin-top: 20px;" class="card">
            <div
                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">
                <h3 id="builderTitle" style="margin:0; color: var(--feu-green);">Edit Evaluation Form</h3>
                <div>
                <button id="deleteBtn" class="btn-danger" onclick="deleteForm()" style="padding: 5px 15px; display:none;">Delete</button>
                <button class="btn-secondary" onclick="closeBuilder()" style="padding: 5px 15px;">Cancel</button>
                </div>
            </div>

            <input type="hidden" id="currentFormId">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="input-group">
                    <label style="display:block; font-weight: 600; margin-bottom: 5px;">School Year</label>
                    <input type="text" id="sy" placeholder="2025-2026"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd;">
                </div>
                <div class="input-group">
                    <label style="display:block; font-weight: 600; margin-bottom: 5px;">Semester</label>
                    <select id="sem" style="width: 100%; padding: 8px; border: 1px solid #ddd;">
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>
                <div class="input-group">
                    <label style="display:block; font-weight: 600; margin-bottom: 5px;">Open At</label>
                    <input type="datetime-local" id="openAt" style="width: 100%; padding: 8px; border: 1px solid #ddd;">
                </div>
                <div class="input-group">
                    <label style="display:block; font-weight: 600; margin-bottom: 5px;">Close At</label>
                    <input type="datetime-local" id="closeAt" style="width: 100%; padding: 8px; border: 1px solid #ddd;">
                </div>
            </div>

            <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h4 style="margin:0;">Evaluation Questions</h4>
                    <button class="btn-secondary" onclick="addQuestion()" style="padding: 8px 15px;">+ Add Question</button>
                </div>
                <div id="formCanvas"></div>
            </div>

            <div style="margin-top: 20px;">
                <button class="btn-primary" onclick="saveForm()" style="width: 100%; padding: 12px; font-weight: bold;">Save
                    Evaluation Period</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin/forms.js') }}"></script>
@endpush
