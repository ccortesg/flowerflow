@extends('layouts.flowerflow')
@section('title', 'Mis propuestas')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><p class="ff-kicker mb-1">Convocatoria</p><h1>Mis propuestas</h1></div>@if(config('flowerflow.flags.submissions'))<a class="btn btn-flower" href="{{ route('submissions.create') }}">Nueva propuesta</a>@endif</div>
<div class="card ff-card"><div class="card-body">@if($submissions->isEmpty())<p class="mb-0">Aún no tienes propuestas registradas.</p>@else<div class="table-responsive"><table class="table"><thead><tr><th>Título</th><th>Categoría</th><th>Estado</th><th>Folio</th><th></th></tr></thead><tbody>@foreach($submissions as $item)<tr><td>{{ $item->title }}</td><td>{{ $item->category->name }}</td><td>{{ $item->statusLabel() }}</td><td>{{ $item->folio ?: '—' }}</td><td><a href="{{ route('submissions.show', $item) }}">Ver</a>@if($item->isDraft() && config('flowerflow.flags.submissions')) · <a href="{{ route('submissions.edit', $item) }}">Editar</a>@endif</td></tr>@endforeach</tbody></table></div>@endif</div></div>
@endsection
