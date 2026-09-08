<div class="modal fade ff-voting-modal" id="public-voting-modal" tabindex="-1" aria-labelledby="public-voting-title" aria-describedby="public-voting-description" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title" id="public-voting-title">Vota por tu proyecto favorito</h2>
        <button class="ff-voting-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar votación">
          <span class="ff-landing-icon ri-close-line" aria-hidden="true"></span>
        </button>
      </div>
      <div class="modal-body">
        <p class="ff-voting-loading" role="status" data-voting-loading hidden>Cargando formulario de Google…</p>
        <iframe id="public-voting-frame" title="Formulario de votación de proyectos de Hermosillo" data-src="{{ config('flowerflow.voting.embed_url') }}" referrerpolicy="strict-origin-when-cross-origin" tabindex="0"></iframe>
      </div>
      <div class="modal-footer">
        <p id="public-voting-description">Google solicita iniciar sesión para responder.</p>
        <a class="ff-button ff-button-primary" href="{{ config('flowerflow.voting.form_url') }}" target="_blank" rel="noopener noreferrer">Abrir en Google<span class="visually-hidden"> (se abre en una pestaña nueva)</span></a>
      </div>
    </div>
  </div>
</div>
