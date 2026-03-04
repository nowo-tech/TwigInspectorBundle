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
});
