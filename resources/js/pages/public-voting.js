import { Modal } from 'bootstrap';

const dialog = document.getElementById('public-voting-modal');
const frame = document.getElementById('public-voting-frame');
const triggers = [...document.querySelectorAll('[data-voting-trigger]')];

if (dialog && frame && triggers.length) {
  const modal = Modal.getOrCreateInstance(dialog);
  const closeButton = dialog.querySelector('[data-bs-dismiss="modal"]');
  const loading = dialog.querySelector('[data-voting-loading]');
  const backgroundStates = new Map();
  let returnTarget = null;
  let loadingTimer;

  const clearLoading = () => {
    window.clearTimeout(loadingTimer);
    if (loading) loading.hidden = true;
  };

  const isVisible = element => element?.getClientRects().length > 0 && !element.closest('[inert]');

  dialog.addEventListener('show.bs.modal', event => {
    returnTarget = event.relatedTarget ?? document.activeElement;

    const menuToggle = document.querySelector('[data-public-menu-toggle][aria-expanded="true"]');
    menuToggle?.click();

    if (!frame.hasAttribute('src')) {
      if (loading) loading.hidden = false;
      // A cross-origin load event is not proof of a working form or a recorded vote.
      frame.addEventListener('load', clearLoading, { once: true });
      loadingTimer = window.setTimeout(clearLoading, 10000);
      frame.src = frame.dataset.src;
    }
  });

  dialog.addEventListener('shown.bs.modal', () => {
    closeButton?.focus({ preventScroll: true });
    for (const element of document.body.children) {
      if (element === dialog || element.matches('script, style, link, .modal-backdrop')) continue;
      backgroundStates.set(element, element.inert);
      element.inert = true;
    }
  });

  dialog.addEventListener('hide.bs.modal', () => {
    // Avoid leaving focus inside the container as Bootstrap marks it aria-hidden.
    if (dialog.contains(document.activeElement)) document.activeElement.blur();
  });

  dialog.addEventListener('hidden.bs.modal', () => {
    for (const [element, wasInert] of backgroundStates) element.inert = wasInert;
    backgroundStates.clear();

    const target = isVisible(returnTarget) ? returnTarget : triggers.find(isVisible);
    target?.focus({ preventScroll: true });
  });

  triggers.forEach(trigger => {
    // Keep a normal HTTPS link until the enhancement has loaded successfully.
    trigger.setAttribute('role', 'button');
    trigger.setAttribute('aria-haspopup', 'dialog');
    trigger.setAttribute('aria-controls', dialog.id);
    trigger.addEventListener('click', event => {
      if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      modal.show(trigger);
    });
    trigger.addEventListener('keydown', event => {
      if (event.key !== ' ') return;
      event.preventDefault();
      trigger.click();
    });
  });
}
