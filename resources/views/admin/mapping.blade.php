@extends('layouts.admin')

@section('content')
    <div id="mapping" class="page-content">
        <div class="page-heading">
            <h2>Faculty-Course Mapping</h2>
            <button class="btn-primary" onclick="toggleQuickAdd()">+ New Mapping</button>
        </div>

        <div class="card builder-card" id="quickAddContainer" style="display: none; margin-bottom: 25px;">
            <div class="card-header-row">
                <h3>Create New Association</h3>
                <button onclick="toggleQuickAdd()" class="close-btn">x</button>
            </div>

            <div class="form-main-grid">
                <div class="input-stack">
                    <div class="input-group">
                        <label>Primary Department</label>
                        <select id="mapDept" class="table-select">
                            <option value="" disabled selected>Assign to Department...</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->full_name }} ({{ $dept->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Class Section</label>
                        <select id="mapSection">
                            <option value="" disabled selected>Select Section</option>
                            @foreach($sections as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->section_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Academic Term</label>
                        <select id="mapSemester">
                            <option value="" disabled selected>Select Semester</option>
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester">2nd Semester</option>
                            <option value="Summer">Summer</option>
                        </select>
                    </div>
                </div>

                <div class="input-stack">
                    <div class="input-group dropdown-wrapper">
                        <label>Course</label>
                        <input type="text" id="subSearch" placeholder="Type to search courses..."
                            onkeyup="runCombinedFilter('sub')" onfocus="showDropdown('sub')" autocomplete="off">
                        <div id="subDropdownList" class="floating-list">
                            @foreach($allSubjects as $subject)
                                <div class="dropdown-item" data-info="{{ $subject->subject_code }} {{ $subject->subject_name }}"
                                    onclick="selectItem('sub', '{{ $subject->subject_code }}', '{{ $subject->id }}')">
                                    <span class="dept-tag">[{{ $subject->department->code ?? 'N/A' }}]</span>
                                    {{ $subject->subject_code }} - {{ $subject->subject_name }}
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" id="mapSub" value="">
                    </div>

                    <div class="input-group dropdown-wrapper">
                        <label>Assigned Instructor</label>
                        <input type="text" id="facSearch" placeholder="Type to search faculty..."
                            onkeyup="runCombinedFilter('fac')" onfocus="showDropdown('fac')" autocomplete="off">
                        <div id="facDropdownList" class="floating-list">
                            @foreach($allFaculty as $fac)
                                <div class="dropdown-item" data-info="{{ $fac->name }}"
                                    onclick="selectItem('fac', '{{ $fac->name }}', '{{ $fac->id }}')">
                                    {{ $fac->name }} <small class="text-muted">({{ $fac->dept_code ?? 'N/A' }})</small>
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" id="mapFac" value="">
                    </div>
                </div>
            </div>

            <div class="form-actions-row">
                <button class="btn-secondary btn-small" onclick="toggleQuickAdd()">Cancel</button>
                <button class="btn-primary btn-small" onclick="addMapping()">Link Account</button>
            </div>
        </div>

        <div class="card">
            <div class="table-toolbar">
                <p class="sidebar-label">Existing Mappings</p>
                <input type="text" id="userSearch" onkeyup="searchTable()" placeholder="Search by name or code...">
            </div>

            <table class="mapping-table" id="userTable">
                <thead>
                    <tr>
                        <th>Course Details</th>
                        <th>Assigned Faculty</th>
                        <th>Section</th>
                        <th>Semester</th>
                        <th>Department</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mappings as $map)
                        <tr class="mapping-row" data-dept="{{ $map->department_name }}">
                            <td>
                                <div class="table-primary-text">{{ $map->subject_code }}</div>
                                <div class="table-muted-text">{{ $map->subject_name }}</div>
                            </td>
                            <td><b>{{ $map->faculty_name }}</b></td>
                            <td><span class="badge pos">{{ $map->section_name }}</span></td>
                            <td>{{ $map->semester }}</td>
                            <td><small class="text-muted">{{ $map->department_name ?? 'N/A' }}</small></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin/mapping.js') }}"></script>
@endpush
