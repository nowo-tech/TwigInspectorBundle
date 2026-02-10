/**
 * Value objects for template and block (TemplateClass, BlockClass).
 */

import type { Block, Template } from './types';

/** Value object for a single template (name + link). */
export class TemplateClass implements Template {
  /**
   * @param name - Display name for the template.
   * @param link - URL to open the template in the IDE.
   */
  constructor(public name: string, public link: string) {}
}

/** Value object for a block: element index, DOM node, and list of templates. */
export class BlockClass implements Block {
  /**
   * @param index - Index in BlockStorage.
   * @param element - The DOM element.
   * @param templates - Templates that rendered this block.
   */
  constructor(
    public index: number,
    public element: HTMLElement,
    public templates: Template[]
  ) {}

  /** Renders template names as HTML (with <br/>) for the tooltip. */
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
