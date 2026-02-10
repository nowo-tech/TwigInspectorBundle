/**
 * Overlay UI: highlight block and tooltip; mouse move to show, click to open template in IDE.
 * Hides when hovering over the Symfony toolbar.
 */

import type { Block } from './types';
import { BlockStorage } from './BlockStorage';
import { blockMatchesFilter } from './filterMatch';

export class Overlay {
  /** Whether the overlay is currently tracking mouse and showing tooltips. */
  public isEnabled: boolean = false;
  /** Highlight div positioned over the focused element. */
  public block: HTMLDivElement;
  /** Tooltip div showing template name(s). */
  public info: HTMLDivElement;
  /** Layer that contains one div per block matching the filter (persistent highlight). */
  private filterHighlightLayer: HTMLDivElement;
  private scrollResizeThrottle: ReturnType<typeof setTimeout> | null = null;
  private lastFocusedElement: HTMLElement | null = null;
  /** Filter string: only show overlay when a template name contains this (case-insensitive). */
  public filterQuery: string = '';

  /**
   * @param storage - Block storage (element–template map).
   * @param statusIcon - Toolbar icon element (used to toggle green/yellow state).
   */
  constructor(
    private storage: BlockStorage,
    private statusIcon: HTMLElement
  ) {
    this.block = document.createElement('DIV');
    this.block.id = '_twig_inspector__overlay__block';
    document.body.appendChild(this.block);

    this.info = document.createElement('DIV');
    this.info.id = '_twig_inspector__overlay__info';
    document.body.appendChild(this.info);

    this.filterHighlightLayer = document.createElement('DIV');
    this.filterHighlightLayer.id = '_twig_inspector__filter_highlights';
    document.body.appendChild(this.filterHighlightLayer);

    window.addEventListener('scroll', this.onScrollResize, { passive: true });
    window.addEventListener('resize', this.onScrollResize);
  }

  private onScrollResize = (): void => {
    if (this.scrollResizeThrottle !== null) return;
    this.scrollResizeThrottle = setTimeout(() => {
      this.scrollResizeThrottle = null;
      this.updateFilterHighlights();
    }, 50);
  };

  /** Returns true if the block matches the current filter (delegates to blockMatchesFilter). */
  matchesFilter(layoutItem: Block): boolean {
    return blockMatchesFilter(layoutItem, this.filterQuery);
  }

  /**
   * Draws or clears the persistent highlight boxes around blocks that match the current filter.
   * When filter is non-empty, each matching block gets a colored frame (veil); when empty, frames are removed.
   */
  updateFilterHighlights(): void {
    this.filterHighlightLayer.innerHTML = '';
    const q = this.filterQuery.trim();
    if (!q) {
      return;
    }
    const blocks = this.storage.getAllBlocks();
    for (let i = 0; i < blocks.length; i++) {
      const layoutItem = blocks[i];
      if (!this.matchesFilter(layoutItem)) continue;
      const el = layoutItem.element;
      const rect = el.getBoundingClientRect();
      const top = rect.top + window.scrollY;
      const left = rect.left + window.scrollX;
      const box = document.createElement('DIV');
      box.className = '_twig_inspector__filter_highlight';
      box.style.top = top + 'px';
      box.style.left = left + 'px';
      box.style.width = rect.width + 'px';
      box.style.height = rect.height + 'px';
      this.filterHighlightLayer.appendChild(box);
    }
  }

  /** Hides the overlay block and info tooltip. */
  hide(): void {
    this.info.classList.remove('_twig_inspector__visible');
    this.block.classList.remove('_twig_inspector__visible');
  }

  /**
   * Call when the filter text changes: hides the overlay and clears last focused element
   * so the next mousemove re-evaluates which block (if any) matches the new filter.
   */
  onFilterChange(): void {
    this.lastFocusedElement = null;
    this.hide();
    this.updateFilterHighlights();
  }

  /**
   * Positions the overlay and tooltip over the given block and shows them.
   * @param layoutItem - Block to show (element + templates).
   */
  show(layoutItem: Block): void {
    const element = layoutItem.element;

    const width = element.offsetWidth;
    const height = element.offsetHeight;
    const left = element.getBoundingClientRect().left;
    const top = element.getBoundingClientRect().top + window.scrollY;

    this.block.style.width = width + 'px';
    this.block.style.height = height + 'px';
    this.block.style.left = left + 'px';
    this.block.style.top = top + 'px';

    this.block.dataset.templateIndex = layoutItem.index.toString();

    this.info.innerHTML = layoutItem.toString();

    if (top + height + 50 < window.innerHeight + window.scrollY) {
      this.info.style.top = top + height + 2 + 'px';
    } else {
      this.info.style.top = top - this.info.offsetHeight - 2 + 'px';
    }

    if (left + this.info.offsetWidth < window.innerWidth) {
      this.info.style.left = left + 'px';
      this.info.style.right = 'auto';
    } else {
      this.info.style.left = 'auto';
      this.info.style.right = '0';
    }

    this.block.classList.add('_twig_inspector__visible');
    this.info.classList.add('_twig_inspector__visible');
  }

  /**
   * Stops tracking mouse and hides the info panel.
   * Block stays visible so the user can click to open the template.
   */
  freeze(): void {
    this.info.classList.remove('_twig_inspector__visible');
    document.body.removeEventListener('mousemove', this.onMouseMove);
  }

  /**
   * Re-scans the DOM for Twig Inspector comments (e.g. after AJAX or ESI content loaded).
   * No-op if the overlay is not enabled.
   */
  rescan(): void {
    if (this.isEnabled) {
      this.storage.collectData();
      this.updateFilterHighlights();
    }
  }

  /** Enables mouse tracking and shows the overlay on hover; updates toolbar icon to green. */
  enable(): void {
    document.body.addEventListener('mousemove', this.onMouseMove);
    this.isEnabled = true;

    this.storage.collectData();
    this.updateFilterHighlights();
    this.statusIcon.classList.add('sf-toolbar-status-green');
    this.statusIcon.classList.remove('sf-toolbar-status-yellow');
  }

  /** Disables overlay, clears state, removes static picker, resets toolbar icon to yellow. */
  reset(): void {
    this.freeze();
    this.info.classList.remove('_twig_inspector__visible');
    this.block.classList.remove('_twig_inspector__visible');
    this.block.classList.remove('_twig_inspector__overlay__block_static');
    this.block.innerHTML = '';
    this.filterHighlightLayer.innerHTML = '';
    this.statusIcon.classList.remove('sf-toolbar-status-green');
    this.statusIcon.classList.add('sf-toolbar-status-yellow');
    this.isEnabled = false;
  }

  /** Mouse move handler: find element under cursor, show overlay if it has templates and matches filter. */
  private onMouseMove = (event: MouseEvent): void => {
    const sfToolbar = document.getElementsByClassName('sf-toolbar')[0] as HTMLElement;
    const elements = document.elementsFromPoint(event.clientX, event.clientY);

    for (let i = 0; i < elements.length; i++) {
      const element = elements[i] as HTMLElement;
      if (sfToolbar.contains(element)) {
        this.hide();
        return;
      }
      const layoutItem = this.storage.find(element);

      if (null !== layoutItem && this.matchesFilter(layoutItem)) {
        if (this.lastFocusedElement === element) {
          return;
        }
        this.lastFocusedElement = element;
        return this.show(layoutItem);
      }
    }

    this.lastFocusedElement = null;
    this.hide();
  };

  /**
   * Binds click on the overlay: single template navigates to link; multiple show a static picker.
   * Entries with link '#' (e.g. controller-only blocks) do not navigate.
   */
  initClickHandler(): void {
    this.block.addEventListener('click', (event: MouseEvent) => {
      if (this.block.classList.contains('_twig_inspector__overlay__block_static')) {
        return;
      }

      const templateIndex = this.block.dataset.templateIndex;
      if (!templateIndex) {
        return;
      }

      const templates = this.storage.getTemplates(parseInt(templateIndex, 10));

      if (templates.length === 1) {
        const link = templates[0].link;
        if (link && link !== '#') {
          this.reset();
          window.location.href = link;
        }
        event.stopPropagation();
      } else {
        for (let i = 0; i < templates.length; i++) {
          const template = templates[i];

          const link = document.createElement('div');
          link.dataset.href = template.link;
          link.innerText = template.name;
          link.addEventListener('click', (event: MouseEvent) => {
            const href = (event.currentTarget as HTMLElement).dataset.href || '';
            this.reset();
            if (href && href !== '#') {
              window.location.href = href;
            }
            event.stopPropagation();
          });
          this.block.appendChild(link);
          this.block.classList.add('_twig_inspector__overlay__block_static');
        }
        this.block.style.left = event.clientX - 20 + 'px';
        this.block.style.right = 'auto';
        this.block.style.top = event.clientY + window.scrollY - 20 + 'px';
      }

      this.freeze();
      event.stopPropagation();
    });
  }

  /**
   * Handles Escape to reset the overlay. Called from the global keydown listener.
   * @param evt - Keyboard event.
   * @returns True if Escape was handled (caller should preventDefault).
   */
  handleKeyDown(evt: KeyboardEvent): boolean {
    if (evt.key === 'Escape' || evt.keyCode === 27) {
      this.reset();
      return true;
    }
    return false;
  }
}
