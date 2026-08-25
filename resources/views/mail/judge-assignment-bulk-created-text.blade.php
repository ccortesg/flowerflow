FLOWER FLOW · FLORECE HERMOSILLO

Nuevas asignaciones de evaluación

Hola:

Se agregaron {{ $assignmentCount }} {{ $assignmentCount === 1 ? 'asignación' : 'asignaciones' }} a tu área de evaluación.

Distribución por categoría:
@foreach($categories as $category => $count)
- {{ $category }}: {{ $count }}
@endforeach

{{ $dueDates->count() === 1 ? 'Plazo' : 'Plazos' }}:
@foreach($dueDates as $dueAt)
- {{ $dueAt }} (hora de Hermosillo)
@endforeach

Ver mis asignaciones: {{ $actionUrl }}

Inicia sesión para consultar cada paquete ciego. Este correo no incluye participantes, títulos, contenido, anexos, motivos ni otros jueces.
