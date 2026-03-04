/**
 * Value objects for template and block used by the overlay and storage.
 *
 * TemplateClass: display name and IDE link for a single template or controller.
 * BlockClass: a DOM element with its associated templates (name + link for tooltip and click).
 */

import type { Block, Template } from './types';

/**
 * Value object for a single template (or controller) entry: display name and link to open in IDE.
 */
export class TemplateClass implements Template {
  /**
   * @param name - Display name (e.g. "template.html.twig [/path]" or "Controller: FQCN [main]").
   * @param link - URL to open in IDE (e.g. /_template/... or # for controller-only).
   */
  constructor(public name: string, public link: string) {}
}

/**
 * Value object for a block: index in storage, DOM element, and list of templates that rendered it.
 */
export class BlockClass implements Block {
  /**
   * @param index - Index in BlockStorage (used as key for getTemplates).
   * @param element - The DOM element that was rendered by the listed templates.
   * @param templates - Templates (and controller comments) that produced this block.
   */
  constructor(
    public index: number,
    public element: HTMLElement,
    public templates: Template[]
  ) {}

  /**
   * Renders template names as HTML (separated by <br/>) for the overlay tooltip.
   *
   * @returns HTML string of template names for this block
   */
  toString(): string {
    let text = '';
    for (let i = 0; i < this.templates.length; i++) {
      const template = this.templates[i];
      if (text.length > 0) {
        text += '<br/>';
      }
      text += template.name;
    }
    return text;
  }
}
