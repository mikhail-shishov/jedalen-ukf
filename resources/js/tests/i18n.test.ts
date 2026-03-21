import { describe, expect, it, vi } from 'vitest';

describe('i18n locale bootstrap', () => {
  it('uses saved locale when it is supported', async () => {
    localStorage.setItem('preferred_locale', 'ru');
    vi.resetModules();

    const { i18n } = await import('@/i18n');

    expect(i18n.global.locale.value).toBe('ru');
  });

  it('falls back to sk when locale is unsupported', async () => {
    localStorage.setItem('preferred_locale', 'de');
    vi.resetModules();

    const { i18n } = await import('@/i18n');

    expect(i18n.global.locale.value).toBe('sk');
  });
});
