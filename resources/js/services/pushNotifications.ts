import { i18n } from '@/i18n';

const SCHEDULE_KEY = 'push_scheduler_enabled';
let initialized = false;

const isBrowserNotificationSupported = () => {
  return typeof window !== 'undefined' && 'Notification' in window;
};

const nextOccurrence = (hours: number, minutes: number) => {
  const now = new Date();
  const candidate = new Date(now);
  candidate.setHours(hours, minutes, 0, 0);

  if (candidate.getTime() <= now.getTime()) {
    candidate.setDate(candidate.getDate() + 1);
  }

  return candidate;
};

const showLocalizedNotification = (slot: 'open' | 'closing') => {
  if (!isBrowserNotificationSupported() || Notification.permission !== 'granted') {
    return;
  }

  const title = i18n.global.t(`notifications.${slot}.title`);
  const body = i18n.global.t(`notifications.${slot}.body`);

  new Notification(title, {
    body,
    tag: `canteen-${slot}`,
  });
};

const scheduleDailyNotification = (hours: number, minutes: number, slot: 'open' | 'closing') => {
  const target = nextOccurrence(hours, minutes);
  const delay = Math.max(0, target.getTime() - Date.now());

  window.setTimeout(() => {
    showLocalizedNotification(slot);
    scheduleDailyNotification(hours, minutes, slot);
  }, delay);
};

export const requestPushPermission = async (): Promise<NotificationPermission | 'unsupported'> => {
  if (!isBrowserNotificationSupported()) {
    return 'unsupported';
  }

  if (Notification.permission === 'granted') {
    return 'granted';
  }

  return Notification.requestPermission();
};

export const setPushSchedulerEnabled = (enabled: boolean) => {
  localStorage.setItem(SCHEDULE_KEY, enabled ? '1' : '0');
};

export const isPushSchedulerEnabled = (): boolean => {
  return localStorage.getItem(SCHEDULE_KEY) === '1';
};

export const initPushScheduler = () => {
  if (initialized) {
    return;
  }

  if (!isBrowserNotificationSupported()) {
    return;
  }

  if (Notification.permission !== 'granted' || !isPushSchedulerEnabled()) {
    return;
  }

  initialized = true;
  scheduleDailyNotification(11, 30, 'open');
  scheduleDailyNotification(13, 0, 'closing');
};
