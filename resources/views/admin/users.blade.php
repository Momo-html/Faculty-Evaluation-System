@extends('layouts.admin')

@section('content')
        <div id="users" class="page-content">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h2 style="margin:0;">User Directory</h2>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn-primary" onclick="toggleQuickAdd()">+ Add Faculty</button>
                        <input type="file" id="csvFileInput" accept=".csv" style="display:none;"
                            onchange="handleCSVUpload(this)">
                        <button class="btn-secondary" onclick="document.getElementById('csvFileInput').click()">📥 Bulk
                            CSV Import</button>
                    </div>
                </div>

                <div id="quickAddContainer" class="card" style="display: none;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                        <h3 style="margin:0;">Manual Faculty Entry</h3>
                        <button onclick="toggleQuickAdd()" class="close-btn">✕</button>
                    </div>
                    <div class="flex-row">
                        <div><label>Faculty ID</label><input type="text" id="facId" placeholder="Faculty ID"></div>
                        <div><label>Email Address</label><input type="email" id="facEmail"
                                placeholder="email@feucavite.edu.ph"></div>
                        <div><label>Full Name</label><input type="text" id="facName" placeholder="Full Name"></div>
                        <div>
                            <label>Department</label>
                            <select id="facDept">
                                <option value="ICS">Inst. of Computing Studies</option>
                                <option value="IBA">Inst. of Biz & Accountancy</option>
                                <option value="IHS">Inst. of Health Sciences</option>
                                <option value="IET">Inst. of Eng. & Tech</option>
                            </select>
                        </div>
                        <div style="flex: 0 0 150px;"><label>&nbsp;</label><button class="btn-primary"
                                onclick="submitFaculty()" style="width:100%;">Save User</button></div>
                    </div>
                </div>

                <div class="card">
                    <input type="text" id="userSearch" placeholder="🔍 Search by ID, Name, Role or Department..."
                        onkeyup="searchTable()">
                    <table id="userTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td>{{ $user->faculty_id }}</td>
                                    <td><b>{{ $user->name }}</b></td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ ucfirst($user->role) }}</td>
                                    <td>{{ $user->department ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
@endsection