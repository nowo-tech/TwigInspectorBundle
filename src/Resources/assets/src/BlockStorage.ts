/**
 * Maps DOM elements to the Twig templates that rendered them.
 * Populated by scanning HTML comments injected by the Twig extension.
 */

import type { Block, Template } from './types';
import { BlockClass, TemplateClass } from './models';

export class BlockStorage {
  private elements: HTMLElement[] = [];
  private templatesToElements: Template[][] = [];

  /**
   * Scans the document for Twig Inspector HTML comments and rebuilds the element–template map.
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
