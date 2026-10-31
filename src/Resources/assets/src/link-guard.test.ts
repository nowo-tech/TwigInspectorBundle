import { afterEach, describe, expect, it } from 'vitest';
import { DEFAULT_TEMPLATE_ROUTE_PREFIX, getTemplateRoutePrefix, safeTemplateLink } from './link-guard';

describe('link-guard', () => {
  afterEach(() => {
    delete window.__twig_inspector_config;
  });

  describe('getTemplateRoutePrefix', () => {
    it('defaults to /_template/ without config', () => {
      expect(getTemplateRoutePrefix()).toBe(DEFAULT_TEMPLATE_ROUTE_PREFIX);
    });

    it('uses the server-generated prefix and appends a trailing slash', () => {
      window.__twig_inspector_config = { template_route_prefix: '/index.php/_template' };
      expect(getTemplateRoutePrefix()).toBe('/index.php/_template/');
      window.__twig_inspector_config = { template_route_prefix: '/dev/_template/' };
      expect(getTemplateRoutePrefix()).toBe('/dev/_template/');
    });

    it.each(['', '_template/', '//evil.example/', 'javascript:x'])('falls back for invalid prefix %j', (prefix) => {
      window.__twig_inspector_config = { template_route_prefix: prefix };
      expect(getTemplateRoutePrefix()).toBe(DEFAULT_TEMPLATE_ROUTE_PREFIX);
    });
  });

  describe('safeTemplateLink', () => {
    const origin = window.location.origin;

    it.each([
      ['/_template/base.html.twig?line=3', origin + '/_template/base.html.twig?line=3'],
      ['/_template/%40App%2Fhome.html.twig?line=1', origin + '/_template/%40App%2Fhome.html.twig?line=1'],
      [origin + '/_template/a/b.html.twig', origin + '/_template/a/b.html.twig'],
    ])('accepts same-origin template link %s', (link, expected) => {
      expect(safeTemplateLink(link)).toBe(expected);
    });

    it.each([
      [null],
      [undefined],
      [''],
      ['#'],
      ['javascript:alert(document.domain)'],
      ['JaVaScRiPt:alert(1)'],
      [' javascript:alert(1)'],
      ['data:text/html,<script>alert(1)</script>'],
      ['vbscript:msgbox(1)'],
      ['//evil.example/_template/x'],
      ['/\\evil.example/_template/x'],
      ['https://other-origin.example/_template/x'],
      ['http://user:pass@' + window.location.host + '/_template/x'],
      ['/_template/../admin'],
      ['/_template/%2e%2e/admin'],
      ['../_template/../secret'],
      ['/_template/'],
      ['/_templatex/a'],
      ['/other/path'],
      ['http://['],
    ])('rejects %s', (link) => {
      expect(safeTemplateLink(link)).toBeNull();
    });

    it('honours a custom route prefix', () => {
      expect(safeTemplateLink('/dev/_template/a.html.twig', '/dev/_template/')).toBe(origin + '/dev/_template/a.html.twig');
      expect(safeTemplateLink('/_template/a.html.twig', '/dev/_template/')).toBeNull();
    });
  });
});
