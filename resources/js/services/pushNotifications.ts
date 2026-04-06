import { i18n } from '@/i18n';

const SCHEDULE_KEY = 'push_scheduler_enabled';
const SELECTED_CANTEEN_STORAGE_KEY = 'selected_canteen_id';
const SELECTED_CANTEEN_COOKIE_KEY = 'selected_canteen_id';
const CANTEEN_DATA_STORAGE_KEY = 'canteens_data';
const LOOKAHEAD_DAYS = 14;
let initialized = false;
let scheduledTimeouts: number[] = [];

type Slot = 'open' | 'closing';
type WeekdayKey = 'mon' | 'tue' | 'wed' | 'thu' | 'fri' | 'sat' | 'sun';

interface CanteenClosure {
  date: string;
  is_closed: boolean;
  open_time: string | null;
  close_time: string | null;
}

interface CanteenScheduleDay {
  open_time: string | null;
  close_time: string | null;
}

interface CanteenSchedule {
  mon: CanteenScheduleDay;
  tue: CanteenScheduleDay;
  wed: CanteenScheduleDay;
  thu: CanteenScheduleDay;
  fri: CanteenScheduleDay;
  sat: CanteenScheduleDay;
  sun: CanteenScheduleDay;
}

interface CanteenForNotifications {
  id: number;
  timezone: string;
  notifications_enabled: boolean;
  notify_open_offset_min: number;
  notify_close_offset_min: number;
  schedule: CanteenSchedule;
  closures: CanteenClosure[];
}

const isBrowserNotificationSupported = () => {
  return typeof window !== 'undefined' && 'Notification' in window;
};

const clearScheduledTimeouts = () => {
  scheduledTimeouts.forEach((timer) => window.clearTimeout(timer));
  scheduledTimeouts = [];
};

const showLocalizedNotification = (slot: Slot) => {
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

const parseTime = (value: string | null): { hours: number; minutes: number } | null => {
  if (!value) {
    return null;
  }

  const match = value.match(/^(\d{1,2}):(\d{2})/);
  if (!match) {
    return null;
  }

  const hours = Number(match[1]);
  const minutes = Number(match[2]);

  if (!Number.isFinite(hours) || !Number.isFinite(minutes) || hours < 0 || hours > 23 || minutes < 0 || minutes > 59) {
    return null;
  }

  return { hours, minutes };
};

const getZonedDateParts = (date: Date, timeZone: string): {
  year: number;
  month: number;
  day: number;
  weekday: WeekdayKey;
} | null => {
  const parts = new Intl.DateTimeFormat('en-US', {
    timeZone,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    weekday: 'short',
    hourCycle: 'h23',
  }).formatToParts(date);

  const values: Record<string, string> = {};
  parts.forEach((part) => {
    if (part.type !== 'literal') {
      values[part.type] = part.value;
    }
  });

  const year = Number(values.year);
  const month = Number(values.month);
  const day = Number(values.day);
  const weekdayToken = String(values.weekday || '').slice(0, 3).toLowerCase();
  const weekdayMap: Record<string, WeekdayKey> = {
    mon: 'mon',
    tue: 'tue',
    wed: 'wed',
    thu: 'thu',
    fri: 'fri',
    sat: 'sat',
    sun: 'sun',
  };

  if (!Number.isInteger(year) || !Number.isInteger(month) || !Number.isInteger(day) || !weekdayMap[weekdayToken]) {
    return null;
  }

  return {
    year,
    month,
    day,
    weekday: weekdayMap[weekdayToken],
  };
};

const getTimeZoneOffsetMinutes = (date: Date, timeZone: string): number => {
  const parts = new Intl.DateTimeFormat('en-US', {
    timeZone,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hourCycle: 'h23',
  }).formatToParts(date);

  const values: Record<string, string> = {};
  parts.forEach((part) => {
    if (part.type !== 'literal') {
      values[part.type] = part.value;
    }
  });

  const zonedTimestamp = Date.UTC(
    Number(values.year),
    Number(values.month) - 1,
    Number(values.day),
    Number(values.hour),
    Number(values.minute),
    Number(values.second),
  );

  return (zonedTimestamp - date.getTime()) / 60000;
};

const createZonedDateTime = (
  year: number,
  month: number,
  day: number,
  hour: number,
  minute: number,
  second: number,
  timeZone: string,
): Date | null => {
  if (!Number.isInteger(year) || !Number.isInteger(month) || !Number.isInteger(day)) {
    return null;
  }

  const utcGuess = Date.UTC(year, month - 1, day, hour, minute, second);
  const guessedDate = new Date(utcGuess);
  const offsetMinutes = getTimeZoneOffsetMinutes(guessedDate, timeZone);

  return new Date(utcGuess - (offsetMinutes * 60 * 1000));
};

const formatDateKey = (date: Date): string => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const weekdayKey = (date: Date): WeekdayKey => {
  const mapping: WeekdayKey[] = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
  return mapping[date.getDay()];
};

const readSelectedCanteenId = (): number | null => {
  const localValue = localStorage.getItem(SELECTED_CANTEEN_STORAGE_KEY);
  const localId = Number(localValue);
  if (Number.isFinite(localId) && localId > 0) {
    return localId;
  }

  const cookieEntry = document.cookie
    .split('; ')
    .find((chunk) => chunk.startsWith(`${SELECTED_CANTEEN_COOKIE_KEY}=`));

  if (!cookieEntry) {
    return null;
  }

  const cookieId = Number(cookieEntry.split('=')[1]);
  return Number.isFinite(cookieId) && cookieId > 0 ? cookieId : null;
};

const readCachedCanteens = (): CanteenForNotifications[] => {
  const raw = localStorage.getItem(CANTEEN_DATA_STORAGE_KEY);
  if (!raw) {
    return [];
  }

  try {
    const parsed = JSON.parse(raw) as unknown;
    const records = Array.isArray(parsed)
      ? parsed
      : parsed && typeof parsed === 'object' && Array.isArray((parsed as { items?: unknown }).items)
        ? ((parsed as { items: unknown[] }).items)
        : null;

    if (!records) {
      return [];
    }

    return records
      .map((item) => {
        if (!item || typeof item !== 'object') {
          return null;
        }

        const record = item as Record<string, unknown>;
        const id = Number(record.id);
        if (!Number.isFinite(id) || id <= 0) {
          return null;
        }

        const schedule = record.schedule && typeof record.schedule === 'object'
          ? (record.schedule as CanteenSchedule)
          : {
            mon: { open_time: null, close_time: null },
            tue: { open_time: null, close_time: null },
            wed: { open_time: null, close_time: null },
            thu: { open_time: null, close_time: null },
            fri: { open_time: null, close_time: null },
            sat: { open_time: null, close_time: null },
            sun: { open_time: null, close_time: null },
          };

        return {
          id,
          timezone: String(record.timezone ?? 'Europe/Bratislava'),
          notifications_enabled: record.notifications_enabled === undefined ? true : Boolean(record.notifications_enabled),
          notify_open_offset_min: Number(record.notify_open_offset_min ?? 30),
          notify_close_offset_min: Number(record.notify_close_offset_min ?? 30),
          schedule,
          closures: Array.isArray(record.closures) ? (record.closures as CanteenClosure[]) : [],
        };
      })
      .filter((item): item is CanteenForNotifications => item !== null);
  } catch {
    return [];
  }
};

const resolveScheduleForDate = (canteen: CanteenForNotifications, dateKey: string, weekday: WeekdayKey): CanteenScheduleDay => {
  const daySchedule = canteen.schedule[weekday] ?? { open_time: null, close_time: null };
  const closure = canteen.closures.find((item) => item.date === dateKey);

  if (!closure) {
    return daySchedule;
  }

  if (closure.is_closed) {
    return { open_time: null, close_time: null };
  }

  return {
    open_time: closure.open_time ?? daySchedule.open_time,
    close_time: closure.close_time ?? daySchedule.close_time,
  };
};

const applyOffsetMinutes = (base: Date, offsetMin: number): Date => {
  const candidate = new Date(base);
  candidate.setMinutes(candidate.getMinutes() - offsetMin);
  return candidate;
};

const buildEventTime = (
  year: number,
  month: number,
  day: number,
  value: string | null,
  timeZone: string,
): Date | null => {
  const parsed = parseTime(value);
  if (!parsed) {
    return null;
  }

  return createZonedDateTime(year, month, day, parsed.hours, parsed.minutes, 0, timeZone);
};

const findNextEvent = (canteen: CanteenForNotifications, slot: Slot): Date | null => {
  const now = new Date();
  const timeZone = canteen.timezone || 'Europe/Bratislava';
  const offset = slot === 'open' ? canteen.notify_open_offset_min : canteen.notify_close_offset_min;

  const todayInZone = getZonedDateParts(now, timeZone);
  if (!todayInZone) {
    return null;
  }

  const dayCursor = createZonedDateTime(
    todayInZone.year,
    todayInZone.month,
    todayInZone.day,
    12,
    0,
    0,
    timeZone,
  );

  if (!dayCursor) {
    return null;
  }

  for (let index = 0; index < LOOKAHEAD_DAYS; index += 1) {
    const day = new Date(dayCursor);
    day.setUTCDate(dayCursor.getUTCDate() + index);

    const zonedDay = getZonedDateParts(day, timeZone);
    if (!zonedDay) {
      continue;
    }

    const dateKey = `${zonedDay.year}-${String(zonedDay.month).padStart(2, '0')}-${String(zonedDay.day).padStart(2, '0')}`;
    const schedule = resolveScheduleForDate(canteen, dateKey, zonedDay.weekday);
    const anchorTime = slot === 'open' ? schedule.open_time : schedule.close_time;
    const anchorDate = buildEventTime(zonedDay.year, zonedDay.month, zonedDay.day, anchorTime, timeZone);

    if (!anchorDate) {
      continue;
    }

    const candidate = applyOffsetMinutes(anchorDate, Number.isFinite(offset) ? offset : 0);
    if (candidate.getTime() > now.getTime()) {
      return candidate;
    }
  }

  return null;
};

const scheduleNotification = (target: Date, slot: Slot) => {
  const delay = Math.max(0, target.getTime() - Date.now());

  const timeoutId = window.setTimeout(() => {
    showLocalizedNotification(slot);
    refreshPushScheduler();
  }, delay);

  scheduledTimeouts.push(timeoutId);
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
  refreshPushScheduler();
};

export const isPushSchedulerEnabled = (): boolean => {
  return localStorage.getItem(SCHEDULE_KEY) === '1';
};

export const initPushScheduler = () => {
  initialized = true;
  refreshPushScheduler();
};

export const refreshPushScheduler = () => {
  if (!isBrowserNotificationSupported()) {
    clearScheduledTimeouts();
    return;
  }

  if (!initialized) {
    return;
  }

  clearScheduledTimeouts();

  if (Notification.permission !== 'granted' || !isPushSchedulerEnabled()) {
    return;
  }

  const selectedCanteenId = readSelectedCanteenId();
  if (selectedCanteenId === null) {
    return;
  }

  const selectedCanteen = readCachedCanteens().find((canteen) => canteen.id === selectedCanteenId);
  if (!selectedCanteen || !selectedCanteen.notifications_enabled) {
    return;
  }

  const nextOpen = findNextEvent(selectedCanteen, 'open');
  const nextClosing = findNextEvent(selectedCanteen, 'closing');

  if (nextOpen) {
    scheduleNotification(nextOpen, 'open');
  }

  if (nextClosing) {
    scheduleNotification(nextClosing, 'closing');
  }

  if (!nextOpen && !nextClosing) {
    const timeoutId = window.setTimeout(() => {
      refreshPushScheduler();
    }, 6 * 60 * 60 * 1000);
    scheduledTimeouts.push(timeoutId);
  }
};
