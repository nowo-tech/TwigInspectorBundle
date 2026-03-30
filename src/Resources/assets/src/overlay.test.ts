import { beforeEach, describe, expect, it, vi } from 'vitest';
import { BlockStorage } from './block-storage';
import { createBundleLogger, setBundleLogger } from './logger';
import { BlockClass, TemplateClass } from './models';
import { Overlay } from './overlay';

describe('Overlay', () => {
  let storage: BlockStorage;
  let statusIcon: HTMLElement;
  let overlay: Overlay;

  beforeEach(() => {
    vi.spyOn(console, 'debug').mockImplementation(() => {});
    vi.spyOn(console, 'info').mockImplementation(() => {});
    setBundleLogger(createBundleLogger('twig-inspector', { alwaysLog: true }));

    document.body.innerHTML = '';
    const toolbar = document.createElement('div');
    toolbar.className = 'sf-toolbar';
    document.body.appendChild(toolbar);

    if (typeof document.elementsFromPoint !== 'function') {
      document.elementsFromPoint = vi.fn(() => []);
    }

    storage = new BlockStorage();
    statusIcon = document.createElement('div');
    statusIcon.id = '_twig_inspector__icon';
    document.body.appendChild(statusIcon);
    overlay = new Overlay(storage, statusIcon);
  });

  describe('constructor', () => {
    it('appends block, info and filterHighlightLayer to body', () => {
      expect(document.getElementById('_twig_inspector__overlay__block')).not.toBe(null);
      expect(document.getElementById('_twig_inspector__overlay__info')).not.toBe(null);
      expect(document.getElementById('_twig_inspector__filter_highlights')).not.toBe(null);
    });
  });

  describe('matchesFilter', () => {
    it('returns true when filter is empty', () => {
      overlay.filterQuery = '';
      const block = new BlockClass(0, document.createElement('div'), [
        new TemplateClass('base.html.twig', '/_template/base.html.twig'),
      ]);
      expect(overlay.matchesFilter(block)).toBe(true);
    });

    it('returns true when block name matches filter', () => {
      overlay.filterQuery = 'base';
      const block = new BlockClass(0, document.createElement('div'), [
        new TemplateClass('base.html.twig', '/_template/base.html.twig'),
      ]);
      expect(overlay.matchesFilter(block)).toBe(true);
    });

    it('returns false when block does not match filter', () => {
      overlay.filterQuery = 'other';
      const block = new BlockClass(0, document.createElement('div'), [
        new TemplateClass('base.html.twig', '/_template/base.html.twig'),
      ]);
      expect(overlay.matchesFilter(block)).toBe(false);
    });
  });

  describe('updateFilterHighlights', () => {
    it('does nothing when filter is empty', () => {
      overlay.filterQuery = '';
      overlay.updateFilterHighlights();
      expect(overlay['filterHighlightLayer'].children.length).toBe(0);
    });

    it('adds highlight boxes for matching blocks', () => {
      const div = document.createElement('div');
      document.body.appendChild(div);
      storage.create(div);
      storage.addTemplate(0, new TemplateClass('base.html.twig', '/_template/base.html.twig'));
      overlay.filterQuery = 'base';
      overlay.updateFilterHighlights();
      expect(overlay['filterHighlightLayer'].querySelectorAll('._twig_inspector__filter_highlight').length).toBe(1);
    });

    it('skips non-matching blocks (continue branch)', () => {
      const divA = document.createElement('div');
      const divB = document.createElement('div');
      document.body.appendChild(divA);
      document.body.appendChild(divB);
      storage.create(divA);
      storage.create(divB);
      storage.addTemplate(0, new TemplateClass('match.html.twig', '/_template/match.html.twig'));
      storage.addTemplate(1, new TemplateClass('other.html.twig', '/_template/other.html.twig'));
      overlay.filterQuery = 'match';
      overlay.updateFilterHighlights();
      expect(overlay['filterHighlightLayer'].querySelectorAll('._twig_inspector__filter_highlight').length).toBe(1);
    });
  });

  describe('hide', () => {
    it('removes visible class from info and block', () => {
      overlay.info.classList.add('_twig_inspector__visible');
      overlay.block.classList.add('_twig_inspector__visible');
      overlay.hide();
      expect(overlay.info.classList.contains('_twig_inspector__visible')).toBe(false);
      expect(overlay.block.classList.contains('_twig_inspector__visible')).toBe(false);
    });
  });

  describe('onFilterChange', () => {
    it('clears lastFocusedElement and updates filter highlights', () => {
      overlay['lastFocusedElement'] = document.createElement('div');
      overlay.filterQuery = 'x';
      overlay.onFilterChange();
      expect(overlay['lastFocusedElement']).toBe(null);
    });
  });

  describe('show', () => {
    it('positions block and info and adds visible class', () => {
      const div = document.createElement('div');
      document.body.appendChild(div);
      const block = new BlockClass(0, div, [
        new TemplateClass('base.html.twig', '/_template/base.html.twig'),
      ]);
      overlay.show(block);
      expect(overlay.block.classList.contains('_twig_inspector__visible')).toBe(true);
      expect(overlay.info.classList.contains('_twig_inspector__visible')).toBe(true);
      expect(overlay.block.dataset.templateIndex).toBe('0');
      expect(overlay.info.innerHTML).toContain('base.html.twig');
    });

    it('positions info above block when near bottom of viewport', () => {
      const div = document.createElement('div');
      div.style.position = 'absolute';
      div.style.top = '9999px';
      document.body.appendChild(div);
      Object.defineProperty(window, 'innerHeight', { value: 500, configurable: true });
      const block = new BlockClass(0, div, [
        new TemplateClass('t', '/_template/t'),
      ]);
      overlay.show(block);
      expect(overlay.info.style.top).not.toBe('');
    });

    it('positions info above when block would overflow viewport bottom', () => {
      const div = document.createElement('div');
      div.getBoundingClientRect = vi.fn(() => ({ left: 0, top: 400, right: 100, bottom: 440, width: 100, height: 40, x: 0, y: 400, toJSON: () => ({}) }));
      Object.defineProperty(div, 'offsetHeight', { value: 40, configurable: true });
      Object.defineProperty(div, 'offsetWidth', { value: 100, configurable: true });
      Object.defineProperty(window, 'innerHeight', { value: 450, configurable: true });
      const block = new BlockClass(0, div, [
        new TemplateClass('t', '/_template/t'),
      ]);
      overlay.show(block);
      expect(overlay.info.style.top).toContain('px');
    });

    it('positions info on right when near right edge of viewport', () => {
      const div = document.createElement('div');
      div.getBoundingClientRect = vi.fn(() => ({ left: 900, top: 10, right: 1000, bottom: 50, width: 100, height: 40, x: 900, y: 10, toJSON: () => ({}) }));
      Object.defineProperty(window, 'innerWidth', { value: 500, configurable: true });
      const block = new BlockClass(0, div, [
        new TemplateClass('t', '/_template/t'),
      ]);
      overlay.show(block);
      expect(overlay.info.style.left).toBe('auto');
      expect(overlay.info.style.right).toBe('0px');
    });
  });

  describe('freeze', () => {
    it('removes info visible and mousemove listener', () => {
      overlay.enable();
      overlay.freeze();
      expect(overlay.info.classList.contains('_twig_inspector__visible')).toBe(false);
    });
  });

  describe('rescan', () => {
    it('does nothing when overlay is not enabled', () => {
      overlay.rescan();
      expect(overlay.isEnabled).toBe(false);
    });

    it('calls storage.collectData and updateFilterHighlights when enabled', () => {
      const collectSpy = vi.spyOn(storage, 'collectData');
      overlay.enable();
      overlay.rescan();
      expect(collectSpy).toHaveBeenCalled();
    });
  });

  describe('enable', () => {
    it('sets isEnabled true and adds green class to status icon', () => {
      overlay.enable();
      expect(overlay.isEnabled).toBe(true);
      expect(statusIcon.classList.contains('sf-toolbar-status-green')).toBe(true);
      expect(statusIcon.classList.contains('sf-toolbar-status-yellow')).toBe(false);
    });
  });

  describe('reset', () => {
    it('disables overlay and resets icon to yellow', () => {
      overlay.enable();
      overlay.reset();
      expect(overlay.isEnabled).toBe(false);
      expect(statusIcon.classList.contains('sf-toolbar-status-yellow')).toBe(true);
      expect(statusIcon.classList.contains('sf-toolbar-status-green')).toBe(false);
    });
  });

  describe('handleKeyDown', () => {
    it('returns true and resets on Escape', () => {
      overlay.enable();
      const evt = new KeyboardEvent('keydown', { key: 'Escape' });
      const result = overlay.handleKeyDown(evt);
      expect(result).toBe(true);
      expect(overlay.isEnabled).toBe(false);
    });

    it('returns true for keyCode 27', () => {
      const evt = new KeyboardEvent('keydown', { keyCode: 27 });
      expect(overlay.handleKeyDown(evt)).toBe(true);
    });

    it('returns false for non-Escape key', () => {
      const evt = new KeyboardEvent('keydown', { key: 'a' });
      expect(overlay.handleKeyDown(evt)).toBe(false);
    });
  });

  describe('initClickHandler', () => {
    it('does nothing when templateIndex is not set', () => {
      overlay.block.dataset.templateIndex = '';
      overlay.block.dispatchEvent(new MouseEvent('click', { bubbles: true }));
      expect(overlay.block.classList.contains('_twig_inspector__overlay__block_static')).toBe(false);
    });

    it('handleBlockClick returns early when templateIndex is not set', () => {
      overlay.block.dataset.templateIndex = '';
      overlay.block.removeAttribute('data-template-index');
      const evt = new MouseEvent('click', { bubbles: true });
      expect(() => overlay.handleBlockClick(evt)).not.toThrow();
    });

    it('when block has no templates, click sets position and freezes', () => {
      const div = document.createElement('div');
      storage.create(div);
      overlay.block.dataset.templateIndex = '0';
      overlay.initClickHandler();
      overlay.block.dispatchEvent(new MouseEvent('click', { bubbles: true, clientX: 100, clientY: 200 }));
      expect(overlay.block.style.left).toBe('80px');
      expect(overlay.block.style.top).toBe((200 + window.scrollY - 20) + 'px');
    });

    it('single template with link: resets overlay (navigation stubbed)', () => {
      const div = document.createElement('div');
      storage.create(div);
      storage.addTemplate(0, new TemplateClass('base', '/_template/base.html.twig'));
      overlay.block.setAttribute('data-template-index', '0');
      let navigatedTo = '';
      const origLocation = window.location;
      Object.defineProperty(window, 'location', {
        configurable: true,
        value: {
          ...origLocation,
          get href() {
            return navigatedTo || origLocation.href;
          },
          set href(v: string) {
            navigatedTo = v;
          },
        },
      });
      overlay.initClickHandler();
      overlay.block.dispatchEvent(new MouseEvent('click', { bubbles: true, clientX: 10, clientY: 10 }));
      expect(overlay.isEnabled).toBe(false);
      expect(navigatedTo).toBe('/_template/base.html.twig');
      Object.defineProperty(window, 'location', { configurable: true, value: origLocation });
    });

    it('handleBlockClick with one template runs single-template path', () => {
      storage.create(document.createElement('div'));
      storage.addTemplate(0, new TemplateClass('one', '/url'));
      overlay.block.dataset.templateIndex = '0';
      let href = '';
      const origLocation = window.location;
      Object.defineProperty(window, 'location', {
        configurable: true,
        value: { get href() { return href; }, set href(v: string) { href = v; } },
      });
      const evt = new MouseEvent('click', { bubbles: true, clientX: 1, clientY: 1 });
      overlay.handleBlockClick(evt);
      expect(href).toBe('/url');
      Object.defineProperty(window, 'location', { configurable: true, value: origLocation });
    });

    it('single template with # does not navigate', () => {
      const div = document.createElement('div');
      storage.create(div);
      storage.addTemplate(0, new TemplateClass('Controller', '#'));
      overlay.block.dataset.templateIndex = '0';
      const before = window.location.href;
      overlay.initClickHandler();
      overlay.block.dispatchEvent(new MouseEvent('click', { bubbles: true }));
      expect(window.location.href).toBe(before);
    });

    it('multiple templates: shows static picker with links', () => {
      const div = document.createElement('div');
      storage.create(div);
      storage.addTemplate(0, new TemplateClass('a', '/a'));
      storage.addTemplate(0, new TemplateClass('b', '/b'));
      overlay.block.dataset.templateIndex = '0';
      overlay.initClickHandler();
      overlay.block.dispatchEvent(new MouseEvent('click', { bubbles: true, clientX: 50, clientY: 50 }));
      expect(overlay.block.classList.contains('_twig_inspector__overlay__block_static')).toBe(true);
      expect(overlay.block.children.length).toBe(2);
    });

    it('multiple templates: click on link # resets overlay without navigating', () => {
      const div = document.createElement('div');
      storage.create(div);
      storage.addTemplate(0, new TemplateClass('Controller', '#'));
      storage.addTemplate(0, new TemplateClass('tpl', '/x'));
      overlay.block.dataset.templateIndex = '0';
      overlay.initClickHandler();
      overlay.block.dispatchEvent(new MouseEvent('click', { bubbles: true, clientX: 50, clientY: 50 }));
      const linkHash = Array.from(overlay.block.querySelectorAll('[data-href]')).find(
        (el) => (el as HTMLElement).dataset.href === '#'
      ) as HTMLElement;
      linkHash.click();
      expect(overlay.isEnabled).toBe(false);
    });

    it('multiple templates: click on picker link with href navigates (location stubbed)', () => {
      const div = document.createElement('div');
      storage.create(div);
      storage.addTemplate(0, new TemplateClass('a', '/page-a'));
      storage.addTemplate(0, new TemplateClass('b', '/page-b'));
      overlay.block.dataset.templateIndex = '0';
      let navigatedTo = '';
      const origLocation = window.location;
      Object.defineProperty(window, 'location', {
        configurable: true,
        value: {
          ...origLocation,
          get href() {
            return navigatedTo || origLocation.href;
          },
          set href(v: string) {
            navigatedTo = v;
          },
        },
      });
      overlay.initClickHandler();
      overlay.block.dispatchEvent(new MouseEvent('click', { bubbles: true, clientX: 50, clientY: 50 }));
      const linkA = Array.from(overlay.block.querySelectorAll('[data-href]')).find(
        (el) => (el as HTMLElement).dataset.href === '/page-a'
      ) as HTMLElement;
      linkA.click();
      expect(overlay.isEnabled).toBe(false);
      expect(navigatedTo).toBe('/page-a');
      Object.defineProperty(window, 'location', { configurable: true, value: origLocation });
    });

    it('click when already static picker does nothing', () => {
      overlay.block.classList.add('_twig_inspector__overlay__block_static');
      const spy = vi.spyOn(overlay, 'freeze');
      overlay.initClickHandler();
      overlay.block.dispatchEvent(new MouseEvent('click', { bubbles: true }));
      expect(spy).not.toHaveBeenCalled();
    });
  });

  describe('onMouseMove', () => {
    it('hides overlay when cursor over toolbar', () => {
      const toolbar = document.querySelector('.sf-toolbar')!;
      document.elementsFromPoint = vi.fn(() => [toolbar]);
      overlay.enable();
      overlay.show(
        new BlockClass(0, document.createElement('div'), [
          new TemplateClass('t', '/_template/t'),
        ])
      );
      document.body.dispatchEvent(new MouseEvent('mousemove', { clientX: 0, clientY: 0, bubbles: true }));
      expect(overlay.info.classList.contains('_twig_inspector__visible')).toBe(false);
    });

    it('shows overlay when cursor over element in storage matching filter', () => {
      const openComment = document.createComment(' ┏━ base [/_template/base.html.twig] #tid1');
      const div = document.createElement('div');
      const closeComment = document.createComment(' ┗ base #tid1');
      document.body.appendChild(openComment);
      document.body.appendChild(div);
      document.body.appendChild(closeComment);
      document.elementsFromPoint = vi.fn(() => [div]);
      overlay.enable();
      overlay.filterQuery = '';
      document.body.dispatchEvent(new MouseEvent('mousemove', { clientX: 5, clientY: 5, bubbles: true }));
      expect(overlay.block.classList.contains('_twig_inspector__visible')).toBe(true);
      expect(overlay.info.innerHTML).toContain('base.html.twig');
    });

    it('does not call show again when cursor stays on same element', () => {
      const openComment = document.createComment(' ┏━ base [/_template/base.html.twig] #tid1');
      const div = document.createElement('div');
      const closeComment = document.createComment(' ┗ base #tid1');
      document.body.appendChild(openComment);
      document.body.appendChild(div);
      document.body.appendChild(closeComment);
      document.elementsFromPoint = vi.fn(() => [div]);
      overlay.enable();
      overlay.filterQuery = '';
      const showSpy = vi.spyOn(overlay, 'show');
      document.body.dispatchEvent(new MouseEvent('mousemove', { clientX: 5, clientY: 5, bubbles: true }));
      expect(showSpy).toHaveBeenCalledTimes(1);
      document.body.dispatchEvent(new MouseEvent('mousemove', { clientX: 6, clientY: 6, bubbles: true }));
      expect(showSpy).toHaveBeenCalledTimes(1);
    });

    it('hides when cursor not over any tracked element', () => {
      const other = document.createElement('div');
      document.body.appendChild(other);
      document.elementsFromPoint = vi.fn(() => [other]);
      overlay.enable();
      overlay.show(
        new BlockClass(0, document.createElement('div'), [
          new TemplateClass('t', '/_template/t'),
        ])
      );
      document.body.dispatchEvent(new MouseEvent('mousemove', { clientX: 0, clientY: 0, bubbles: true }));
      expect(overlay.info.classList.contains('_twig_inspector__visible')).toBe(false);
    });
  });

  describe('onScrollResize', () => {
    it('throttles and calls updateFilterHighlights', async () => {
      overlay.filterQuery = 'x';
      const div = document.createElement('div');
      document.body.appendChild(div);
      storage.create(div);
      storage.addTemplate(0, new TemplateClass('x', '/x'));
      window.dispatchEvent(new Event('scroll'));
      window.dispatchEvent(new Event('scroll'));
      await new Promise((r) => setTimeout(r, 60));
      expect(overlay['filterHighlightLayer'].children.length).toBe(1);
    });
  });
});
