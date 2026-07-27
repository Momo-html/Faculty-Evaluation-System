@if(session('success'))<div class="card" role="status">{{ session('success') }}</div>@endif
@if(session('warning'))<div class="card" role="alert">{{ session('warning') }}</div>@endif
@if($errors->any())<div class="card" role="alert"><strong>Please correct these items:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(session('import_errors'))<div class="card"><strong>Rejected CSV rows:</strong><ul>@foreach(session('import_errors') as $item)<li>Row {{ $item->row_number }}: {{ $item->error_message }}</li>@endforeach</ul></div>@endif
