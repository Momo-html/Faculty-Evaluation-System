@if(session('success'))<div class="card" role="status">{{ session('success') }}</div>@endif
@if(session('warning'))
    <div class="card directory-alert directory-alert-warning" role="alert">
        <span class="directory-alert-icon" aria-hidden="true">!</span>
        <div>{{ session('warning') }}</div>
    </div>
@endif
@if($errors->any())
    <div class="card directory-alert directory-alert-error" role="alert">
        <span class="directory-alert-icon" aria-hidden="true">!</span>
        <div>
            <strong>Import unsuccessful</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    </div>
@endif
@if(session('import_errors'))<div class="card"><strong>Rejected CSV rows:</strong><ul>@foreach(session('import_errors') as $item)<li>Row {{ $item->row_number }}: {{ $item->error_message }}</li>@endforeach</ul></div>@endif
