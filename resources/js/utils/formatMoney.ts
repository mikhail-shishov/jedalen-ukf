const DEFAULT_LOCALE = 'sk-SK';

export const formatMoney = (value: number | string | null | undefined): string => {
  const normalizedValue = typeof value === 'string' ? value.replace(',', '.') : value;
  const numericValue = Number(normalizedValue ?? 0);

  if (Number.isNaN(numericValue)) {
    return `0,00\u00A0€`;
  }

  const formatted = new Intl.NumberFormat(DEFAULT_LOCALE, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(numericValue);

  return `${formatted}\u00A0€`;
};
