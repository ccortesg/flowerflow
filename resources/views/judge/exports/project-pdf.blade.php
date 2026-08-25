<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Proyecto asignado {{ $assignment->public_id }}</title>
  <style>
    @page { margin: 105px 46px 64px; }
    * { box-sizing: border-box; }
    body { color: #17352f; font-family: "DejaVu Sans", sans-serif; font-size: 10pt; line-height: 1.5; }
    header { position: fixed; top: -82px; left: 0; right: 0; height: 66px; border-bottom: 2px solid #167c5b; }
    header table { width: 100%; border-collapse: collapse; }
    header td { vertical-align: middle; }
    header img { max-width: 160px; }
    header .flower-flow-logo { height: 84px; }
    header .florece-logo { height: 48px; }
    header .right { text-align: right; }
    footer { position: fixed; left: 0; right: 0; bottom: -42px; border-top: 1px solid #cbdad3; padding-top: 8px; color: #52655f; font-size: 8pt; }
    footer .page-number { float: right; }
    footer .page-number::after { content: counter(page); }
    h1 { color: #0b5c42; font-size: 21pt; margin: 0 0 5px; }
    h2 { color: #0b5c42; font-size: 13pt; margin: 18px 0 7px; border-bottom: 1px solid #dce8e2; padding-bottom: 4px; }
    p { margin: 0 0 8px; }
    .kicker { color: #167c5b; font-size: 8pt; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
    .notice { background: #fff8dd; border-left: 4px solid #d9a900; margin: 13px 0; padding: 9px 11px; }
    .metadata { width: 100%; border-collapse: collapse; margin-top: 12px; }
    .metadata th, .metadata td { border-bottom: 1px solid #e2ebe6; padding: 6px; text-align: left; vertical-align: top; }
    .metadata th { background: #f3f8f5; width: 26%; }
    ul { margin: 5px 0 0; padding-left: 19px; }
    li { margin-bottom: 6px; }
    a { color: #0b5c42; overflow-wrap: anywhere; }
    .description { overflow-wrap: anywhere; }
    .description img, .description video, .description audio, .description iframe, .description script { display: none !important; }
    .confidential { color: #7a2f20; font-weight: bold; }
  </style>
</head>
<body>
  <header>
    <table><tr><td><img class="flower-flow-logo" src="{{ $flowerFlowLogo }}" alt="Flower Flow"></td><td class="right"><img class="florece-logo" src="{{ $floreceLogo }}" alt="Florece Hermosillo"></td></tr></table>
  </header>
  <footer>
    Flower Flow · Documento de trabajo para evaluación · <span class="confidential">Uso confidencial</span>
    <span class="page-number">Página </span>
  </footer>

  <p class="kicker">Paquete ciego inmutable</p>
  <h1>Proyecto asignado</h1>
  <p>Asignación <strong>{{ $assignment->public_id }}</strong></p>
  <div class="notice"><strong>Anonimización estructural.</strong> Se ocultan los datos estructurados de identidad y operación. El texto, los enlaces o los anexos pueden identificar a su autor.</div>

  <table class="metadata">
    <tr><th>Categoría</th><td>{{ data_get($payload, 'category.name') }}</td></tr>
    <tr><th>Modalidad</th><td>{{ data_get($payload, 'submission.participation_type') === 'team' ? 'Equipo' : 'Individual' }}</td></tr>
    <tr><th>Título</th><td>{{ data_get($payload, 'submission.title') }}</td></tr>
  </table>

  <h2>Resumen</h2>
  <p>{{ data_get($payload, 'submission.summary') }}</p>

  <h2>Descripción</h2>
  <div class="description">{!! data_get($payload, 'submission.description_html') !!}</div>

  <h2>Enlaces externos</h2>
  <ul>
    @forelse(data_get($payload, 'external_links', []) as $link)
      <li><strong>{{ $link['kind'] === 'youtube' ? 'Video del proyecto' : 'Carpeta pública del proyecto' }}:</strong> <a href="{{ $link['url'] }}">{{ $link['url'] }}</a></li>
    @empty
      <li>Sin enlaces externos.</li>
    @endforelse
  </ul>

  <h2>Anexos evaluables</h2>
  <ul>
    @forelse($package->files as $file)
      <li><strong>{{ $file->neutral_label }}</strong> — {{ $file->file_class->label() }}, {{ number_format($file->expected_size_bytes / 1024, 1) }} KiB.<br><a href="{{ route('judge.assignments.packages.files.download', [$assignment, $file]) }}">Descargar anexo con autenticación</a></li>
    @empty
      <li>Sin anexos capturados.</li>
    @endforelse
  </ul>
</body>
</html>
