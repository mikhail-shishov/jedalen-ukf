<script lang="ts">
export default {
  name: "TheHeader",
  setup() {

  }
}
</script>

<template>
  <header class="header">
    <div class="container">
      <nav class="navbar">
        <RouterLink to="/" class="logo">
          <img src="/logo.svg" width="130" height="24" alt="UKF" />
        </RouterLink>
        <ul class="navbar__list">
          <li><a href="#about">About</a></li>
          <li><a href="#benefits">Benefits</a></li>
          <li><a href="form">Form</a></li>
        </ul>
      </nav>

    </div>
  </header>
</template>

<style lang="scss"></style>