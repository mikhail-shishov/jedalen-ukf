<template>
  <footer class="footer">
    <div class="container">

      <ul class="footer__links">
        <li class="footer__item">
          <a href="#" class="footer__link">Zoznam alergénov</a>
        </li>
        <li class="footer__item">
          <a href="#" class="footer__link">Objednávanie a možnosti platby</a>
        </li>
      </ul>

      <div class="footer__bottom">
        <p class="footer__text">
          © {{ new Date().getFullYear() }} Univerzita Konštantína Filozofa v Nitre
        </p>

        <p class="footer__info">
          CARDPAY, Verzia: 3.0.0<br>
          Intranetové objednávanie stravy
        </p>
      </div>
    </div>
  </footer>
</template>

<style lang="scss">
.footer {
  background-color: $grey1;
  padding: 45px 0;
  margin-top: auto;
  color: white;

  &__links {
    list-style: none;
    padding-left: 0;
  }

  &__item {
    margin: 10px 0;
  }

  &__link {
    text-decoration: underline;
    color: white;
  }

  &__bottom {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;

    @media (max-width: 992px) {
      flex-direction: column;
      align-items: baseline;
      justify-content: space-between;
    }
  }
}
</style>