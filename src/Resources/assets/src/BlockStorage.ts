/**
 * Maps DOM elements to the Twig templates (and controller renders) that produced them.
 * Populated by scanning HTML comments injected by the Twig extension and ControllerCommentSubscriber.
 */

import type { Block, Template } from './types';
import { BlockClass, TemplateClass } from './models';

/** Regex for controller opening comment: ┏ controller: FQCN::method [main|fragment] (template: path)? (same start as Twig blocks). */
const CONTROLLER_OPEN =
  /^\s*┏\s*controller:\s+(.+?)\s+\[(main|fragment)\](?:\s+template:\s+(\S+))?\s*$/u;
/** Regex for controller closing comment (fragment only): ┗ /controller */
const CONTROLLER_CLOSE = /^\s*┗\s*\/controller\s*$/u;

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
  }

  /**
   * Sorts each block's templates for overlay display: controller principal first, then [fragment] if any, then Twig templates in flow order.
   */
  private sortTemplatesForDisplay(): void {
    for (let i = 0; i < this.templatesToElements.length; i++) {
      const list = this.templatesToElements[i];
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
    }
  }

  /**
   * Scans for controller comments (┏ controller: … [main|fragment] … ┗ /controller) and adds a
   * "Controller: FQCN [main|fragment] · template" entry to every element in that range so the overlay shows controller info on hover.
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

  private findControllerCloseComment(afterNode: Node): Node | null {
    let node: Node | null = afterNode;
    while ((node = this.nextInDocumentOrder(node, document.body))) {
      if (node.nodeType === Node.COMMENT_NODE && CONTROLLER_CLOSE.test((node as Comment).nodeValue ?? '')) {
        return node;
      }
    }
    return null;
  }

  private visitElementsInRange(
    start: Node | null,
    end: Node | null,
    fn: (el: HTMLElement) => void
  ): void {
    let node: Node | null = start;
    while (node && node !== end) {
      if (node.nodeType === Node.ELEMENT_NODE) {
        fn(node as HTMLElement);
      }
      node = this.nextInDocumentOrder(node, document.body);
    }
  }

  private nextInDocumentOrder(node: Node | null, root: Node): Node | null {
    if (!node) return null;
    if (node.firstChild) return node.firstChild;
    if (node.nextSibling) return node.nextSibling;
    let p: Node | null = node.parentNode;
    while (p && p !== root) {
      if (p.nextSibling) return p.nextSibling;
      p = p.parentNode;
    }
    return null;
  }

  /**
   * Returns the block for the given element, or null if not tracked.
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
   */
  create(element: HTMLElement): Block {
    const length = this.elements.push(element);
    const index = length - 1;
    this.templatesToElements[index] = [];
    return new BlockClass(index, element, []);
  }

  /**
   * Returns the block for the element, creating one if not yet tracked.
   */
  findOrCreate(element: HTMLElement): Block {
    const layoutItem = this.find(element);
    return layoutItem !== null ? layoutItem : this.create(element);
  }

  /**
   * Associates a template with a block by index.
   */
  addTemplate(index: number, template: Template): void {
    this.templatesToElements[index].push(template);
  }

  /**
   * Returns the templates for a block index.
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
