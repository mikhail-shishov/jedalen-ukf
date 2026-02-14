<script lang="ts">

export default {
  name: "TheMenu",
  setup() {
  }
}
</script>

<template>
  <div class="container">
    <div class="menu">
      <div class="menu__head">
        <span class="menu__title">Vyberte si jedaleň:</span>
        <span>Ak nevidíte niektoré položky, skontrolujte si voľbu alergenov v Nastaveniach.</span>
      </div>

      <div class="menu__body">
        <div class="menu__row">
          <div class="menu__col">
            <h2 class="menu__date"><b>pondelok</b> 14. októbra 2024</h2>

            <div class="menu-card">
              <span class="menu-card__badge">Obed M1</span>
              <p class="menu-card__name">
                <span>1.</span>
                <span>Brav.rezeň na rošte, zemiaky, šalát 1,10 pol.hov.vývar s cestovinou 1,3,9</span>
              </p>
              <div class="menu-card__info">
                <a href="" class="menu-card__link">Viac informácií</a>
                <span class="menu-card__price">4,30 €</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss">
.menu {
  &__head {
    background-color: $lightblue2;
    border-radius: 8px 8px 0 0;
    padding: 20px 35px;
  }

  &__body {
    background-color: white;
    padding: 26px 0 30px;
  }

  &__date {
    margin: 0 0 25px 35px;
    color: $grey1;
    font-size: 24px;
    font-weight: 400;

    b {
      font-weight: 600;
    }
  }

  &__title {
    color: white;
    font-size: 18px;
  }

  &-card {
    padding: 16px 16px 16px 35px;
    position: relative;

    &:nth-child(even) {
      background-color: $grey4;
    }

    &__badge {
      color: $grey5;
      font-weight: 700;
      font-size: 12px;
    }

    &__name {
      font-size: 18px;
      margin: 10px 0;
      display: flex;
      align-items: flex-start;
      gap: 6px;
    }

    &__price {
      display: block;
      color: $green1;
      font-weight: 700;
    }

    &__info {
      display: flex;
      align-items: center;
    }

    &__price {
      margin-left: auto;
    }

    &__link {
      color: $grey1;
      text-decoration: none;
    }
  }

  &__row {
    display: flex;
  }

  &__col {
    flex-basis: 50%;
    flex-grow: 1;
  }
}
</style>