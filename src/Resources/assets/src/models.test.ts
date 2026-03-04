import { describe, it, expect } from 'vitest';
import { BlockClass, TemplateClass } from './models';

describe('TemplateClass', () => {
  it('stores name and link', () => {
    const t = new TemplateClass('demo/home.html.twig [/path/demo/home.html.twig]', '/_template/demo/home.html.twig');
    expect(t.name).toBe('demo/home.html.twig [/path/demo/home.html.twig]');
    expect(t.link).toBe('/_template/demo/home.html.twig');
  });

  it('allows empty link (e.g. controller-only)', () => {
    const t = new TemplateClass('Controller: App\\Controller::index [main]', '#');
    expect(t.name).toContain('Controller:');
    expect(t.link).toBe('#');
  });
});

describe('BlockClass', () => {
  it('stores index, element and templates', () => {
    const el = document.createElement('div');
    const templates = [
      new TemplateClass('base.html.twig', '/_template/base.html.twig'),
      new TemplateClass('home.html.twig', '/_template/home.html.twig'),
    ];
    const block = new BlockClass(0, el, templates);
    expect(block.index).toBe(0);
    expect(block.element).toBe(el);
    expect(block.templates).toBe(templates);
    expect(block.templates.length).toBe(2);
  });

  it('toString returns single template name', () => {
    const el = document.createElement('span');
    const block = new BlockClass(0, el, [
      new TemplateClass('single.html.twig', '/_template/single.html.twig'),
    ]);
    expect(block.toString()).toBe('single.html.twig');
  });

  it('toString joins multiple template names with <br/>', () => {
    const el = document.createElement('div');
    const block = new BlockClass(0, el, [
      new TemplateClass('base.html.twig', '/base'),
      new TemplateClass('home.html.twig', '/home'),
    ]);
    expect(block.toString()).toBe('base.html.twig<br/>home.html.twig');
  });

  it('toString returns empty string when no templates', () => {
    const el = document.createElement('div');
    const block = new BlockClass(0, el, []);
    expect(block.toString()).toBe('');
  });
});
