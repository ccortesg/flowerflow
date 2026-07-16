<footer class="ff-public-footer">
  <div class="ff-landing-container ff-footer-grid">
    <div>
      <a class="ff-brand-lockup ff-brand-lockup-footer" href="{{ route('landing') }}" aria-label="Flower Flow y Florece Hermosillo, página de inicio">
        <img src="{{ asset('assets/flowerflow/logo_flowerflow_transparente.png') }}" width="320" height="320" alt="Flower Flow">
        <span class="ff-brand-divider" aria-hidden="true"></span>
        <img src="{{ asset('assets/flowerflow/logo_florecehermosillo_transparente.png') }}" width="320" height="320" alt="Florece Hermosillo">
      </a>
      <p class="ff-footer-description">Ideas ciudadanas para ayudar a que Hermosillo florezca.</p>
    </div>
    <div>
      <h2 class="ff-footer-heading">Convocatoria</h2>
      <ul class="ff-footer-links">
        <li><a href="#categorias">Categorías</a></li>
        <li><a href="#como-participar">Cómo participar</a></li>
        <li><a href="#documentos">Documentos oficiales</a></li>
      </ul>
    </div>
    <div>
      <h2 class="ff-footer-heading">Contacto</h2>
      <ul class="ff-footer-links">
        <li><a href="mailto:convocatoria@flowerflow.com.mx">convocatoria@flowerflow.com.mx</a></li>
        <li><a href="mailto:privacidad@flowerflow.com.mx">privacidad@flowerflow.com.mx</a></li>
        <li>Hermosillo, Sonora, México</li>
      </ul>
    </div>
  </div>
  <div class="ff-landing-container ff-footer-bottom">
    <p>© {{ now()->year }} Flower Flow. Todos los derechos reservados.</p>
    <div>
      <a href="{{ asset('documentos/2026/02_Terminos_y_Condiciones_Plataforma_Flower_Flow_2026.pdf') }}">Términos y condiciones</a>
      <a href="{{ asset('documentos/2026/03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026.pdf') }}">Aviso de privacidad</a>
    </div>
  </div>
</footer>
