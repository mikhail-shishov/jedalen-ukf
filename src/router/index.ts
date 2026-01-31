import { createRouter, createWebHistory } from "vue-router";
import MenuView from "../views/MenuView.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [ 
    {
      path: "/",
      name: "menu",
      component: MenuView,
    },
    {
      path: "/profile",
      name: "profile",
      component: () => import("../views/ProfileView.vue"),
    },
    {
      path: "/payment",
      name: "payment",
      component: () => import("../views/PaymentView.vue"),
    },
    {
      path: "/thank-you",
      name: "thank-you",
      component: () => import("../views/ThankYouView.vue"),
    },
    {
      path: "/history",
      name: "history",
      component: () => import("../views/HistoryView.vue"),
    },
    {
      path: "/settings",
      name: "settings",
      component: () => import("../views/SettingsView.vue"),
    }
  ],
});

export default router;