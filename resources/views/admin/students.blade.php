@extends('layouts.admin')

@section('content')
    <div id="students" class="page-content">
        <div class="page-heading">
            <h2>Student Directory</h2>
            <div class="page-actions">
                <button class="btn-primary" onclick="toggleQuickAdd()">+ Add Student</button>
                <input type="file" id="csvFileInput" accept=".csv" style="display:none;" onchange="handleCSVUpload(this)">
                <button class="btn-secondary" onclick="document.getElementById('csvFileInput').click()">Bulk Import</button>
            </div>
        </div>

        <div id="quickAddContainer" class="card" style="display: none; margin-bottom: 20px;">
            <div class="card-header-row">
                <h3>Manual Student Entry</h3>
                <button onclick="toggleQuickAdd()" class="close-btn">x</button>
            </div>

            <div class="quick-add-form">
                <div class="form-row two-cols">
                    <div class="input-group">
                        <label for="stuId">Student ID</label>
                        <input type="text" id="stuId" name="student_id" placeholder="e.g. 2024-10001">
                    </div>
                    <div class="input-group">
                        <label for="stuEmail">Email Address</label>
                        <input type="email" id="stuEmail" name="email" placeholder="student@feucavite.edu.ph">
                    </div>
                </div>

                <div class="form-row three-cols">
                    <div class="input-group">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="first_name" placeholder="First Name" required>
                    </div>
                    <div class="input-group">
                        <label for="middleName">Middle Name</label>
                        <input type="text" id="middleName" name="middle_name" placeholder="Middle Name">
                    </div>
                    <div class="input-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="last_name" placeholder="Last Name" required>
                    </div>
                </div>

                <div class="form-row three-cols">
                    <div class="input-group">
                        <label for="stuDept">Department / Program</label>
                        <select id="stuDept" name="department_id">
                            <option value="" selected disabled>Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->code }} - {{ $dept->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="stuSection">Class Assignment</label>
                        <select id="stuSection" name="section_id">
                            <option value="" selected disabled>Select Class</option>
                            @foreach($sections as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->section_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="button-group">
                        <button class="btn-primary" onclick="submitStudent()" style="width:100%;">Save Student</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-toolbar">
                <input type="text" id="userSearch" placeholder="Search Students..." onkeyup="searchTable()">
            </div>

            <table id="userTable">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Department</th>
                        <th>Class</th>
                        <th>Course Assignments</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $user)
                        <tr class="student-main-row">
                            <td>{{ $user->faculty_id }}</td>
                            <td><b>{{ $user->name }}</b></td>
                            <td>{{ $user->department_name ?? $user->department }}</td>
                            <td>
                                <span class="text-primary" style="font-weight: 600;">
                                    {{ $user->primary_section_name ?? 'Unassigned' }}
                                </span>
                            </td>
                            <td>
                                <div class="subject-badges">
                                    @foreach($user->enrolled_subjects as $sub)
                                        <span class="static-pill">{{ $sub->subject_code }} ({{ $sub->section_name }})</span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <button class="btn-small" onclick="toggleDetails('details-{{ $user->id }}')">Manage</button>
                            </td>
                        </tr>

                        <tr id="details-{{ $user->id }}" class="detail-row" style="display: none;">
                            <td colspan="6">
                                <div class="expansion-content">
                                    <div class="expansion-grid">
                                        <div class="manage-box">
                                            <p><strong>Class Assignment</strong></p>
                                            <div class="inline-control-row">
                                                <select id="update-sec-{{ $user->id }}" class="search-input">
                                                    <option value="">-- Unassigned --</option>
                                                    @foreach($sections as $sec)
                                                        <option value="{{ $sec->id }}" {{ (isset($user->section_id) && $user->section_id == $sec->id) ? 'selected' : '' }}>
                                                            {{ $sec->section_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button class="btn-primary" onclick="updateStudentSection({{ $user->id }})">Update</button>
                                            </div>
                                            <small class="help-text">Updating the class assignment can auto-enroll mapped courses.</small>
                                        </div>

                                        <div class="manage-box">
                                            <p><strong>Add Course Assignment</strong></p>
                                            <div class="inline-control-row">
                                                <select id="sub-select-{{ $user->id }}" class="search-input" data-user-dept="{{ $user->department_id ?? '' }}">
                                                    <option value="">-- Select Assigned Mapping --</option>
                                                    @foreach($mappings as $map)
                                                        <option value="{{ $map->id }}" data-dept="{{ $map->department_id ?? '' }}">
                                                            {{ $map->subject_code }} - {{ $map->section_name }}
                                                            ({{ $map->semester }})
                                                            ({{ $map->faculty_name }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button class="btn-primary" onclick="inlineAssign({{ $user->id }})">Add</button>
                                            </div>
                                        </div>

                                        <div class="manage-box">
                                            <p><strong>Current Assignments</strong></p>
                                            <div class="removable-badges-list">
                                                @forelse($user->enrolled_subjects as $sub)
                                                    <span class="badge-removable">
                                                        {{ $sub->subject_code }}
                                                        <span class="delete-icon"
                                                            onclick="unassignSubject({{ $user->id }}, {{ $sub->id }})">x</span>
                                                    </span>
                                                @empty
                                                    <small class="help-text">No courses assigned yet.</small>
                                                @endforelse
                                            </div>
                                        </div>
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
    <script src="{{ asset('js/admin/students.js') }}"></script>
@endpush
