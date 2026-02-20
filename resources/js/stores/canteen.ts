import { defineStore } from 'pinia';

export const useCanteenStore = defineStore('canteen', {
  state: () => ({
    currentCanteen: 'Tr.A.Hlinku',
    canteens: ['Tr.A.Hlinku', 'Stefanikova', 'Kraskova', 'Internat Zobor']
  }),
  actions: {
    setCanteen(name: string) {
      this.currentCanteen = name;
    }
  }
});