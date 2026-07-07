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
            <button class="btn-primary" type="button" onclick="toggleModal(true)">+ Add Admin</button>
        </div>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert-danger">{{ $errors->first() }}</div>
        @endif

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admins as $admin)
                    <tr>
                        <td>USR-{{ $admin->id }}</td>
                        <td>{{ $admin->name }}</td>
                        <td>{{ $admin->email }}</td>
                        <td>{{ ucfirst($admin->role) }}</td>
                        <td>{{ $admin->department?->department_name ?? 'All Departments' }}</td>
                        <td>{{ ucfirst($admin->status) }}</td>
                        <td style="display:flex; gap:8px;">
                            <form action="{{ route('superadmin.updateAdmin', $admin->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="name" value="{{ $admin->name }}">
                                <input type="hidden" name="email" value="{{ $admin->email }}">
                                <input type="hidden" name="role" value="{{ $admin->role }}">
                                <input type="hidden" name="department_id" value="{{ $admin->department_id }}">
                                <input type="hidden" name="status" value="{{ $admin->status === 'active' ? 'inactive' : 'active' }}">
                                <button type="submit" class="btn-primary" style="padding:6px 10px;">
                                    {{ $admin->status === 'active' ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>

                            <form action="{{ route('superadmin.deleteAdmin', $admin->id) }}" method="POST" onsubmit="return confirm('Deactivate {{ $admin->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger">Revoke</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding: 30px; text-align:center; color:#777;">No administrator accounts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="modal-overlay" id="adminModal">
        <div class="card" style="width: 100%; max-width: 500px;">
            <h2 style="color: var(--feu-green);">Register Administrator</h2>
            <form action="{{ route('superadmin.addAdmin') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" class="form-control" required>
                        <option value="admin">Admin</option>
                        <option value="superadmin">Superadmin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select name="department_id" class="form-control">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->department_name }} ({{ $department->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Initial Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
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
