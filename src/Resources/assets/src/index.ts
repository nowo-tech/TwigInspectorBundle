/**
 * Twig Inspector Bundle: template inspection overlay and interaction.
 *
 * Only runs when the Web Profiler toolbar is present; exits immediately otherwise to avoid consuming resources.
 * When enabled:
 * - Scans the document for Twig Inspector HTML comments (template/block and controller comments)
 * - Shows a highlight and tooltip over the element under the cursor with template (and controller) info
 * - Click on the overlay opens the template in the IDE (or shows a picker when multiple templates)
 * - Filter input restricts overlay to blocks whose template name or path matches (comma-separated)
 * - Ctrl+Shift+R rescans the DOM (e.g. after AJAX); Escape resets the overlay
 *
 * @module index
 */

import './style.scss';

import { getConfig, applyThemeAndAccessibility } from './config';
import { BlockStorage } from './BlockStorage';
import { Overlay } from './Overlay';
import { shortcutMatches } from './shortcut';

(function (): void {
  // Toolbar not present (e.g. prod, or script loaded without profiler): do nothing
  if (!document.querySelector('.sf-toolbar')) {
    return;
  }

  const config = getConfig();
  applyThemeAndAccessibility(config);

  const statusCheckbox = document.getElementById('_twig_inspector__status') as HTMLInputElement | null;
  if (!statusCheckbox) {
    return;
  }

  statusCheckbox.addEventListener('click', (event: Event) => {
    const target = event.target as HTMLInputElement;
    const isActive = target.checked === true;
    document.cookie = config.cookie_name + '=' + isActive + ';path=/';
    location.reload();
  });

  const storage = new BlockStorage();
  const statusIcon = document.getElementById('_twig_inspector__icon');
  if (!statusIcon) {
    return;
  }

  const overlay = new Overlay(storage, statusIcon);
  overlay.initClickHandler();

  document.addEventListener('keydown', (evt: KeyboardEvent) => {
    if (overlay.handleKeyDown(evt)) {
      evt.preventDefault();
      return;
    }
    if (shortcutMatches(config.keyboard_shortcut, evt)) {
      evt.preventDefault();
      const isActive = !statusCheckbox.checked;
      statusCheckbox.checked = isActive;
      document.cookie = config.cookie_name + '=' + isActive + ';path=/';
      location.reload();
      return;
    }
    if (evt.key === 'R' && evt.ctrlKey && evt.shiftKey) {
      evt.preventDefault();
      overlay.rescan();
    }
  });

  const filterContainer = document.getElementById('_twig_inspector__filter');
  if (filterContainer) {
    const filterInput = document.createElement('input');
    filterInput.type = 'text';
    filterInput.placeholder = 'name or path, comma-separated';
    filterInput.id = '_twig_inspector__filter_input';
    filterInput.setAttribute('aria-label', 'Filter by template name or path (comma-separated)');
    filterInput.addEventListener('input', () => {
      overlay.filterQuery = filterInput.value;
      overlay.onFilterChange();
    });
    filterContainer.appendChild(filterInput);
  }

  const rescanBtn = document.getElementById('_twig_inspector__rescan');
  if (rescanBtn) {
    rescanBtn.addEventListener('click', () => overlay.rescan());
  }

  if (statusCheckbox.checked === false) {
    return;
  }

  statusIcon.addEventListener('click', () => {
    if (overlay.isEnabled) {
      overlay.reset();
    } else {
      overlay.enable();
    }
  });

  overlay.enable();
})();
