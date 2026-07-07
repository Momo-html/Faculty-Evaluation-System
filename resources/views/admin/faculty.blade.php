@extends('layouts.admin')

@section('content')
    <div id="faculty" class="page-content">
        <div class="page-heading">
            <h2>Faculty Directory</h2>
            <div class="page-actions">
                <button class="btn-primary" onclick="toggleQuickAdd()">+ Add Faculty</button>
                <input type="file" id="csvFileInput" accept=".csv" style="display:none;" onchange="handleCSVUpload(this)">
                <button class="btn-secondary" onclick="document.getElementById('csvFileInput').click()">Bulk CSV Import</button>
            </div>
        </div>

        <div id="quickAddContainer" class="card" style="display: none;">
            <div class="card-header-row">
                <h3>Manual Faculty Entry</h3>
                <button onclick="toggleQuickAdd()" class="close-btn">x</button>
            </div>

            <div class="quick-add-form">
                <div class="form-row two-cols">
                    <div class="input-group">
                        <label>Faculty ID</label>
                        <input type="text" id="facId" placeholder="e.g. 2024-001">
                    </div>
                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" id="facEmail" placeholder="email@feucavite.edu.ph">
                    </div>
                </div>

                <div class="form-row three-cols">
                    <div class="input-group">
                        <label for="facfirstName">First Name</label>
                        <input type="text" id="facfirstName" name="first_name" placeholder="First Name" required>
                    </div>
                    <div class="input-group">
                        <label for="facmiddleName">Middle Name</label>
                        <input type="text" id="facmiddleName" name="middle_name" placeholder="Middle Name">
                    </div>
                    <div class="input-group">
                        <label for="faclastName">Last Name</label>
                        <input type="text" id="faclastName" name="last_name" placeholder="Last Name" required>
                    </div>
                </div>

                <div class="form-row action-row">
                    <div class="input-group">
                        <label>Department</label>
                        <select id="facDept">
                            <option value="" selected disabled>Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->full_name }} ({{ $dept->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="button-group">
                        <button class="btn-primary" onclick="submitFaculty()" style="width:100%;">Save Faculty Member</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <input type="text" id="userSearch" placeholder="Search Faculty..." onkeyup="searchTable()">
            <table id="userTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($faculty as $fac)
                        <tr class="faculty-main-row">
                            <td>{{ $fac->employee_id }}</td>
                            <td><b>{{ $fac->name }}</b></td>
                            <td>{{ $fac->email ?? 'No Email' }}</td>
                            <td>
                                @if($fac->department_name)
                                    {{ $fac->department_name }}
                                    <small class="text-muted">({{ $fac->department_code }})</small>
                                @else
                                    <span class="text-muted">No Department Assigned (ID: {{ $fac->department_id }})</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-stack">
                                    <button class="btn-small btn-primary" onclick="toggleFacultyManage('faculty-manage-{{ $fac->id }}')">Manage</button>
                                </div>
                            </td>
                        </tr>
                        <tr id="faculty-manage-{{ $fac->id }}" class="detail-row faculty-manage-row" style="display: none;">
                            <td colspan="5">
                                <div class="expansion-content">
                                    <div class="card-header-row">
                                        <h3>Manage Assignment</h3>
                                        <button class="close-btn" onclick="toggleFacultyManage('faculty-manage-{{ $fac->id }}')">x</button>
                                    </div>

                                    <div class="form-row five-cols">
                                        <div class="input-group">
                                            <label for="fac-manage-dept-{{ $fac->id }}">Assign to Department</label>
                                            <select id="fac-manage-dept-{{ $fac->id }}">
                                                <option value="" disabled>Select Department</option>
                                                @foreach($departments as $dept)
                                                    <option value="{{ $dept->id }}" {{ $fac->department_id == $dept->id ? 'selected' : '' }}>
                                                        {{ $dept->full_name }} ({{ $dept->code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="input-group">
                                            <label for="fac-manage-section-{{ $fac->id }}">Select Section</label>
                                            <select id="fac-manage-section-{{ $fac->id }}">
                                                <option value="" selected disabled>Select Section</option>
                                                @foreach($sections as $section)
                                                    <option value="{{ $section->id }}" data-dept="{{ $section->department_id }}">
                                                        {{ $section->section_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="input-group">
                                            <label for="fac-manage-semester-{{ $fac->id }}">Select Semester</label>
                                            <select id="fac-manage-semester-{{ $fac->id }}">
                                                <option value="" selected disabled>Select Semester</option>
                                                @foreach($semesters as $semester)
                                                    <option value="{{ $semester }}">{{ $semester }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="input-group">
                                            <label for="fac-manage-course-{{ $fac->id }}">Course</label>
                                            <select id="fac-manage-course-{{ $fac->id }}">
                                                <option value="" selected disabled>Select Course</option>
                                                @foreach($allSubjects as $subject)
                                                    <option value="{{ $subject->id }}">
                                                        {{ $subject->subject_code }} - {{ $subject->subject_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="input-group">
                                            <label for="fac-manage-instructor-{{ $fac->id }}">Assigned Instructor</label>
                                            <select id="fac-manage-instructor-{{ $fac->id }}">
                                                @foreach($allFaculty as $instructor)
                                                    <option value="{{ $instructor->id }}" {{ $fac->id == $instructor->id ? 'selected' : '' }}>
                                                        {{ $instructor->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="manage-actions">
                                        <button class="btn-primary" onclick="saveFacultyAssignment({{ $fac->id }})">Save Assignment</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin/shared.js') }}"></script>
    <script src="{{ asset('js/admin/faculty.js') }}"></script>
@endpush
