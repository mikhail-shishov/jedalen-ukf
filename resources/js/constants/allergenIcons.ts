export const allergenIconMap: Record<number, string> = {
  0: new URL('../../assets/img/icons/allergens/0-meat.svg', import.meta.url).href,
  1: new URL('../../assets/img/icons/allergens/1-wheat.svg', import.meta.url).href,
  2: new URL('../../assets/img/icons/allergens/2-crab.svg', import.meta.url).href,
  3: new URL('../../assets/img/icons/allergens/3-egg.svg', import.meta.url).href,
  4: new URL('../../assets/img/icons/allergens/4-fish.svg', import.meta.url).href,
  5: new URL('../../assets/img/icons/allergens/5-peanut.svg', import.meta.url).href,
  6: new URL('../../assets/img/icons/allergens/6-soy.svg', import.meta.url).href,
  7: new URL('../../assets/img/icons/allergens/7-milk.svg', import.meta.url).href,
  8: new URL('../../assets/img/icons/allergens/8-nut.svg', import.meta.url).href,
  9: new URL('../../assets/img/icons/allergens/9-celery.svg', import.meta.url).href,
  10: new URL('../../assets/img/icons/allergens/10-mustard.svg', import.meta.url).href,
  11: new URL('../../assets/img/icons/allergens/11-sesame.svg', import.meta.url).href,
  12: new URL('../../assets/img/icons/allergens/12-oxide.svg', import.meta.url).href,
  13: new URL('../../assets/img/icons/allergens/13-lupine.svg', import.meta.url).href,
  14: new URL('../../assets/img/icons/allergens/14-mollusca.svg', import.meta.url).href,
};

export const getAllergenIconUrl = (number: number): string | null => {
  return allergenIconMap[number] ?? null;
};
