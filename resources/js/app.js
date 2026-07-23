import './bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

document.documentElement.classList.add('ff-js');

document.querySelectorAll('[data-flowerflow-editor]').forEach(editor => {
  const quill = new Quill(editor, {
    theme: 'snow',
    placeholder: editor.dataset.placeholder ?? '',
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
  const counter = form.querySelector('[data-editor-counter]');
  const maximum = Number(editor.dataset.max ?? 0);
  const editable = editor.querySelector('.ql-editor');
  editable?.setAttribute('role', 'textbox');
  editable?.setAttribute('aria-multiline', 'true');
  if (html?.value) quill.clipboard.dangerouslyPasteHTML(html.value);
  const sync = () => {
    html.value = quill.getSemanticHTML();
    delta.value = JSON.stringify(quill.getContents());
    text.value = quill.getText().trim();
    const length = [...text.value].length;
    if (counter) counter.textContent = `${length} / ${maximum}`;
    const exceedsLimit = maximum > 0 && length > maximum;
    editable?.setAttribute('aria-invalid', exceedsLimit ? 'true' : 'false');
    editor.dataset.editorInvalid = exceedsLimit ? 'true' : 'false';
  };
  quill.on('text-change', () => {
    sync();
    form.dispatchEvent(new CustomEvent('flowerflow:changed'));
  });
  form.addEventListener('submit', sync);
  sync();
});

document.querySelectorAll('[data-confirm]').forEach(form => {
  form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  });
});

document.querySelectorAll('[data-team-choice]').forEach(group => {
  const target = group.closest('form')?.querySelector('[data-team-fields]');
  const controls = [...(target?.querySelectorAll('input, select, textarea') ?? [])];
  const update = () => {
    const isTeam = group.querySelector('[name="participation_type"]:checked')?.value === 'team';
    if (target) target.hidden = !isTeam;
    controls.forEach(control => {
      control.disabled = !isTeam;
    });
  };
  group.addEventListener('change', update);
  update();
});

document.querySelectorAll('[data-character-counter]').forEach(counter => {
  const input = document.getElementById(counter.dataset.for);
  const maximum = Number(counter.dataset.max ?? input?.maxLength ?? 0);
  const update = () => {
    const length = [...(input?.value ?? '')].length;
    counter.textContent = `${length} / ${maximum}`;
  };
  input?.addEventListener('input', update);
  update();
});

document.querySelectorAll('[data-wizard-form]').forEach(form => {
  const status = form.querySelector('[data-wizard-save-status]');
  const quotaProgress = form.querySelector('[data-quota-progress]');
  const quotaText = form.querySelector('[data-quota-text]');
  const quotaError = form.querySelector('[data-quota-error]');
  const existingBytes = Number(form.dataset.existingBytes ?? 0);
  const quotaBytes = Number(form.dataset.quotaBytes ?? 0);
  const pickers = [...form.querySelectorAll('[data-file-picker]')];
  let dirty = false;
  let submitting = false;

  const setDirty = () => {
    if (submitting || dirty) return;
    dirty = true;
    status?.classList.add('is-dirty');
    if (status) status.textContent = 'Cambios sin guardar';
  };

  const selectedBytes = () => pickers.reduce((total, picker) => {
    const input = picker.querySelector('[data-file-input]');
    return total + [...(input?.files ?? [])].reduce((sum, file) => sum + file.size, 0);
  }, 0);

  const updateQuota = () => {
    const total = existingBytes + selectedBytes();
    if (quotaProgress) quotaProgress.value = Math.min(total, quotaBytes);
    if (quotaText) quotaText.textContent = `${(total / 1048576).toFixed(2)} de ${(quotaBytes / 1048576).toFixed(0)} MiB`;
    const exceeded = quotaBytes > 0 && total > quotaBytes;
    if (quotaError) quotaError.hidden = !exceeded;
    return !exceeded;
  };

  pickers.forEach(picker => {
    const input = picker.querySelector('[data-file-input]');
    const dropzone = picker.querySelector('[data-file-dropzone]');
    const list = picker.querySelector('[data-file-list]');
    const previewUrls = new Map();
    let selectedFiles = [...(input?.files ?? [])];

    const assignFiles = () => {
      if (!input || typeof DataTransfer === 'undefined') return false;
      const transfer = new DataTransfer();
      selectedFiles.forEach(file => transfer.items.add(file));
      input.files = transfer.files;
      return true;
    };

    const fileKey = file => `${file.name}:${file.size}:${file.lastModified}`;
    const formatSize = bytes => bytes >= 1048576
      ? `${(bytes / 1048576).toFixed(2)} MiB`
      : `${Math.max(1, Math.round(bytes / 1024))} KiB`;

    const render = () => {
      previewUrls.forEach(url => URL.revokeObjectURL(url));
      previewUrls.clear();
      list?.replaceChildren();

      selectedFiles.forEach((file, index) => {
        const item = document.createElement('li');
        if (picker.dataset.kind === 'image' && file.type.startsWith('image/')) {
          const image = document.createElement('img');
          const url = URL.createObjectURL(file);
          previewUrls.set(fileKey(file), url);
          image.src = url;
          image.alt = '';
          item.append(image);
        } else {
          const icon = document.createElement('span');
          icon.className = 'ri ri-file-text-line';
          icon.setAttribute('aria-hidden', 'true');
          item.append(icon);
        }

        const details = document.createElement('span');
        const name = document.createElement('strong');
        const size = document.createElement('small');
        name.textContent = file.name;
        size.textContent = formatSize(file.size);
        details.append(name, size);

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.setAttribute('aria-label', `Quitar ${file.name}`);
        const removeIcon = document.createElement('span');
        removeIcon.className = 'ri ri-delete-bin-line';
        removeIcon.setAttribute('aria-hidden', 'true');
        const removeText = document.createElement('span');
        removeText.textContent = 'Quitar';
        remove.append(removeIcon, removeText);
        remove.addEventListener('click', () => {
          selectedFiles.splice(index, 1);
          assignFiles();
          render();
          updateQuota();
          setDirty();
        });

        item.append(details, remove);
        list?.append(item);
      });
    };

    const addFiles = files => {
      const known = new Set(selectedFiles.map(fileKey));
      files.forEach(file => {
        if (!known.has(fileKey(file))) {
          selectedFiles.push(file);
          known.add(fileKey(file));
        }
      });
      if (!assignFiles()) return;
      render();
      updateQuota();
      setDirty();
    };

    input?.addEventListener('change', () => {
      selectedFiles = [...input.files];
      render();
      updateQuota();
      setDirty();
    });

    ['dragenter', 'dragover'].forEach(eventName => {
      dropzone?.addEventListener(eventName, event => {
        event.preventDefault();
        dropzone.classList.add('is-dragging');
      });
    });
    ['dragleave', 'drop'].forEach(eventName => {
      dropzone?.addEventListener(eventName, event => {
        event.preventDefault();
        dropzone.classList.remove('is-dragging');
      });
    });
    dropzone?.addEventListener('drop', event => addFiles([...event.dataTransfer.files]));
    render();
  });

  const youtubeInput = form.querySelector('[data-youtube-url]');
  const youtubePreview = form.querySelector('[data-youtube-preview]');

  const youtubeId = value => {
    if (!value) return null;
    try {
      const url = new URL(value);
      const hosts = (youtubeInput?.dataset.allowedHosts ?? '').split(',').filter(Boolean);
      if (url.protocol !== 'https:' || url.username || url.password || !hosts.includes(url.hostname.toLowerCase())) return null;
      const id = url.hostname.toLowerCase() === 'youtu.be'
        ? url.pathname.split('/').filter(Boolean)[0]
        : (url.pathname === '/watch' ? url.searchParams.get('v') : url.pathname.match(/^\/(?:embed|shorts)\/([^/]+)/)?.[1]);
      return id && /^[A-Za-z0-9_-]{11}$/.test(id) ? id : null;
    } catch {
      return null;
    }
  };

  const updateYoutubePreview = () => {
    if (!youtubePreview) return;
    youtubePreview.replaceChildren();
    const value = youtubeInput?.value.trim() ?? '';
    const id = youtubeId(value);
    if (id) {
      const iframe = document.createElement('iframe');
      iframe.src = `https://www.youtube-nocookie.com/embed/${id}`;
      iframe.title = 'Vista previa del video de YouTube';
      iframe.loading = 'lazy';
      iframe.referrerPolicy = 'strict-origin-when-cross-origin';
      iframe.allow = 'accelerometer; encrypted-media; gyroscope; picture-in-picture';
      iframe.allowFullscreen = true;
      youtubePreview.append(iframe);
      return;
    }
    const message = document.createElement('p');
    message.textContent = value
      ? 'Ingresa un enlace HTTPS válido de YouTube para mostrar la vista previa.'
      : 'La vista previa aparecerá aquí cuando ingreses un enlace válido de YouTube.';
    message.classList.toggle('is-invalid', Boolean(value));
    youtubePreview.append(message);
  };

  youtubeInput?.addEventListener('input', updateYoutubePreview);
  updateYoutubePreview();

  form.addEventListener('input', setDirty);
  form.addEventListener('change', setDirty);
  form.addEventListener('flowerflow:changed', setDirty);
  updateQuota();

  form.closest('.ff-wizard-page')?.querySelectorAll('[data-wizard-navigation]').forEach(link => {
    link.addEventListener('click', event => {
      if (dirty && !window.confirm('Tienes cambios sin guardar. ¿Deseas salir de este paso?')) event.preventDefault();
    });
  });

  form.addEventListener('submit', event => {
    const action = event.submitter?.value ?? 'save';
    const editor = form.querySelector('[data-flowerflow-editor]');
    const description = form.querySelector('[name="description_text"]');
    if (action === 'continue' && editor && !(description?.value.trim())) {
      event.preventDefault();
      window.alert('Escribe la descripción detallada antes de continuar.');
      editor.querySelector('.ql-editor')?.focus();
      return;
    }
    if (editor?.dataset.editorInvalid === 'true') {
      event.preventDefault();
      window.alert('La descripción supera el máximo de caracteres permitido.');
      editor.querySelector('.ql-editor')?.focus();
      return;
    }
    if (!updateQuota()) {
      event.preventDefault();
      quotaError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }
    submitting = true;
    dirty = false;
    status?.classList.remove('is-dirty');
    status?.classList.add('is-saving');
    if (status) status.textContent = 'Guardando...';
  });

  window.addEventListener('beforeunload', event => {
    if (!dirty || submitting) return;
    event.preventDefault();
    event.returnValue = '';
  });
});

document.querySelector('[data-error-summary]')?.focus();

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
