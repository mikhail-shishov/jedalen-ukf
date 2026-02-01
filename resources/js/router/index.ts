import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';
import TheMenu from '@/views/TheMenu.vue';

const routes: Array<RouteRecordRaw> = [
    {
        path: '/',
        name: 'homepage',
        component: TheMenu
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;