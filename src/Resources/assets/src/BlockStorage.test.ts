import { describe, it, expect, beforeEach } from 'vitest';
import { BlockStorage } from './BlockStorage';
import { TemplateClass } from './models';

describe('BlockStorage', () => {
  let storage: BlockStorage;

  beforeEach(() => {
    storage = new BlockStorage();
  });

  describe('create', () => {
    it('registers element and returns block with empty templates', () => {
      const el = document.createElement('div');
      const block = storage.create(el);
      expect(block.index).toBe(0);
      expect(block.element).toBe(el);
      expect(block.templates).toEqual([]);
    });

    it('assigns incrementing index for each create', () => {
      const el1 = document.createElement('div');
      const el2 = document.createElement('span');
      const b1 = storage.create(el1);
      const b2 = storage.create(el2);
      expect(b1.index).toBe(0);
      expect(b2.index).toBe(1);
    });
  });

  describe('find', () => {
    it('returns null when element was not registered', () => {
      const el = document.createElement('div');
      expect(storage.find(el)).toBe(null);
    });

    it('returns block when element was created', () => {
      const el = document.createElement('div');
      storage.create(el);
      const block = storage.find(el);
      expect(block).not.toBe(null);
      expect(block!.element).toBe(el);
      expect(block!.index).toBe(0);
    });
  });

  describe('findOrCreate', () => {
    it('creates block when element not found', () => {
      const el = document.createElement('div');
      const block = storage.findOrCreate(el);
      expect(block.index).toBe(0);
      expect(block.element).toBe(el);
      expect(storage.find(el)).not.toBe(null);
    });

    it('returns existing block when element already registered', () => {
      const el = document.createElement('div');
      const first = storage.findOrCreate(el);
      const second = storage.findOrCreate(el);
      expect(second.index).toBe(first.index);
      expect(second.element).toBe(first.element);
      expect(second.templates).toEqual(first.templates);
    });
  });

  describe('addTemplate and getTemplates', () => {
    it('adds template to block by index', () => {
      const el = document.createElement('div');
      const block = storage.create(el);
      const t = new TemplateClass('home.html.twig', '/_template/home.html.twig');
      storage.addTemplate(block.index, t);
      expect(storage.getTemplates(block.index)).toHaveLength(1);
      expect(storage.getTemplates(block.index)[0].name).toBe('home.html.twig');
      expect(storage.getTemplates(block.index)[0].link).toBe('/_template/home.html.twig');
    });

    it('appends multiple templates to same block', () => {
      const el = document.createElement('div');
      const block = storage.create(el);
      storage.addTemplate(block.index, new TemplateClass('base.html.twig', '/base'));
      storage.addTemplate(block.index, new TemplateClass('home.html.twig', '/home'));
      const templates = storage.getTemplates(block.index);
      expect(templates).toHaveLength(2);
      expect(templates[0].name).toBe('base.html.twig');
      expect(templates[1].name).toBe('home.html.twig');
    });
  });

  describe('getAllBlocks', () => {
    it('returns empty array when no elements', () => {
      expect(storage.getAllBlocks()).toEqual([]);
    });

    it('returns all blocks in index order', () => {
      const el1 = document.createElement('div');
      const el2 = document.createElement('span');
      storage.create(el1);
      storage.create(el2);
      storage.addTemplate(0, new TemplateClass('a', '/a'));
      storage.addTemplate(1, new TemplateClass('b', '/b'));
      const blocks = storage.getAllBlocks();
      expect(blocks).toHaveLength(2);
      expect(blocks[0].index).toBe(0);
      expect(blocks[0].element).toBe(el1);
      expect(blocks[0].templates[0].name).toBe('a');
      expect(blocks[1].index).toBe(1);
      expect(blocks[1].element).toBe(el2);
      expect(blocks[1].templates[0].name).toBe('b');
    });
  });

  describe('collectData', () => {
    beforeEach(() => {
      document.body.innerHTML = '';
      const toolbar = document.createElement('div');
      toolbar.className = 'sf-toolbar';
      document.body.appendChild(toolbar);
    });

    it('collects Twig block comments and maps elements to templates', () => {
      const openComment = document.createComment(' ┏━ block_name [/_template/base.html.twig] #tid1');
      const div = document.createElement('div');
      div.textContent = 'content';
      const closeComment = document.createComment(' ┗ block_name #tid1');
      document.body.appendChild(openComment);
      document.body.appendChild(div);
      document.body.appendChild(closeComment);

      storage.collectData();

      const block = storage.find(div);
      expect(block).not.toBe(null);
      expect(block!.templates).toHaveLength(1);
      expect(block!.templates[0].name).toContain('block_name');
      expect(block!.templates[0].link).toBe('/_template/base.html.twig');
    });

    it('collects controller [main] comment and adds template to elements in range', () => {
      const controllerOpen = document.createComment(
        ' ┏ controller: App\\Controller\\Home::index [main] template: base.html.twig'
      );
      const div = document.createElement('div');
      div.textContent = 'main content';
      document.body.appendChild(controllerOpen);
      document.body.appendChild(div);

      storage.collectData();

      const block = storage.find(div);
      expect(block).not.toBe(null);
      const controllerTemplate = block!.templates.find((t) => t.name.startsWith('Controller:'));
      expect(controllerTemplate).toBeDefined();
      expect(controllerTemplate!.name).toContain('[main]');
      expect(controllerTemplate!.name).toContain('base.html.twig');
      expect(controllerTemplate!.link).toBe('/_template/base.html.twig');
    });

    it('collects controller [fragment] with closing comment and adds template to elements between', () => {
      const controllerOpen = document.createComment(
        ' ┏ controller: App\\Controller\\Fragment::foo [fragment]'
      );
      const span = document.createElement('span');
      span.textContent = 'fragment';
      const controllerClose = document.createComment(' ┗ /controller');
      document.body.appendChild(controllerOpen);
      document.body.appendChild(span);
      document.body.appendChild(controllerClose);

      storage.collectData();

      const block = storage.find(span);
      expect(block).not.toBe(null);
      const controllerTemplate = block!.templates.find((t) => t.name.startsWith('Controller:'));
      expect(controllerTemplate).toBeDefined();
      expect(controllerTemplate!.name).toContain('[fragment]');
      expect(controllerTemplate!.link).toBe('#');
    });

    it('fragment without closing comment still visits elements until end of document', () => {
      const controllerOpen = document.createComment(
        ' ┏ controller: App\\Controller\\Fragment::noClose [fragment]'
      );
      const span = document.createElement('span');
      span.textContent = 'no close';
      document.body.appendChild(controllerOpen);
      document.body.appendChild(span);

      storage.collectData();

      const block = storage.find(span);
      expect(block).not.toBe(null);
      expect(block!.templates.some((t) => t.name.includes('noClose'))).toBe(true);
    });

    it('sorts templates with controller first then Twig (sortTemplatesForDisplay)', () => {
      const controllerOpen = document.createComment(
        ' ┏ controller: App\\Controller\\Main [main] template: layout.html.twig'
      );
      const twigOpen = document.createComment(' ┏━ block [/_template/layout.html.twig] #t1');
      const div = document.createElement('div');
      div.textContent = 'content';
      const twigClose = document.createComment(' ┗ block #t1');
      document.body.appendChild(controllerOpen);
      document.body.appendChild(twigOpen);
      document.body.appendChild(div);
      document.body.appendChild(twigClose);

      storage.collectData();

      const block = storage.find(div);
      expect(block).not.toBe(null);
      expect(block!.templates.length).toBe(2);
      const names = block!.templates.map((t) => t.name);
      const idxController = names.findIndex((n) => n.startsWith('Controller:'));
      const idxTwig = names.findIndex((n) => n.includes('block'));
      expect(idxController).toBe(0);
      expect(idxTwig).toBe(1);
    });

    it('sorts two controller templates with [main] before [fragment]', () => {
      const fragmentOpen = document.createComment(
        ' ┏ controller: App\\Controller\\Fragment::foo [fragment]'
      );
      const mainOpen = document.createComment(
        ' ┏ controller: App\\Controller\\Main [main] template: a.html.twig'
      );
      const div = document.createElement('div');
      div.textContent = 'x';
      const fragmentClose = document.createComment(' ┗ /controller');
      document.body.appendChild(fragmentOpen);
      document.body.appendChild(mainOpen);
      document.body.appendChild(div);
      document.body.appendChild(fragmentClose);

      storage.collectData();

      const block = storage.find(div);
      expect(block).not.toBe(null);
      expect(block!.templates.length).toBe(2);
      const names = block!.templates.map((t) => t.name);
      expect(names[0]).toContain('[main]');
      expect(names[1]).toContain('[fragment]');
    });

    it('sorts two controller [main] templates (stable order)', () => {
      const main1 = document.createComment(
        ' ┏ controller: App\\Controller\\A [main] template: a.html.twig'
      );
      const main2 = document.createComment(
        ' ┏ controller: App\\Controller\\B [main] template: b.html.twig'
      );
      const div = document.createElement('div');
      div.textContent = 'y';
      document.body.appendChild(main1);
      document.body.appendChild(main2);
      document.body.appendChild(div);

      storage.collectData();

      const block = storage.find(div);
      expect(block).not.toBe(null);
      expect(block!.templates.length).toBe(2);
      expect(block!.templates.every((t) => t.name.includes('[main]'))).toBe(true);
    });

    it('sorts two Twig-only templates (no controller, stable order)', () => {
      const open1 = document.createComment(' ┏━ a [/_template/a.html.twig] #id1');
      const open2 = document.createComment(' ┏━ b [/_template/b.html.twig] #id2');
      const div = document.createElement('div');
      const close1 = document.createComment(' ┗ a #id1');
      const close2 = document.createComment(' ┗ b #id2');
      document.body.appendChild(open1);
      document.body.appendChild(open2);
      document.body.appendChild(div);
      document.body.appendChild(close1);
      document.body.appendChild(close2);

      storage.collectData();

      const block = storage.find(div);
      expect(block).not.toBe(null);
      expect(block!.templates.length).toBe(2);
      expect(block!.templates.every((t) => !t.name.startsWith('Controller:'))).toBe(true);
    });

    it('sort puts controller before Twig when block had [twig, controller] order', () => {
      const twigOpen = document.createComment(' ┏━ block [/_template/layout.html.twig] #t1');
      const controllerOpen = document.createComment(
        ' ┏ controller: App\\Controller\\Main [main] template: layout.html.twig',
      );
      const div = document.createElement('div');
      div.textContent = 'content';
      const twigClose = document.createComment(' ┗ block #t1');
      document.body.appendChild(twigOpen);
      document.body.appendChild(controllerOpen);
      document.body.appendChild(div);
      document.body.appendChild(twigClose);

      storage.collectData();

      const block = storage.find(div);
      expect(block).not.toBe(null);
      expect(block!.templates.length).toBe(2);
      const names = block!.templates.map((t) => t.name);
      expect(names[0]).toMatch(/^Controller:/);
      expect(names[1]).toContain('block');
    });

    it('controller range with text node before element skips text node in visit', () => {
      const fragmentOpen = document.createComment(
        ' ┏ controller: App\\Controller\\Fragment::foo [fragment]',
      );
      const textNode = document.createTextNode(' ');
      const div = document.createElement('div');
      div.textContent = 'x';
      const fragmentClose = document.createComment(' ┗ /controller');
      document.body.appendChild(fragmentOpen);
      document.body.appendChild(textNode);
      document.body.appendChild(div);
      document.body.appendChild(fragmentClose);

      storage.collectData();

      const block = storage.find(div);
      expect(block).not.toBe(null);
      expect(block!.templates.some((t) => t.name.includes('Fragment'))).toBe(true);
    });
  });
});
