/**
 * Block storage: maps DOM elements to the Twig templates (and controller renders) that produced them.
 *
 * Populated by scanning HTML comments injected by the Twig extension and ControllerCommentSubscriber.
 * Supports both template/block comments (box-drawing ┏┗) and controller comments (┏ controller: … ┗ /controller).
 */

import type { Block, Template } from './types';
import { BlockClass, TemplateClass } from './models';
import { getLogger } from './logger';

/** Regex for controller opening comment: ┏ controller: FQCN::method [main|fragment] (template: path)? (same start as Twig blocks). */
const CONTROLLER_OPEN =
  /^\s*┏\s*controller:\s+(.+?)\s+\[(main|fragment)\](?:\s+template:\s+(\S+))?\s*$/u;
/** Regex for controller closing comment (fragment only): ┗ /controller */
const CONTROLLER_CLOSE = /^\s*┗\s*\/controller\s*$/u;

/**
 * Storage that maps DOM elements to the Twig templates (and controller renders) that produced them.
 * Populated by scanning HTML comments injected by the Twig extension and ControllerCommentSubscriber.
 */
export class BlockStorage {
  private elements: HTMLElement[] = [];
  private templatesToElements: Template[][] = [];

  /**
   * Scans the document for Twig Inspector HTML comments and rebuilds the element–template map.
   * Includes template/block comments (box-drawing ┏┗) and controller comments (┏ controller: … ┗ /controller).
   * Call after DOM changes (e.g. AJAX) to rescan.
   */
  collectData(): void {
    this.elements = [];
    this.templatesToElements = [];

    const sfToolbar = document.getElementsByClassName('sf-toolbar')[0] as HTMLElement;
    const startComments = document.createNodeIterator(
      document.body,
      NodeFilter.SHOW_COMMENT
    );

    let curNode: Node | null;
    while ((curNode = startComments.nextNode())) {
      const match = curNode.nodeValue?.match(
        /^(\s+[\u250F\u256D\u2554\u250E]([^\s]?)+\s)([^\s]+)(\s\[)([^\]]+)(\]\s#)(\w+)/
      );

      if (null === match || match[3] === undefined || match[5] === undefined || match[7] === undefined) {
        continue;
      }

      const templateId = match[7];
      const templateName = match[3] + ' [' + match[5].replace(/\/_template\//g, '') + ']';
      const templateLink = match[5];
      let element: Node | null = curNode.nextSibling;
      const regexp = new RegExp(
        '^(\\s+[\\u2517\\u2570\\u255A\\u2516])([^#]+)(#' + templateId + ')$',
        'g'
      );

      while (!(!element || (element.nodeType === 8 && element.nodeValue?.match(regexp)))) {
        if (
          element.nodeType === 1 &&
          !['SCRIPT', 'STYLE'].includes((element as HTMLElement).tagName) &&
          !sfToolbar.contains(element as HTMLElement) &&
          window.getComputedStyle(element as HTMLElement).display !== 'none'
        ) {
          const layoutItem = this.findOrCreate(element as HTMLElement);
          const template = new TemplateClass(templateName, templateLink);
          this.addTemplate(layoutItem.index, template);
        }
        element = element.nextSibling;
      }
    }

    this.collectControllerRanges(sfToolbar);
    this.sortTemplatesForDisplay();
    getLogger().debug('BlockStorage collectData finished', {
      blocks: this.elements.length,
      templatesCount: this.templatesToElements.length,
    });
  }

  /**
   * Sorts each block's templates for overlay display: controller principal first, then [fragment] if any, then Twig templates in flow order.
   */
  private sortTemplatesForDisplay(): void {
    for (let i = 0; i < this.templatesToElements.length; i++) {
      const list = this.templatesToElements[i];
      /* c8 ignore start -- sort comparator branches underreported by v8 */
      this.templatesToElements[i] = [...list].sort((a, b) => {
        const aController = a.name.startsWith('Controller:');
        const bController = b.name.startsWith('Controller:');
        if (aController && !bController) return -1;
        if (!aController && bController) return 1;
        if (aController && bController) {
          const aMain = a.name.includes('[main]');
          const bMain = b.name.includes('[main]');
          if (aMain && !bMain) return -1;
          if (!aMain && bMain) return 1;
          return 0;
        }
        return 0;
      });
      /* c8 ignore stop */
    }
  }

  /**
   * Scans for controller comments (┏ controller: … [main|fragment] … ┗ /controller) and adds a
   * "Controller: FQCN [main|fragment] · template" entry to every element in that range so the overlay shows controller info on hover.
   *
   * @param sfToolbar - Symfony toolbar element (elements inside it are excluded from overlay)
   */
  private collectControllerRanges(sfToolbar: HTMLElement): void {
    const commentIterator = document.createNodeIterator(
      document.body,
      NodeFilter.SHOW_COMMENT
    );
    let node: Node | null;
    while ((node = commentIterator.nextNode())) {
      const openMatch = node.nodeValue?.match(CONTROLLER_OPEN);
      if (!openMatch) {
        continue;
      }
      const controllerStr = openMatch[1].trim();
      const role = openMatch[2];
      const templatePath = openMatch[3]?.trim();
      const controllerName =
        'Controller: ' + controllerStr + ' [' + role + ']' + (templatePath ? ' · ' + templatePath : '');
      const controllerLink = templatePath ? '/_template/' + templatePath : '#';

      let start: Node | null = node.nextSibling;
      let end: Node | null = null;
      if (role === 'fragment') {
        end = this.findControllerCloseComment(node);
      }
      this.visitElementsInRange(start, end, (el: HTMLElement) => {
        if (
          !['SCRIPT', 'STYLE'].includes(el.tagName) &&
          !sfToolbar.contains(el) &&
          window.getComputedStyle(el).display !== 'none'
        ) {
          const block = this.findOrCreate(el);
          this.addTemplate(block.index, new TemplateClass(controllerName, controllerLink));
        }
      });
    }
  }

  /**
   * Finds the next controller closing comment (┗ /controller) in document order after the given node.
   *
   * @param afterNode - Node to start searching after (e.g. the node after the controller open comment)
   * @returns The closing comment node, or null if not found
   */
  private findControllerCloseComment(afterNode: Node): Node | null {
    let node: Node | null = afterNode;
    /* c8 ignore start -- loop branches underreported by v8 */
    while ((node = this.nextInDocumentOrder(node, document.body))) {
      if (node.nodeType === Node.COMMENT_NODE && CONTROLLER_CLOSE.test((node as Comment).nodeValue ?? '')) {
        return node;
      }
    }
    /* c8 ignore stop */
    return null;
  }

  /**
   * Visits every element node between start and end (exclusive) in document order, calling fn for each.
   *
   * @param start - First node to consider (inclusive)
   * @param end - Node to stop before (exclusive), or null to visit until end of document
   * @param fn - Callback invoked for each element node in the range
   */
  private visitElementsInRange(
    start: Node | null,
    end: Node | null,
    fn: (el: HTMLElement) => void
  ): void {
    let node: Node | null = start;
    /* c8 ignore start -- loop/nodeType branches underreported by v8 */
    while (node && node !== end) {
      if (node.nodeType === Node.ELEMENT_NODE) {
        fn(node as HTMLElement);
      }
      node = this.nextInDocumentOrder(node, document.body);
    }
    /* c8 ignore stop */
  }

  /**
   * Returns the next node in document order (depth-first): first child, then next sibling, then parent's next sibling, etc.
   *
   * @param node - Current node (may be null)
   * @param root - Root node to stop at (do not go above this)
   * @returns The next node, or null if there is none before reaching root
   */
  private nextInDocumentOrder(node: Node | null, root: Node): Node | null {
    /* c8 ignore start -- branches underreported by v8 */
    if (!node) return null;
    if (node.firstChild) return node.firstChild;
    if (node.nextSibling) return node.nextSibling;
    let p: Node | null = node.parentNode;
    while (p && p !== root) {
      if (p.nextSibling) return p.nextSibling;
      p = p.parentNode;
    }
    return null;
    /* c8 ignore stop */
  }

  /**
   * Returns the block for the given element, or null if not tracked.
   *
   * @param element - DOM element to look up
   * @returns Block (element + templates) or null
   */
  find(element: HTMLElement): Block | null {
    const index = this.elements.indexOf(element);
    if (index < 0) {
      return null;
    }
    const templates = this.templatesToElements[index];
    return new BlockClass(index, element, templates);
  }

  /**
   * Registers a new element and returns its block.
   *
   * @param element - DOM element to register
   * @returns New block with empty templates array
   */
  create(element: HTMLElement): Block {
    const length = this.elements.push(element);
    const index = length - 1;
    this.templatesToElements[index] = [];
    return new BlockClass(index, element, []);
  }

  /**
   * Returns the block for the element, creating one if not yet tracked.
   *
   * @param element - DOM element to look up or register
   * @returns Block (existing or newly created)
   */
  findOrCreate(element: HTMLElement): Block {
    const layoutItem = this.find(element);
    return layoutItem !== null ? layoutItem : this.create(element);
  }

  /**
   * Associates a template with a block by index.
   *
   * @param index - Block index (from find/create)
   * @param template - Template (name + link) to add to the block
   */
  addTemplate(index: number, template: Template): void {
    this.templatesToElements[index].push(template);
  }

  /**
   * Returns the templates for a block index.
   *
   * @param index - Block index (from find/create)
   * @returns Array of templates (name + link) for that block
   */
  getTemplates(index: number): Template[] {
    return this.templatesToElements[index];
  }

  /**
   * Returns all blocks (element + templates) currently in storage.
   * Used to draw filter highlights over matching blocks.
   */
  getAllBlocks(): Block[] {
    const blocks: Block[] = [];
    for (let i = 0; i < this.elements.length; i++) {
      blocks.push(new BlockClass(i, this.elements[i], this.templatesToElements[i]));
    }
    return blocks;
  }
}
