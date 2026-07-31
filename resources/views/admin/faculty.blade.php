@extends('layouts.admin')

@section('content')
<div class="page-content">
    <div class="page-heading"><h2>Faculty Directory</h2></div>
    @include('admin.directory-messages')

    <div class="card">
        <h3>Add Faculty</h3>
        <form method="POST" action="{{ route('admin.faculty.store') }}">@csrf
            <div class="form-row three-cols">
                <div class="input-group"><label>Employee ID</label><input name="employee_id" value="{{ old('employee_id') }}" required></div>
                <div class="input-group"><label>Full name</label><input name="faculty_name" value="{{ old('faculty_name') }}" required></div>
                <div class="input-group"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required></div>
                <div class="input-group"><label>Department</label><select name="department_id" required><option value="">Select</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->code }} — {{ $department->department_name }}</option>@endforeach</select></div>
            </div>
            <input type="hidden" name="status" value="active">
            <div class="directory-form-actions">
                <button class="btn-primary">Add Faculty</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Bulk Import Faculty</h3>
        <p class="help-text">CSV files required.</p>
        <form method="POST" action="{{ route('admin.directory.import') }}" enctype="multipart/form-data">@csrf
            <input type="hidden" name="type" value="account_creation">
            <div class="directory-form-actions">
                <input type="file" name="csv_files[]" accept=".csv,text/csv" multiple required>
                <button class="btn-secondary">Import Faculty</button>
            </div>
        </form>
    </div>

    <div class="card" id="directory-results">
        <form method="GET" action="{{ route('admin.faculty') }}#directory-results" class="table-toolbar">
            <input name="search" value="{{ request('search') }}" placeholder="Search ID, name, or email">
            <select name="department_id"><option value="">All departments</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->code }}</option>@endforeach</select>
            <select name="status"><option value="">All statuses</option><option @selected(request('status')==='active')>active</option><option @selected(request('status')==='inactive')>inactive</option></select>
            <button class="btn-primary">Filter</button><a class="btn-secondary" href="{{ route('admin.faculty') }}#directory-results">Clear</a>
        </form>
        <table><thead><tr><th>ID / Faculty</th><th>Department</th><th>Status</th><th>Academic assignments</th><th>Manage</th></tr></thead><tbody>
        @forelse($faculty as $member)
            <tr><td><strong>{{ $member->employee_id }}</strong><br>{{ $member->faculty_name }}<br><small>{{ $member->email }}</small></td><td>{{ $member->department?->code ?? 'Unassigned' }}</td><td>{{ ucfirst($member->status) }}</td>
                <td>@forelse($member->subjectMappings as $mapping)<div>{{ $mapping->subject->subject_code }} / {{ $mapping->section->section_name }} / {{ $mapping->semester }}
                    <form method="POST" action="{{ route('admin.faculty.assignments.destroy', [$member, $mapping]) }}" style="display:inline">@csrf @method('DELETE')<button class="btn-small" onclick="return confirm('Remove this unused mapping?')">Remove</button></form></div>@empty None @endforelse</td>
                <td>
                    <button type="button" class="btn-small btn-primary" onclick="openDirectoryModal('faculty-modal-{{ $member->id }}')">Edit</button>
                    <dialog class="directory-modal" id="faculty-modal-{{ $member->id }}" aria-labelledby="faculty-modal-title-{{ $member->id }}">
                        <div class="directory-modal-header">
                            <div><small>FACULTY PROFILE</small><h3 id="faculty-modal-title-{{ $member->id }}">Edit {{ $member->faculty_name }}</h3></div>
                            <button type="button" class="directory-modal-close" onclick="closeDirectoryModal('faculty-modal-{{ $member->id }}')" aria-label="Close">&times;</button>
                        </div>
                        <div class="directory-modal-body">
                    <form method="POST" action="{{ route('admin.faculty.update', $member) }}" class="directory-modal-section">@csrf @method('PUT')
                        <h4>Profile Details</h4>
                        <div class="directory-modal-grid">
                        <div class="input-group"><label>Employee ID</label><input name="employee_id" value="{{ $member->employee_id }}" required></div>
                        <div class="input-group"><label>Full Name</label><input name="faculty_name" value="{{ $member->faculty_name }}" required></div>
                        <div class="input-group"><label>Email Address</label><input type="email" name="email" value="{{ $member->email }}" required></div>
                        <div class="input-group"><label>Department</label><select name="department_id" required>@foreach($departments as $department)<option value="{{ $department->id }}" @selected($member->department_id===$department->id)>{{ $department->code }}</option>@endforeach</select></div>
                        <div class="input-group"><label>Account Status</label><select name="status"><option @selected($member->status==='active')>active</option><option @selected($member->status==='inactive')>inactive</option></select></div>
                        </div><button class="btn-primary">Save Profile</button>
                    </form>
                    <form method="POST" action="{{ route('admin.faculty.assignments.store', $member) }}" class="directory-modal-section">@csrf
                        <h4>Academic Assignment</h4><div class="directory-modal-grid">
                        <div class="input-group"><label>Subject</label><select name="subject_id" required><option value="">Select subject</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->subject_code }}</option>@endforeach</select></div>
                        <div class="input-group"><label>Section</label><select name="section_id" required><option value="">Select section</option>@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->section_name }}</option>@endforeach</select></div>
                        <div class="input-group"><label>School Year</label><input name="school_year" placeholder="2026-2027" required pattern="\d{4}-\d{4}"></div>
                        <div class="input-group"><label>Semester</label><input name="semester" placeholder="1st Semester" required></div></div><button class="btn-primary">Assign Subject</button>
                    </form>
                    <form method="POST" action="{{ route('admin.faculty.destroy', $member) }}" class="directory-modal-danger">@csrf @method('DELETE')<button class="btn-secondary" onclick="return confirm('Archive this faculty profile?')">Archive Faculty</button></form>
                        </div>
                    </dialog>
                </td></tr>
        @empty
            @if($importedFacultyEnrollments->isEmpty())<tr><td colspan="5">No faculty records found.</td></tr>@endif
        @endforelse
        @foreach($importedFacultyEnrollments as $enrollment)
            <tr>
                <td><strong>{{ $enrollment->user_id }}</strong><br>
                    {{ $enrollment->account?->full_name ?? $enrollment->account?->short_name ?? 'Account details not uploaded' }}<br>
                    <small>{{ $enrollment->account?->email }}</small>
                </td>
                <td>{{ $enrollment->department?->code ?? $enrollment->section?->department?->code ?? 'Unassigned' }}</td>
                <td>{{ ucfirst($enrollment->status) }}</td>
                <td><strong>{{ collect([$enrollment->course_id, $enrollment->section?->section_name ?? $enrollment->section_id])->filter()->join(' / ') }}</strong><br>
                    <small>{{ $enrollment->course?->long_name ?? $enrollment->course?->short_name ?? 'Course details not uploaded' }}</small>
                </td>
                <td>
                    <button type="button" class="btn-small btn-primary"
                        onclick="openDirectoryModal('imported-faculty-modal-{{ $enrollment->id }}')">Edit</button>
                    <dialog class="directory-modal" id="imported-faculty-modal-{{ $enrollment->id }}"
                        aria-labelledby="imported-faculty-modal-title-{{ $enrollment->id }}">
                        <div class="directory-modal-header">
                            <div><small>FACULTY PROFILE</small>
                                <h3 id="imported-faculty-modal-title-{{ $enrollment->id }}">Edit {{ $enrollment->account?->full_name ?? $enrollment->user_id }}</h3>
                            </div>
                            <button type="button" class="directory-modal-close"
                                onclick="closeDirectoryModal('imported-faculty-modal-{{ $enrollment->id }}')" aria-label="Close">&times;</button>
                        </div>
                        <div class="directory-modal-body">
                            <form method="POST" action="{{ route('admin.faculty.imported.update', $enrollment) }}"
                                class="directory-modal-section">
                                @csrf @method('PUT')
                                <h4>Profile Details</h4>
                                <div class="directory-modal-grid">
                                    <div class="input-group"><label>Employee ID</label><input value="{{ $enrollment->user_id }}" disabled></div>
                                    <div class="input-group"><label>Full Name</label><input name="full_name" value="{{ $enrollment->account?->full_name }}" required></div>
                                    <div class="input-group"><label>Email Address</label><input type="email" name="email" value="{{ $enrollment->account?->email }}"></div>
                                    <div class="input-group"><label>Department</label><select name="department_id">
                                        <option value="">Unassigned</option>
                                        @foreach($departments as $department)<option value="{{ $department->id }}" @selected((string) ($enrollment->department_id ?? $enrollment->section?->department_id) === (string) $department->id)>{{ $department->code }}</option>@endforeach
                                    </select></div>
                                    <div class="input-group"><label>Account Status</label><select name="status">
                                        <option @selected($enrollment->status === 'active')>active</option>
                                        <option @selected($enrollment->status === 'inactive')>inactive</option>
                                    </select></div>
                                </div>
                                <h4>Academic Assignment</h4>
                                <div class="directory-modal-grid">
                                    <div class="input-group"><label>Course ID</label><input name="course_id" value="{{ $enrollment->course_id }}" required></div>
                                    <div class="input-group"><label>Section</label><select name="section_id">
                                        <option value="">Unassigned</option>
                                        @foreach($sections as $section)<option value="{{ $section->id }}" @selected((string) $enrollment->section_id === (string) $section->id)>{{ $section->section_name }}</option>@endforeach
                                    </select></div>
                                </div>
                                <button class="btn-primary">Save Profile</button>
                            </form>
                            <form method="POST" action="{{ route('admin.faculty.imported.destroy', $enrollment) }}"
                                class="directory-modal-danger">
                                @csrf @method('DELETE')
                                <button class="btn-secondary" onclick="return confirm('Archive this faculty profile?')">Archive Faculty</button>
                            </form>
                        </div>
                    </dialog>
                </td>
            </tr>
        @endforeach
        </tbody></table>{{ $directoryPagination->fragment('directory-results')->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin/directory-modal.js') }}"></script>
@endpush
