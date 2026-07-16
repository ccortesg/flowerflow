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

const passwordRules = {
  length: value => [...value].length >= 8,
  lowercase: value => /\p{Ll}/u.test(value),
  uppercase: value => /\p{Lu}/u.test(value),
  number: value => /\p{N}/u.test(value),
  symbol: value => /[\p{Z}\p{S}\p{P}]/u.test(value)
};

document.querySelectorAll('[data-password-validation]').forEach(validation => {
  const password = validation.querySelector('[data-password-input]');
  const confirmation = validation.querySelector('[data-password-confirmation]');
  const matchMessage = validation.querySelector('[data-password-match]');

  const updateRule = (name, isValid, hasValue) => {
    const item = validation.querySelector(`[data-password-rule="${name}"]`);
    const icon = item?.querySelector('.ff-password-rule-icon');
    const status = item?.querySelector('[data-password-rule-status]');
    item?.classList.toggle('is-valid', hasValue && isValid);
    item?.classList.toggle('is-invalid', hasValue && !isValid);
    if (icon) icon.textContent = hasValue ? (isValid ? '✓' : '×') : '○';
    if (status) status.textContent = hasValue ? (isValid ? 'Cumplido: ' : 'Pendiente: ') : 'Sin evaluar: ';
  };

  const update = () => {
    const value = password?.value ?? '';
    const hasValue = value.length > 0;
    const results = Object.fromEntries(
      Object.entries(passwordRules).map(([name, validator]) => [name, validator(value)])
    );

    Object.entries(results).forEach(([name, isValid]) => updateRule(name, isValid, hasValue));

    const passwordIsValid = Object.values(results).every(Boolean);
    password?.setCustomValidity(hasValue && !passwordIsValid
      ? 'La contraseña debe cumplir todos los requisitos indicados.'
      : '');
    if (password) password.setAttribute('aria-invalid', hasValue && !passwordIsValid ? 'true' : 'false');

    const confirmationHasValue = (confirmation?.value ?? '').length > 0;
    const confirmationMatches = confirmationHasValue && confirmation.value === value;
    confirmation?.setCustomValidity(confirmationHasValue && !confirmationMatches
      ? 'Las contraseñas no coinciden.'
      : '');
    if (confirmation) confirmation.setAttribute('aria-invalid', confirmationHasValue && !confirmationMatches ? 'true' : 'false');

    matchMessage?.classList.toggle('is-valid', confirmationMatches);
    matchMessage?.classList.toggle('is-invalid', confirmationHasValue && !confirmationMatches);
    if (matchMessage) {
      matchMessage.textContent = confirmationMatches
        ? 'Las contraseñas coinciden.'
        : (confirmationHasValue ? 'Las contraseñas no coinciden.' : 'Confirma exactamente la misma contraseña.');
    }
  };

  password?.addEventListener('input', update);
  confirmation?.addEventListener('input', update);
  validation.closest('form')?.addEventListener('submit', update);
  update();
});

document.querySelectorAll('[data-password-toggle]').forEach(button => {
  button.addEventListener('click', () => {
    const input = button.parentElement?.querySelector('input');
    if (!input) return;
    const shouldShow = input.type === 'password';
    input.type = shouldShow ? 'text' : 'password';
    const label = button.querySelector('[data-password-toggle-label]');
    const icon = button.querySelector('[data-password-toggle-icon]');
    if (label) label.textContent = shouldShow ? 'Ocultar' : 'Mostrar';
    else button.textContent = shouldShow ? 'Ocultar' : 'Mostrar';
    icon?.classList.toggle('ri-eye-line', !shouldShow);
    icon?.classList.toggle('ri-eye-off-line', shouldShow);
    const fieldLabel = input.id === 'password_confirmation'
      ? 'confirmación de contraseña'
      : (input.id === 'current_password' ? 'contraseña actual' : 'contraseña');
    button.setAttribute('aria-label', `${shouldShow ? 'Ocultar' : 'Mostrar'} ${fieldLabel}`);
    button.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
  });
});

document.querySelectorAll('[data-phone-number]').forEach(component => {
  const input = component.querySelector('[data-phone-national]');
  const status = component.querySelector('[data-phone-status]');

  const update = () => {
    let digits = (input?.value ?? '').replace(/\D/g, '');
    if (digits.startsWith('52') && digits.length > 10) digits = digits.slice(2);
    digits = digits.slice(0, 10);

    const groups = [digits.slice(0, 3), digits.slice(3, 6), digits.slice(6, 10)].filter(Boolean);
    if (input) input.value = groups.join(' ');

    const hasValue = digits.length > 0;
    const isComplete = digits.length === 10;
    input?.setCustomValidity(hasValue && !isComplete ? 'Escribe los 10 dígitos de tu celular.' : '');
    if (input) input.setAttribute('aria-invalid', hasValue && !isComplete ? 'true' : 'false');

    status?.classList.toggle('is-valid', isComplete);
    status?.classList.toggle('is-invalid', hasValue && !isComplete);
    if (status) {
      status.textContent = isComplete
        ? `Número completo: +52 ${groups.join(' ')}`
        : (hasValue ? `Faltan ${10 - digits.length} dígitos.` : 'Escribe tu número celular completo.');
    }
  };

  input?.addEventListener('input', update);
  component.closest('form')?.addEventListener('submit', update);
  update();
});

document.querySelectorAll('[data-public-header]').forEach(header => {
  const toggle = header.querySelector('[data-public-menu-toggle]');
  const navigation = header.querySelector('[data-public-nav]');

  if (!toggle || !navigation) return;

  const closeMenu = ({ restoreFocus = false } = {}) => {
    navigation.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Abrir menú');
    document.body.classList.remove('ff-public-menu-open');
    if (restoreFocus) toggle.focus();
  };

  const openMenu = () => {
    navigation.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
    toggle.setAttribute('aria-label', 'Cerrar menú');
    document.body.classList.add('ff-public-menu-open');
  };

  toggle.addEventListener('click', () => {
    if (toggle.getAttribute('aria-expanded') === 'true') closeMenu();
    else openMenu();
  });

  navigation.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => closeMenu());
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
      closeMenu({ restoreFocus: true });
    }
  });

  document.addEventListener('click', event => {
    if (toggle.getAttribute('aria-expanded') === 'true' && !header.contains(event.target)) closeMenu();
  });

  window.addEventListener('resize', () => {
    if (window.matchMedia('(min-width: 62rem)').matches) closeMenu();
  });
});

document.querySelectorAll('[data-profile-form]').forEach(form => {
  const editButtons = [...form.querySelectorAll('[data-profile-edit]')];
  const cancelButton = form.querySelector('[data-profile-cancel]');
  let lastEditButton = editButtons[0];

  const startEditing = button => {
    lastEditButton = button;
    form.classList.add('is-editing');
    const input = document.getElementById(button.dataset.profileFocus);
    window.requestAnimationFrame(() => input?.focus());
  };

  editButtons.forEach(button => {
    button.addEventListener('click', () => startEditing(button));
  });

  cancelButton?.addEventListener('click', () => {
    form.reset();
    form.classList.remove('is-editing');
    window.requestAnimationFrame(() => lastEditButton?.focus());
  });
});

document.querySelectorAll('[data-submissions-browser]').forEach(browser => {
  const search = browser.querySelector('[data-submissions-search]');
  const status = browser.querySelector('[data-submissions-status]');
  const clear = browser.querySelector('[data-submissions-clear]');
  const count = browser.querySelector('[data-submissions-count]');
  const empty = browser.querySelector('[data-submissions-empty-filter]');
  const items = [...browser.querySelectorAll('[data-submission-item]')];

  const normalize = value => value
    .toLocaleLowerCase('es-MX')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim();

  const update = () => {
    const query = normalize(search?.value ?? '');
    const selectedStatus = status?.value ?? '';
    let visible = 0;

    items.forEach(item => {
      const matchesText = !query || normalize(item.textContent).includes(query);
      const matchesStatus = !selectedStatus || item.dataset.submissionStatus === selectedStatus;
      const shouldShow = matchesText && matchesStatus;
      item.hidden = !shouldShow;
      if (shouldShow) visible += 1;
    });

    if (count) count.textContent = String(visible);
    if (empty) empty.hidden = visible !== 0;
  };

  search?.addEventListener('input', update);
  status?.addEventListener('change', update);
  clear?.addEventListener('click', () => {
    if (search) search.value = '';
    if (status) status.value = '';
    update();
    search?.focus();
  });
});

document.querySelectorAll('.ff-login-menu-toggle').forEach(toggle => {
  const target = document.querySelector(toggle.dataset.bsTarget);
  target?.addEventListener('shown.bs.collapse', () => toggle.setAttribute('aria-label', 'Cerrar navegación'));
  target?.addEventListener('hidden.bs.collapse', () => toggle.setAttribute('aria-label', 'Abrir navegación'));
});

import.meta.glob([
  '../assets/img/**',
  // '../assets/json/**',
  '../assets/vendor/fonts/**'
]);
