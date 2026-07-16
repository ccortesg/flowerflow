import './bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

document.querySelectorAll('[data-flowerflow-editor]').forEach(editor => {
  const quill = new Quill(editor, {
    theme: 'snow',
    modules: { toolbar: [['bold', 'italic', 'underline'], [{ header: [2, 3, false] }], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']] }
  });
  const toolbar = editor.previousElementSibling;
  const labels = {
    '.ql-bold': 'Negritas',
    '.ql-italic': 'Cursivas',
    '.ql-underline': 'Subrayado',
    '.ql-list[value="ordered"]': 'Lista numerada',
    '.ql-list[value="bullet"]': 'Lista con viñetas',
    '.ql-link': 'Insertar enlace',
    '.ql-clean': 'Quitar formato',
    '.ql-header': 'Estilo de párrafo'
  };
  Object.entries(labels).forEach(([selector, label]) => {
    toolbar?.querySelector(selector)?.setAttribute('aria-label', label);
    toolbar?.querySelector(selector)?.setAttribute('title', label);
  });
  const headerLabels = {
    '2': 'Encabezado 2',
    '3': 'Encabezado 3',
    '': 'Normal'
  };
  toolbar?.querySelectorAll('.ql-header .ql-picker-item').forEach(item => {
    const label = headerLabels[item.dataset.value ?? ''];
    item.dataset.label = label;
    item.setAttribute('aria-label', label);
    item.setAttribute('title', label);
  });
  const linkInput = editor.querySelector('.ql-tooltip input[data-link]');
  linkInput?.setAttribute('aria-label', 'Dirección del enlace');
  linkInput?.setAttribute('placeholder', 'https://ejemplo.com');
  const form = editor.closest('form');
  const html = form.querySelector('[name="description_html"]');
  const delta = form.querySelector('[name="description_delta"]');
  const text = form.querySelector('[name="description_text"]');
  if (html?.value) quill.clipboard.dangerouslyPasteHTML(html.value);
  const sync = () => {
    html.value = quill.getSemanticHTML();
    delta.value = JSON.stringify(quill.getContents());
    text.value = quill.getText().trim();
  };
  quill.on('text-change', sync);
  form.addEventListener('submit', sync);
});

document.querySelectorAll('[data-confirm]').forEach(form => {
  form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  });
});

document.querySelectorAll('[data-team-toggle]').forEach(select => {
  const target = document.querySelector(select.dataset.teamToggle);
  const update = () => target?.classList.toggle('d-none', select.value !== 'team');
  select.addEventListener('change', update);
  update();
});

import.meta.glob([
  '../assets/img/**',
  // '../assets/json/**',
  '../assets/vendor/fonts/**'
]);
