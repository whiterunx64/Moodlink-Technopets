const DEFAULT_TIMEZONE = 'Asia/Manila';

function localDateFormatter(timezone: string): Intl.DateTimeFormat {
  return new Intl.DateTimeFormat('en-CA', {
    timeZone: timezone,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  });
}

// Derives the actual UTC offset string (e.g. "+08:00") for any IANA timezone.
// Replaces the brittle hardcoded offset in the old getTodayStartISO.
function resolveTimezoneOffset(timezone: string): string {
  const parts = new Intl.DateTimeFormat('en', {
    timeZone: timezone,
    timeZoneName: 'shortOffset',
  }).formatToParts(new Date());

  const raw = parts.find(p => p.type === 'timeZoneName')?.value ?? 'GMT';
  const match = raw.match(/^GMT([+-])(\d+)(?::(\d+))?$/);
  if (!match) return '+00:00';

  const [, sign, hours, mins = '0'] = match;
  return `${sign}${hours.padStart(2, '0')}:${mins.padStart(2, '0')}`;
}

export function getTodayLocalDate(timezone = DEFAULT_TIMEZONE): string {
  return localDateFormatter(timezone).format(new Date());
}

export function getTodayStartISO(timezone = DEFAULT_TIMEZONE): string {
  const dateStr = getTodayLocalDate(timezone);
  const offset = resolveTimezoneOffset(timezone);
  return `${dateStr}T00:00:00${offset}`;
}

export function formatRelativeDate(datetime: string, timezone = DEFAULT_TIMEZONE): string {
  const date = new Date(datetime);
  const fmt = localDateFormatter(timezone);
  const dateStr = fmt.format(date);
  const todayStr = getTodayLocalDate(timezone);

  // Split today into parts to safely add 1 calendar day without DST issues.
  // Adding 86400000ms is wrong when a day is 23h or 25h due to DST.
  const [y, m, d] = todayStr.split('-').map(Number);
  const tomorrowStr = fmt.format(new Date(y, m - 1, d + 1));

  if (dateStr === todayStr) return 'Today';
  if (dateStr === tomorrowStr) return 'Tomorrow';

  return new Intl.DateTimeFormat('en-US', {
    timeZone: timezone,
    month: 'short',
    day: 'numeric',
  }).format(date);
}

export function formatTime(datetime: string, timezone = DEFAULT_TIMEZONE): string {
  return new Intl.DateTimeFormat('en-US', {
    timeZone: timezone,
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  }).format(new Date(datetime));
}
