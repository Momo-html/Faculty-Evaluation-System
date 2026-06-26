@extends('layouts.superadmin')

@section('title', 'Admin Management')

@section('content')
    <div class="stats-grid">
        <div class="card">
            <h3>Total Administrators</h3>
            <p class="stat-value">{{ $stats['total_admins'] }}</p>
        </div>
        <div class="card">
            <h3>Total Students</h3>
            <p class="stat-value">{{ $stats['total_students'] }}</p>
        </div>
        <div class="card">
            <h3>Total Feedback</h3>
            <p class="stat-value" style="color: var(--feu-gold);">{{ $stats['total_evaluations'] }}</p>
        </div>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="color: var(--feu-green); margin: 0;">Administrator Accounts</h2>
            <button class="btn-primary" onclick="toggleModal(true)">+ Add New Admin</button>
        </div>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Faculty ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($admins as $admin)
                <tr>
                    <td>{{ $admin->faculty_id }}</td>
                    <td>{{ $admin->name }}</td>
                    <td>{{ $admin->email }}</td>
                    <td>{{ $admin->department }}</td>
                    <td>
                        <form action="{{ route('superadmin.deleteAdmin', $admin->id) }}" method="POST" onsubmit="return confirm('Revoke admin access for {{ $admin->name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger">Revoke</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="modal-overlay" id="adminModal">
        <div class="card" style="width: 100%; max-width: 500px;">
            <h2 style="color: var(--feu-green);">Register New Admin</h2>
            <form action="{{ route('superadmin.addAdmin') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Faculty ID</label>
                    <input type="text" name="faculty_id" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Initial Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn-primary">Create Account</button>
                    <button type="button" class="btn-primary" style="background: #666;" onclick="toggleModal(false)">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function toggleModal(show) {
        document.getElementById('adminModal').style.display = show ? 'flex' : 'none';
    }
</script>
@endpush