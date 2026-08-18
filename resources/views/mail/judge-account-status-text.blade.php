FLOWER FLOW · FLORECE HERMOSILLO

@if($event === 'suspended')
Acceso suspendido

Tu acceso al área de juez fue suspendido y tus sesiones quedaron cerradas.
@elseif($event === 'reactivated')
Acceso reactivado

La suspensión de tu cuenta terminó. El acceso dependerá de que tu correo y contraseña propia estén completos.
@else
Acceso 2FA recuperado

El material de autenticación en dos pasos fue eliminado y tus sesiones quedaron cerradas. Puedes iniciar sesión y configurar 2FA nuevamente si lo deseas.
@endif

Si no reconoces esta acción, comunícate con el equipo responsable.

Contacto: {{ config('flowerflow.mail.reply_to') }}
