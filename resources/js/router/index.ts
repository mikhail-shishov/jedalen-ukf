import { createRouter, createWebHistory } from "vue-router";
import MenuView from "@/views/MenuView.vue";
import NotFound from "@/views/NotFound.vue";
import ArticleView from "@/views/ArticleView.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/",
      name: "menu",
      component: MenuView,
      meta: { title: 'Jedáleň UKF - Menu' },
    },
    {
      path: "/profile",
      name: "profile",
      component: () => import("@/views/ProfileView.vue"),
      meta: { title: 'Jedáleň UKF - Profil' },
    },
    {
      path: "/payment",
      name: "payment",
      component: () => import("@/views/PaymentsView.vue"),
      meta: { title: 'Jedáleň UKF - Platby' },
    },
    {
      path: "/articles/:slug",
      name: "article",
      component: ArticleView,
      meta: { title: 'Jedáleň UKF - Článok' },
    },
    {
      path: "/history",
      name: "history",
      component: () => import("@/views/HistoryView.vue"),
      meta: { title: 'Jedáleň UKF - História' },
    },
    {
      path: "/settings",
      name: "settings",
      component: () => import("@/views/SettingsView.vue"),
      meta: { title: 'Jedáleň UKF - Nastavenia' },
    },
    {
      path: "/statistics",
      name: "statistics",
      component: () => import("@/views/StatisticsView.vue"),
      meta: { title: 'Jedáleň UKF - Štatistiky' },
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'NotFound',
      component: NotFound,
      meta: { title: 'Jedáleň UKF - Stránka nenájdená' },
    }
  ],
});

router.afterEach((to) => {
  const title = typeof to.meta?.title === 'string' ? to.meta.title : 'Jedáleň UKF';
  document.title = title;
});

export default router;
