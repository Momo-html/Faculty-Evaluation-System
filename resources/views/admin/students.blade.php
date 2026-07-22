@extends('layouts.admin')

@section('content')
<div class="page-content">
    <div class="page-heading"><h2>Student Directory</h2></div>
    @include('admin.directory-messages')

    <div class="card"><h3>Add Student</h3>
        <form method="POST" action="{{ route('admin.students.store') }}">@csrf
            <div class="form-row three-cols">
                <div class="input-group"><label>Student number</label><input name="student_number" value="{{ old('student_number') }}" required></div>
                <div class="input-group"><label>Full name</label><input name="name" value="{{ old('name') }}" required></div>
                <div class="input-group"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required></div>
                <div class="input-group"><label>Department</label><select name="department_id" required><option value="">Select</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id')==$department->id)>{{ $department->code }} — {{ $department->department_name }}</option>@endforeach</select></div>
                <div class="input-group"><label>Section (optional)</label><select name="section_id"><option value="">Unassigned</option>@foreach($sections as $section)<option value="{{ $section->id }}" @selected(old('section_id')==$section->id)>{{ $section->section_name }} ({{ $section->department->code }})</option>@endforeach</select></div>
                <div class="input-group"><label>Password</label><input type="password" name="password" minlength="8" required></div>
                <div class="input-group"><label>Confirm password</label><input type="password" name="password_confirmation" required></div>
            </div><input type="hidden" name="status" value="active"><button class="btn-primary">Create Student</button>
        </form>
    </div>

    <div class="card"><h3>Bulk Import Students</h3>
        <p class="help-text">Required CSV headers: student_number, name, email, department_code, section_name, password. Section may be blank.</p>
        <form method="POST" action="{{ route('admin.directory.import') }}" enctype="multipart/form-data">@csrf<input type="hidden" name="type" value="student"><input type="file" name="csv_file" accept=".csv,text/csv" required> <button class="btn-secondary">Import CSV</button></form>
    </div>

    <div class="card">
        <form method="GET" class="table-toolbar"><input name="search" value="{{ request('search') }}" placeholder="Search number, name, or email">
            <select name="department_id"><option value="">All departments</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department_id')==$department->id)>{{ $department->code }}</option>@endforeach</select>
            <select name="section_id"><option value="">All sections</option>@foreach($sections as $section)<option value="{{ $section->id }}" @selected(request('section_id')==$section->id)>{{ $section->section_name }}</option>@endforeach</select>
            <select name="status"><option value="">All statuses</option><option @selected(request('status')==='active')>active</option><option @selected(request('status')==='inactive')>inactive</option></select>
            <button class="btn-primary">Filter</button><a class="btn-secondary" href="{{ route('admin.students') }}">Clear</a></form>
        <table><thead><tr><th>Student</th><th>Department / Section</th><th>Status</th><th>Subjects</th><th>Manage</th></tr></thead><tbody>
        @forelse($students as $student)
            <tr><td><strong>{{ $student->student_number }}</strong><br>{{ $student->name }}<br><small>{{ $student->email }}</small></td><td>{{ $student->department?->code }} / {{ $student->section?->section_name ?? 'Unassigned' }}</td><td>{{ ucfirst($student->status) }}</td>
                <td>@forelse($student->subjectMappings as $mapping)<div>{{ $mapping->subject->subject_code }} / {{ $mapping->section->section_name }}
                    <form method="POST" action="{{ route('admin.students.assignments.destroy', [$student, $mapping]) }}" style="display:inline">@csrf @method('DELETE')<button class="btn-small" onclick="return confirm('Unassign this subject?')">Remove</button></form></div>@empty None @endforelse</td>
                <td>
                    <button type="button" class="btn-small btn-primary" onclick="openDirectoryModal('student-modal-{{ $student->id }}')">Edit</button>
                    <dialog class="directory-modal" id="student-modal-{{ $student->id }}" aria-labelledby="student-modal-title-{{ $student->id }}">
                        <div class="directory-modal-header">
                            <div><small>STUDENT PROFILE</small><h3 id="student-modal-title-{{ $student->id }}">Edit {{ $student->name }}</h3></div>
                            <button type="button" class="directory-modal-close" onclick="closeDirectoryModal('student-modal-{{ $student->id }}')" aria-label="Close">&times;</button>
                        </div>
                        <div class="directory-modal-body">
                    <form method="POST" action="{{ route('admin.students.update', $student) }}" class="directory-modal-section">@csrf @method('PUT')
                        <h4>Profile and Section Details</h4><div class="directory-modal-grid">
                        <div class="input-group"><label>Student Number</label><input name="student_number" value="{{ $student->student_number }}" required></div>
                        <div class="input-group"><label>Full Name</label><input name="name" value="{{ $student->name }}" required></div>
                        <div class="input-group"><label>Email Address</label><input type="email" name="email" value="{{ $student->email }}" required></div>
                        <div class="input-group"><label>Department</label><select name="department_id" required>@foreach($departments as $department)<option value="{{ $department->id }}" @selected($student->department_id===$department->id)>{{ $department->code }}</option>@endforeach</select></div>
                        <div class="input-group"><label>Section</label><select name="section_id"><option value="">Unassigned</option>@foreach($sections as $section)<option value="{{ $section->id }}" @selected($student->section_id===$section->id)>{{ $section->section_name }} ({{ $section->department->code }})</option>@endforeach</select></div>
                        <div class="input-group"><label>Account Status</label><select name="status"><option @selected($student->status==='active')>active</option><option @selected($student->status==='inactive')>inactive</option></select></div>
                        <div class="input-group"><label>New Password (Optional)</label><input type="password" name="password" placeholder="Leave blank to keep current password"></div>
                        <div class="input-group"><label>Confirm New Password</label><input type="password" name="password_confirmation" placeholder="Repeat new password"></div></div><button class="btn-primary">Save Profile</button>
                    </form>
                    <form method="POST" action="{{ route('admin.students.assignments.store', $student) }}" class="directory-modal-section">@csrf<h4>Subject Assignment</h4><div class="input-group"><label>Subject, Section, and Faculty</label><select name="subject_mapping_id" required><option value="">Choose subject mapping</option>@foreach($mappings as $mapping)<option value="{{ $mapping->id }}">{{ $mapping->subject->subject_code }} — {{ $mapping->section->section_name }} — {{ $mapping->faculty->faculty_name }}</option>@endforeach</select></div><button class="btn-primary">Assign Subject</button></form>
                    <p class="directory-modal-history"><strong>Recent section history:</strong><br><small>@forelse($student->sectionAllocations->sortByDesc('assigned_at')->take(3) as $allocation){{ $allocation->section?->section_name ?? 'Removed' }} ({{ $allocation->assigned_at->format('M d, Y') }}){{ !$loop->last ? ', ' : '' }}@empty No changes yet @endforelse</small></p>
                    <form method="POST" action="{{ route('admin.students.destroy', $student) }}" class="directory-modal-danger">@csrf @method('DELETE')<button class="btn-secondary" onclick="return confirm('Archive this student?')">Archive Student</button></form>
                        </div>
                    </dialog>
                </td></tr>
        @empty<tr><td colspan="5">No student records found.</td></tr>@endforelse
        </tbody></table>{{ $students->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin/directory-modal.js') }}"></script>
@endpush
