/**
 * Link guard for overlay navigation (REQ-SEC-004, audit BS L1).
 *
 * The overlay scans every HTML comment in the page, so a comment that is part of rendered user content can carry an
 * arbitrary link (javascript:, data:, another origin…). Only same-origin http(s) links whose path is under the
 * bundle's template route (`/_template/…`, or the prefix the server reports in `template_route_prefix`) are followed.
 */

/** Path prefix of the `nowo_twig_inspector_template_link` route when the server does not report one. */
export const DEFAULT_TEMPLATE_ROUTE_PREFIX = '/_template/';

/**
 * Returns the path prefix of the template route: the server-generated `template_route_prefix` from the inline
 * config when it is a valid absolute path, otherwise `/_template/`.
 *
 * @returns Path prefix ending with a slash (e.g. "/_template/" or "/index.php/_template/")
 */
export function getTemplateRoutePrefix(): string {
  const prefix = window.__twig_inspector_config?.template_route_prefix;
  if (typeof prefix !== 'string' || !prefix.startsWith('/') || prefix.startsWith('//')) {
    return DEFAULT_TEMPLATE_ROUTE_PREFIX;
  }

  return prefix.endsWith('/') ? prefix : prefix + '/';
}

/**
 * Validates a link taken from a page comment and returns the normalized URL to navigate to, or null when it must
 * be ignored (not http(s), other origin, credentials, or a path outside the template route after normalization of
 * `..` segments).
 *
 * @param link - Raw link from the comment (relative or absolute)
 * @param prefix - Template route path prefix (defaults to getTemplateRoutePrefix())
 * @returns Absolute same-origin URL under the template route, or null
 */
export function safeTemplateLink(
  link: string | null | undefined,
  prefix: string = getTemplateRoutePrefix()
): string | null {
  if (!link || link === '#') {
    return null;
  }

  let url: URL;
  try {
    url = new URL(link, window.location.href);
  } catch {
    return null;
  }

  if (url.protocol !== 'http:' && url.protocol !== 'https:') {
    return null;
  }
  if (url.origin !== window.location.origin || url.username !== '' || url.password !== '') {
    return null;
  }
  if (!url.pathname.startsWith(prefix) || url.pathname.length <= prefix.length) {
    return null;
  }

  return url.href;
}
