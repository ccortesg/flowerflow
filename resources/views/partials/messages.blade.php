@if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
@if(session('warning'))<div class="alert alert-warning" role="alert">{{ session('warning') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert"><strong>Revisa la información:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
