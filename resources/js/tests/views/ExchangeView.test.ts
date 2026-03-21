import { describe, expect, it, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import ExchangeView from '@/views/ExchangeView.vue';
import { i18n } from '@/i18n';

const pushMock = vi.fn(() => Promise.resolve());

vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: pushMock,
  }),
}));

describe('ExchangeView', () => {
  beforeEach(() => {
    pushMock.mockClear();
  });

  it('renders translated back button', () => {
    i18n.global.locale.value = 'sk';

    const wrapper = mount(ExchangeView, {
      global: {
        plugins: [i18n],
        stubs: {
          TheExchange: true,
        },
      },
    });

    expect(wrapper.text()).toContain('Späť do jedálne');
  });

  it('navigates to menu on back click', async () => {
    const wrapper = mount(ExchangeView, {
      global: {
        plugins: [i18n],
        stubs: {
          TheExchange: true,
        },
      },
    });

    await wrapper.find('button.back-button').trigger('click');
    await Promise.resolve();

    expect(pushMock).toHaveBeenCalledWith('/');
  });
});
