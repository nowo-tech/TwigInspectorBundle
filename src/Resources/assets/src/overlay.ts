/**
 * Overlay UI: highlight block and tooltip; mouse move to show, click to open template in IDE.
 * Hides when hovering over the Symfony toolbar.
 */

import type { Block, Template } from './types';
import { BlockStorage } from './block-storage';
import { blockMatchesFilter } from './filter-match';
import { getLogger } from './logger';

/**
 * Overlay that shows which Twig template (or controller) rendered the element under the cursor.
 * Provides a highlight box, tooltip with template names, and click-to-open in IDE.
 */
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
   * Creates the overlay and appends highlight/tooltip divs to the document body.
   *
   * @param storage - Block storage (element–template map) populated by scanning HTML comments
   * @param statusIcon - Toolbar icon element (used to toggle green/yellow state when overlay is enabled/disabled)
   */
  constructor(
    private storage: BlockStorage,
    private statusIcon: HTMLElement
  ) {
    this.mountOverlayNodes();
    window.addEventListener('scroll', this.onScrollResize, { passive: true });
    window.addEventListener('resize', this.onScrollResize);
  }

  /** Creates highlight, tooltip, and filter layers and appends them to `document.body`. */
  private mountOverlayNodes(): void {
    this.block = document.createElement('div');
    this.block.id = '_twig_inspector__overlay__block';
    document.body.appendChild(this.block);

    this.info = document.createElement('div');
    this.info.id = '_twig_inspector__overlay__info';
    document.body.appendChild(this.info);

    this.filterHighlightLayer = document.createElement('div');
    this.filterHighlightLayer.id = '_twig_inspector__filter_highlights';
    document.body.appendChild(this.filterHighlightLayer);
  }

  private onScrollResize = (): void => {
    if (this.scrollResizeThrottle !== null) return;
    this.scrollResizeThrottle = setTimeout(() => {
      this.scrollResizeThrottle = null;
      this.updateFilterHighlights();
    }, 50);
  };

  /**
   * Returns true if the block matches the current filter (delegates to blockMatchesFilter).
   *
   * @param layoutItem - Block (element + templates) to test
   * @returns True if the block matches this.filterQuery (or filter is empty)
   */
  matchesFilter(layoutItem: Block): boolean {
    return blockMatchesFilter(layoutItem, this.filterQuery);
  }

  /**
   * Draws or clears the persistent highlight boxes around blocks that match the current filter.
   * When filter is non-empty, each matching block gets a colored frame (veil); when empty, frames are removed.
   *
   * @returns void
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
      this.filterHighlightLayer.appendChild(this.createFilterHighlightBox(layoutItem.element));
    }
  }

  private createFilterHighlightBox(el: HTMLElement): HTMLDivElement {
    const rect = el.getBoundingClientRect();
    const box = document.createElement('div');
    box.className = '_twig_inspector__filter_highlight';
    box.style.top = rect.top + window.scrollY + 'px';
    box.style.left = rect.left + window.scrollX + 'px';
    box.style.width = rect.width + 'px';
    box.style.height = rect.height + 'px';
    return box;
  }

  /**
   * Hides the overlay block and info tooltip (keeps highlight layer unchanged).
   *
   * @returns void
   */
  hide(): void {
    this.info.classList.remove('_twig_inspector__visible');
    this.block.classList.remove('_twig_inspector__visible');
  }

  /**
   * Call when the filter text changes: hides the overlay and clears last focused element
   * so the next mousemove re-evaluates which block (if any) matches the new filter.
   *
   * @returns void
   */
  onFilterChange(): void {
    this.lastFocusedElement = null;
    this.hide();
    this.updateFilterHighlights();
    getLogger().debug('Filter changed', { filterQuery: this.filterQuery });
  }

  /**
   * Positions the overlay and tooltip over the given block and shows them.
   *
   * @param layoutItem - Block to show (element + templates)
   * @returns void
   */
  show(layoutItem: Block): void {
    const element = layoutItem.element;
    const rect = element.getBoundingClientRect();
    const topDocument = rect.top + window.scrollY;

    this.applyHighlightLayout(element.offsetWidth, element.offsetHeight, rect.left, topDocument);
    this.block.dataset.templateIndex = layoutItem.index.toString();
    this.info.innerHTML = layoutItem.toString();
    this.placeTooltipVertically(topDocument, element.offsetHeight);
    this.placeTooltipHorizontally(rect.left);

    this.block.classList.add('_twig_inspector__visible');
    this.info.classList.add('_twig_inspector__visible');
  }

  private applyHighlightLayout(
    width: number,
    height: number,
    left: number,
    top: number,
  ): void {
    this.block.style.width = width + 'px';
    this.block.style.height = height + 'px';
    this.block.style.left = left + 'px';
    this.block.style.top = top + 'px';
  }

  /** Places the tooltip below the block when there is room, otherwise above (document coordinates). */
  private placeTooltipVertically(elementTop: number, elementHeight: number): void {
    const viewportBottom = window.innerHeight + window.scrollY;
    const fitsBelow = elementTop + elementHeight + 50 < viewportBottom;
    if (fitsBelow) {
      this.info.style.top = elementTop + elementHeight + 2 + 'px';
    } else {
      this.info.style.top = elementTop - this.info.offsetHeight - 2 + 'px';
    }
  }

  /** Keeps the tooltip inside the viewport horizontally. */
  private placeTooltipHorizontally(elementLeft: number): void {
    if (elementLeft + this.info.offsetWidth < window.innerWidth) {
      this.info.style.left = elementLeft + 'px';
      this.info.style.right = 'auto';
    } else {
      this.info.style.left = 'auto';
      this.info.style.right = '0';
    }
  }

  /**
   * Stops tracking mouse and hides the info panel.
   * Block stays visible so the user can click to open the template.
   *
   * @returns void
   */
  freeze(): void {
    this.info.classList.remove('_twig_inspector__visible');
    document.body.removeEventListener('mousemove', this.onMouseMove);
  }

  /**
   * Re-scans the DOM for Twig Inspector comments (e.g. after AJAX or ESI content loaded).
   * No-op if the overlay is not enabled.
   *
   * @returns void
   */
  rescan(): void {
    if (this.isEnabled) {
      getLogger().info('Rescanning DOM for Twig Inspector comments');
      this.storage.collectData();
      this.updateFilterHighlights();
    }
  }

  /**
   * Enables mouse tracking and shows the overlay on hover; updates toolbar icon to green.
   *
   * @returns void
   */
  enable(): void {
    document.body.addEventListener('mousemove', this.onMouseMove);
    this.isEnabled = true;

    this.storage.collectData();
    this.updateFilterHighlights();
    this.setToolbarIconEnabled();
    getLogger().info('Overlay enabled');
  }

  private setToolbarIconEnabled(): void {
    this.statusIcon.classList.add('sf-toolbar-status-green');
    this.statusIcon.classList.remove('sf-toolbar-status-yellow');
  }

  /**
   * Disables overlay, clears state, removes static picker, resets toolbar icon to yellow.
   *
   * @returns void
   */
  reset(): void {
    this.freeze();
    this.info.classList.remove('_twig_inspector__visible');
    this.block.classList.remove('_twig_inspector__visible');
    this.block.classList.remove('_twig_inspector__overlay__block_static');
    this.clearBlockAndFilterLayers();
    this.setToolbarIconDisabled();
    this.isEnabled = false;
    getLogger().debug('Overlay reset');
  }

  private clearBlockAndFilterLayers(): void {
    this.block.innerHTML = '';
    this.filterHighlightLayer.innerHTML = '';
  }

  private setToolbarIconDisabled(): void {
    this.statusIcon.classList.remove('sf-toolbar-status-green');
    this.statusIcon.classList.add('sf-toolbar-status-yellow');
  }

  /**
   * Mouse move handler: finds the topmost element under the cursor, looks it up in storage,
   * and shows the overlay (highlight + tooltip) if the block has templates and matches the filter.
   * Hides the overlay when the cursor is over the Symfony toolbar.
   */
  private onMouseMove = (event: MouseEvent): void => {
    const sfToolbar = document.getElementsByClassName('sf-toolbar')[0] as HTMLElement;
    const elements = document.elementsFromPoint(event.clientX, event.clientY);

    for (let i = 0; i < elements.length; i++) {
      const element = elements[i] as HTMLElement;
      if (sfToolbar.contains(element)) {
        this.hide();
        return;
      }
      if (this.tryShowBlockUnderCursor(element)) {
        return;
      }
    }

    this.lastFocusedElement = null;
    this.hide();
  };

  /**
   * If the element maps to a filtered block, updates focus and shows the overlay.
   * @returns True when the event chain should stop (toolbar pass or block handled).
   */
  private tryShowBlockUnderCursor(element: HTMLElement): boolean {
    const layoutItem = this.storage.find(element);
    if (layoutItem === null || !this.matchesFilter(layoutItem)) {
      return false;
    }
    if (this.lastFocusedElement === element) {
      return true;
    }
    this.lastFocusedElement = element;
    this.show(layoutItem);
    return true;
  }

  /**
   * Binds click on the overlay: single template navigates to link; multiple show a static picker.
   * Entries with link '#' (e.g. controller-only blocks) do not navigate.
   *
   * @returns void
   */
  initClickHandler(): void {
    this.block.addEventListener('click', (event: MouseEvent) => this.handleBlockClick(event));
  }

  /**
   * Handles click on the overlay block: single template navigates; multiple show picker.
   * @internal Used by initClickHandler; extracted for testability and coverage.
   */
  handleBlockClick(event: MouseEvent): void {
    if (this.block.classList.contains('_twig_inspector__overlay__block_static')) {
      return;
    }

    const templates = this.templatesFromOverlayDataset();
    if (templates === null) {
      /* c8 ignore next 2 -- early return; v8 underreports when reached via event */
      return;
    }

    if (templates.length === 1) {
      this.navigateSingleTemplate(templates[0].link, event);
    } else {
      this.openMultiTemplatePicker(templates, event);
    }

    this.freeze();
    event.stopPropagation();
  }

  /** Resolves templates for the current overlay block from `data-template-index`, or null if missing. */
  private templatesFromOverlayDataset(): Template[] | null {
    const templateIndex = this.block.dataset.templateIndex;
    if (!templateIndex) {
      return null;
    }
    return this.storage.getTemplates(parseInt(templateIndex, 10));
  }

  private navigateSingleTemplate(link: string, event: MouseEvent): void {
    if (link && link !== '#') {
      this.reset();
      window.location.href = link;
    }
    event.stopPropagation();
  }

  private openMultiTemplatePicker(templates: Template[], event: MouseEvent): void {
    this.appendPickerEntries(templates);
    this.positionPickerNearPointer(event);
  }

  private appendPickerEntries(templates: Template[]): void {
    for (let i = 0; i < templates.length; i++) {
      this.block.appendChild(this.createPickerEntry(templates[i]));
    }
    this.block.classList.add('_twig_inspector__overlay__block_static');
  }

  private createPickerEntry(template: Template): HTMLDivElement {
    const row = document.createElement('div');
    row.dataset.href = template.link;
    row.innerText = template.name;
    row.addEventListener('click', (ev: MouseEvent) => {
      const href = (ev.currentTarget as HTMLElement).dataset.href || '';
      this.reset();
      /* c8 ignore start -- v8 underreports branches when reached via synthetic click */
      if (href && href !== '#') {
        window.location.href = href;
      }
      /* c8 ignore stop */
      ev.stopPropagation();
    });
    return row;
  }

  private positionPickerNearPointer(event: MouseEvent): void {
    this.block.style.left = event.clientX - 20 + 'px';
    this.block.style.right = 'auto';
    this.block.style.top = event.clientY + window.scrollY - 20 + 'px';
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
