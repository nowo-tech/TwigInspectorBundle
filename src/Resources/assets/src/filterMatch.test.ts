import { describe, it, expect } from 'vitest';
import { blockMatchesFilter } from './filterMatch';
import { BlockClass, TemplateClass } from './models';

function blockWithTemplates(templates: Array<{ name: string; link: string }>): BlockClass {
  const el = document.createElement('div');
  const list = templates.map((t) => new TemplateClass(t.name, t.link));
  return new BlockClass(0, el, list);
}

describe('blockMatchesFilter', () => {
  it('returns true when filter is empty', () => {
    const block = blockWithTemplates([{ name: 'foo.html.twig', link: '/_template/foo' }]);
    expect(blockMatchesFilter(block, '')).toBe(true);
    expect(blockMatchesFilter(block, '   ')).toBe(true);
  });

  it('matches by template name (case-insensitive)', () => {
    const block = blockWithTemplates([
      { name: 'demo/_header.html.twig [/path/demo/_header.html.twig]', link: '/_template/demo/_header.html.twig' },
    ]);
    expect(blockMatchesFilter(block, 'header')).toBe(true);
    expect(blockMatchesFilter(block, 'HEADER')).toBe(true);
    expect(blockMatchesFilter(block, 'demo')).toBe(true);
    expect(blockMatchesFilter(block, 'footer')).toBe(false);
  });

  it('matches by template link/path', () => {
    const block = blockWithTemplates([
      { name: 'tpl [templates/demo/home.html.twig]', link: '/_template/templates/demo/home.html.twig?line=1' },
    ]);
    expect(blockMatchesFilter(block, 'templates/demo')).toBe(true);
    expect(blockMatchesFilter(block, '_template')).toBe(true);
    expect(blockMatchesFilter(block, 'home')).toBe(true);
    expect(blockMatchesFilter(block, 'other')).toBe(false);
  });

  it('comma-separated filter matches if any part matches (OR)', () => {
    const block = blockWithTemplates([
      { name: 'demo/_footer.html.twig', link: '/_template/demo/_footer.html.twig' },
    ]);
    expect(blockMatchesFilter(block, 'header, footer')).toBe(true);
    expect(blockMatchesFilter(block, 'footer, header')).toBe(true);
    expect(blockMatchesFilter(block, 'header,instructions')).toBe(false);
  });

  it('trims comma-separated parts', () => {
    const block = blockWithTemplates([{ name: 'header', link: '/x' }]);
    expect(blockMatchesFilter(block, '  header  ,  other  ')).toBe(true);
  });

  it('returns true if any template in the block matches', () => {
    const block = blockWithTemplates([
      { name: 'base.html.twig', link: '/base' },
      { name: 'demo/home.html.twig', link: '/demo/home' },
    ]);
    expect(blockMatchesFilter(block, 'base')).toBe(true);
    expect(blockMatchesFilter(block, 'home')).toBe(true);
    expect(blockMatchesFilter(block, 'demo')).toBe(true);
    expect(blockMatchesFilter(block, 'missing')).toBe(false);
  });

  it('empty comma parts are ignored', () => {
    const block = blockWithTemplates([{ name: 'header', link: '/x' }]);
    expect(blockMatchesFilter(block, ',  , header ,  ')).toBe(true);
    expect(blockMatchesFilter(block, ',  ,  ,  ')).toBe(true);
  });

  it('returns false when block has no templates and filter is non-empty', () => {
    const block = blockWithTemplates([]);
    expect(blockMatchesFilter(block, 'foo')).toBe(false);
    expect(blockMatchesFilter(block, '')).toBe(true);
  });
});
