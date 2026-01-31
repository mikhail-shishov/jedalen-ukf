<script lang="ts">

export default {
  name: "TheMenu",
  setup() {
  }
}
</script>

<template>
  <section class="menu-section">
    <div class="container">
      <div class="menu">
        <div class="menu__head">
          <span class="menu__title">Vyberte si jedaleň:</span>
        </div>

        <div class="menu__body">
          <div class="menu__row">
            <div class="menu__col">
              <h2 class="menu__date">pondelok 14. októbra 2024</h2>

              <div class="menu-card">
                <span class="menu-card__badge">Obed M1</span>
                <p class="menu-card__name">
                  Brav.rezeň na rošte, zemiaky, šalát 1,10 pol.hov.vývar s cestovinou 1,3,9
                </p>
                <a href="" class="menu-card__link">Viac informácií</a>
                <span class="menu-card__price">4,30 €</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style lang="scss">
.menu-card {
  border: 1px solid $grey3;
  padding: 16px;

  &__badge {
    background-color: $blue1;
    color: white;
    padding: 4px 8px;
  }

  &__name {
    font-weight: bold;
    margin: 10px 0;
  }

  &__price {
    display: block;
    color: $green1;
    font-weight: 700;
  }
}
</style>