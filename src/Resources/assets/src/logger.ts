/**
 * =============================================================================
 * BUNDLE LOGGER - Twig Inspector Bundle (always-on logging)
 * =============================================================================
 *
 * Same API as other Nowo bundles (IconSelectorBundle, SelectAllChoiceBundle).
 * This bundle has no debug toggle: all logs (scriptLoaded, debug, info, warn, error)
 * are always shown (alwaysLog: true).
 *
 * USAGE:
 *
 * 1. In the entry (index.ts), create and register the logger:
 *
 *    import { createBundleLogger, setBundleLogger } from './logger';
 *    declare const __TWIG_INSPECTOR_BUILD_TIME__: string;
 *
 *    const log = createBundleLogger('twig-inspector', {
 *      buildTime: typeof __TWIG_INSPECTOR_BUILD_TIME__ !== 'undefined' ? __TWIG_INSPECTOR_BUILD_TIME__ : undefined,
 *      alwaysLog: true,
 *    });
 *    log.scriptLoaded();
 *    setBundleLogger(log);
 *
 * 2. In other modules, get the logger and use level methods:
 *
 *    import { getLogger } from './logger';
 *    const log = getLogger();
 *    log.debug('detail', { foo: 1 });
 *    log.info('something happened');
 *    log.warn('unexpected', err);
 *    log.error('failure', err);
 *
 * =============================================================================
 */

export type BundleLoggerOptions = {
  /** If set, scriptLoaded() will include this (e.g. build/compilation time). */
  buildTime?: string;
  /** When true, debug/info/warn/error always output (no setDebug needed). */
  alwaysLog?: boolean;
};

export type BundleLogger = {
  /** Call once at startup. Logs "script loaded" and optional build time. Always shown. */
  scriptLoaded: () => void;
  /** No-op when alwaysLog is true; kept for API compatibility. */
  setDebug: (enabled: boolean) => void;
  debug: (...args: unknown[]) => void;
  info: (...args: unknown[]) => void;
  warn: (...args: unknown[]) => void;
  error: (...args: unknown[]) => void;
};

const STYLES = {
  script: 'color:#0ea5e9;font-weight:bold',
  debug: 'color:#6b7280',
  info: 'color:#2563eb',
  warn: 'color:#d97706',
  error: 'color:#dc2626;font-weight:bold',
} as const;

const EMOJI = {
  script: '📦',
  debug: '🔍',
  info: 'ℹ️',
  warn: '⚠️',
  error: '❌',
} as const;

function formatArgs(args: unknown[]): unknown[] {
  return args.map((a) =>
    typeof a === 'object' && a !== null && !(a instanceof Error) ? JSON.stringify(a) : a,
  );
}

function noop(): void {}

let instance: BundleLogger | null = null;

/**
 * Registers the bundle logger. Call once from the entry (index.ts) after createBundleLogger.
 */
export function setBundleLogger(log: BundleLogger): void {
  instance = log;
}

/**
 * Clears the registered logger (for tests only). After this, getLogger() returns the no-op logger until setBundleLogger is called again.
 */
export function clearBundleLoggerForTest(): void {
  instance = null;
}

/**
 * Returns the bundle logger. Use in Overlay, BlockStorage, etc.
 * If never set, returns a no-op logger so tests do not need to call setBundleLogger.
 */
export function getLogger(): BundleLogger {
  if (instance !== null) {
    return instance;
  }
  return {
    scriptLoaded: noop,
    setDebug: noop,
    debug: noop,
    info: noop,
    warn: noop,
    error: noop,
  };
}

/**
 * Creates a logger for the Twig Inspector bundle. Use alwaysLog: true so all levels are always shown.
 *
 * @param name - Short name for log prefix (e.g. 'twig-inspector').
 * @param options - buildTime for scriptLoaded(); alwaysLog: true so debug/info/warn/error always run.
 */
export function createBundleLogger(name: string, options: BundleLoggerOptions = {}): BundleLogger {
  const prefix = `[${name}]`;
  const { buildTime, alwaysLog = false } = options;
  const logAlways = alwaysLog === true;

  return {
    scriptLoaded(): void {
      if (buildTime !== undefined && buildTime !== '') {
        console.log(
          `%c${EMOJI.script} ${prefix} script loaded, build time: %c${buildTime}`,
          STYLES.script,
          'color:#059669',
        );
      } else {
        console.log(`%c${EMOJI.script} ${prefix} script loaded`, STYLES.script);
      }
    },

    setDebug(_enabled: boolean): void {
      /* no-op when alwaysLog; kept for API compatibility */
    },

    debug(...args: unknown[]): void {
      if (!logAlways) return;
      if (args.length > 0) {
        console.debug(`%c${EMOJI.debug} ${prefix}`, STYLES.debug, ...formatArgs(args));
      } else {
        console.debug(`%c${EMOJI.debug} ${prefix}`, STYLES.debug);
      }
    },

    info(...args: unknown[]): void {
      if (!logAlways) return;
      if (args.length > 0) {
        console.info(`%c${EMOJI.info} ${prefix}`, STYLES.info, ...formatArgs(args));
      } else {
        console.info(`%c${EMOJI.info} ${prefix}`, STYLES.info);
      }
    },

    warn(...args: unknown[]): void {
      if (!logAlways) return;
      if (args.length > 0) {
        console.warn(`%c${EMOJI.warn} ${prefix}`, STYLES.warn, ...formatArgs(args));
      } else {
        console.warn(`%c${EMOJI.warn} ${prefix}`, STYLES.warn);
      }
    },

    error(...args: unknown[]): void {
      if (!logAlways) return;
      if (args.length > 0) {
        console.error(`%c${EMOJI.error} ${prefix}`, STYLES.error, ...formatArgs(args));
      } else {
        console.error(`%c${EMOJI.error} ${prefix}`, STYLES.error);
      }
    },
  };
}
