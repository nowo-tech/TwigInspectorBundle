import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
  clearBundleLoggerForTest,
  createBundleLogger,
  getLogger,
  setBundleLogger,
} from './logger';

describe('logger', () => {
  beforeEach(() => {
    vi.spyOn(console, 'log').mockImplementation(() => {});
    vi.spyOn(console, 'debug').mockImplementation(() => {});
    vi.spyOn(console, 'info').mockImplementation(() => {});
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('createBundleLogger', () => {
    it('scriptLoaded logs without build time when options empty', () => {
      const log = createBundleLogger('twig-inspector');
      log.scriptLoaded();
      expect(console.log).toHaveBeenCalledWith(
        expect.stringContaining('script loaded'),
        expect.any(String),
      );
    });

    it('scriptLoaded logs with build time when buildTime provided', () => {
      const log = createBundleLogger('twig-inspector', {
        buildTime: '2026-01-15T12:00:00.000Z',
      });
      log.scriptLoaded();
      expect(console.log).toHaveBeenCalledWith(
        expect.stringContaining('script loaded'),
        expect.any(String),
        'color:#059669',
      );
      expect(console.log).toHaveBeenCalledWith(
        expect.stringContaining('2026-01-15T12:00:00.000Z'),
        expect.any(String),
        expect.any(String),
      );
    });

    it('alwaysLog: true — debug logs without setDebug', () => {
      const log = createBundleLogger('twig-inspector', { alwaysLog: true });
      log.debug('msg');
      expect(console.debug).toHaveBeenCalled();
    });

    it('alwaysLog: true — info logs without setDebug', () => {
      const log = createBundleLogger('twig-inspector', { alwaysLog: true });
      log.info('msg');
      expect(console.info).toHaveBeenCalled();
    });

    it('alwaysLog: true — warn logs without setDebug', () => {
      const log = createBundleLogger('twig-inspector', { alwaysLog: true });
      log.warn('msg');
      expect(console.warn).toHaveBeenCalled();
    });

    it('alwaysLog: true — error logs without setDebug', () => {
      const log = createBundleLogger('twig-inspector', { alwaysLog: true });
      log.error('msg');
      expect(console.error).toHaveBeenCalled();
    });

    it('setDebug is no-op when alwaysLog true', () => {
      const log = createBundleLogger('twig-inspector', { alwaysLog: true });
      log.setDebug(false);
      log.debug('still logged');
      expect(console.debug).toHaveBeenCalled();
    });

    it('debug with no args when alwaysLog true', () => {
      const log = createBundleLogger('twig-inspector', { alwaysLog: true });
      log.debug();
      expect(console.debug).toHaveBeenCalledWith(expect.any(String), expect.any(String));
    });

    it('info with no args when alwaysLog true', () => {
      const log = createBundleLogger('twig-inspector', { alwaysLog: true });
      log.info();
      expect(console.info).toHaveBeenCalledWith(expect.any(String), expect.any(String));
    });

    it('alwaysLog: false — debug does nothing without setDebug', () => {
      const log = createBundleLogger('twig-inspector', { alwaysLog: false });
      log.debug('msg');
      expect(console.debug).not.toHaveBeenCalled();
    });

    it('alwaysLog: false — info does nothing', () => {
      const log = createBundleLogger('twig-inspector', { alwaysLog: false });
      log.info('msg');
      expect(console.info).not.toHaveBeenCalled();
    });

    it('alwaysLog: false — warn does nothing', () => {
      const log = createBundleLogger('twig-inspector', { alwaysLog: false });
      log.warn('msg');
      expect(console.warn).not.toHaveBeenCalled();
    });

    it('alwaysLog: false — error does nothing', () => {
      const log = createBundleLogger('twig-inspector', { alwaysLog: false });
      log.error('msg');
      expect(console.error).not.toHaveBeenCalled();
    });

    it('warn with no args when alwaysLog true', () => {
      const log = createBundleLogger('twig-inspector', { alwaysLog: true });
      log.warn();
      expect(console.warn).toHaveBeenCalledWith(expect.any(String), expect.any(String));
    });

    it('error with no args when alwaysLog true', () => {
      const log = createBundleLogger('twig-inspector', { alwaysLog: true });
      log.error();
      expect(console.error).toHaveBeenCalledWith(expect.any(String), expect.any(String));
    });

    it('prefix includes bundle name', () => {
      const log = createBundleLogger('twig-inspector', { alwaysLog: true });
      log.debug('test');
      expect(console.debug).toHaveBeenCalledWith(
        expect.stringContaining('[twig-inspector]'),
        expect.any(String),
        'test',
      );
    });

    it('debug with object stringifies', () => {
      const log = createBundleLogger('twig-inspector', { alwaysLog: true });
      log.debug('foo', { a: 1 });
      expect(console.debug).toHaveBeenCalledWith(
        expect.any(String),
        expect.any(String),
        'foo',
        '{"a":1}',
      );
    });
  });

  describe('getLogger / setBundleLogger', () => {
    it('getLogger returns same instance after setBundleLogger', () => {
      const log = createBundleLogger('twig-inspector', { alwaysLog: true });
      setBundleLogger(log);
      const retrieved = getLogger();
      retrieved.scriptLoaded();
      expect(console.log).toHaveBeenCalled();
      expect(retrieved).toBe(log);
    });

    it('getLogger returns no-op logger when never set (no console calls)', () => {
      clearBundleLoggerForTest();
      const retrieved = getLogger();
      retrieved.scriptLoaded();
      retrieved.debug('x');
      retrieved.info('y');
      retrieved.warn('z');
      retrieved.error('e');
      expect(console.log).not.toHaveBeenCalled();
      expect(console.debug).not.toHaveBeenCalled();
      expect(console.info).not.toHaveBeenCalled();
      expect(console.warn).not.toHaveBeenCalled();
      expect(console.error).not.toHaveBeenCalled();
      setBundleLogger(createBundleLogger('twig-inspector', { alwaysLog: true }));
    });
  });
});
