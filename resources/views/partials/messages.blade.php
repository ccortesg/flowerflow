@php
    $rawStatus = session('status');
    $fortifyStatusKey = $rawStatus ? 'fortify.statuses.'.$rawStatus : null;
    $statusMessage = $fortifyStatusKey && \Illuminate\Support\Facades\Lang::has($fortifyStatusKey)
        ? __($fortifyStatusKey)
        : $rawStatus;
@endphp

@if($statusMessage)
    <div class="alert alert-success" role="status" aria-live="polite">{{ $statusMessage }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning" role="alert">{{ session('warning') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert" aria-live="assertive">
        <strong>No pudimos guardar la información.</strong>
        <p class="mb-0 mt-1">Revisa los campos indicados e inténtalo nuevamente:</p>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
