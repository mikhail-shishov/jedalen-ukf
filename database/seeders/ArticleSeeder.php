<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('articles') || !Schema::hasTable('users')) {
            return;
        }

        $authorId = (int) (DB::table('users')->where('role_id', 4)->value('id')
            ?? DB::table('users')->value('id'));

        if (!$authorId) {
            return;
        }

        $articles = [
            [
                'slug' => 'jarne-menu-zmeny',
                'title_sk' => 'Jarné zmeny v jedálnom lístku',
                'title_en' => 'Spring menu updates',
                'title_ua' => 'Весняні зміни в меню',
                'title_ru' => 'Весенние изменения в меню',
                'content_sk' => 'Od budúceho týždňa pridávame viac vegetariánskych jedál a sezónne polievky.',
                'content_en' => 'Starting next week we are adding more vegetarian meals and seasonal soups.',
                'content_ua' => 'З наступного тижня додаємо більше вегетаріанських страв і сезонних супів.',
                'content_ru' => 'Со следующей недели добавляем больше вегетарианских блюд и сезонные супы.',
                'is_published' => 1,
            ],
            [
                'slug' => 'otvaracie-hodiny',
                'title_sk' => 'Zmena otváracích hodín počas skúškového',
                'title_en' => 'Opening hours during exam period',
                'title_ua' => 'Години роботи під час сесії',
                'title_ru' => 'Часы работы в период сессии',
                'content_sk' => 'Počas skúškového obdobia bude jedáleň otvorená od 10:30 do 14:00.',
                'content_en' => 'During exams the canteen will be open from 10:30 to 14:00.',
                'content_ua' => 'Під час сесії їдальня працюватиме з 10:30 до 14:00.',
                'content_ru' => 'Во время сессии столовая будет открыта с 10:30 до 14:00.',
                'is_published' => 1,
            ],
            [
                'slug' => 'nova-burza-jedal',
                'title_sk' => 'Ako funguje burza jedál',
                'title_en' => 'How meal exchange works',
                'title_ua' => 'Як працює біржа обідів',
                'title_ru' => 'Как работает биржа обедов',
                'content_sk' => 'Objednávku môžete zrušiť a automaticky ju ponúknuť na burze.',
                'content_en' => 'You can cancel an order and automatically list it on the exchange.',
                'content_ua' => 'Ви можете скасувати замовлення і автоматично виставити його на біржу.',
                'content_ru' => 'Вы можете отменить заказ и автоматически выставить его на биржу.',
                'is_published' => 1,
            ],
        ];

        foreach ($articles as $article) {
            DB::table('articles')->updateOrInsert(
                ['slug' => $article['slug']],
                [
                    'title_sk' => $article['title_sk'],
                    'title_en' => $article['title_en'],
                    'title_ua' => $article['title_ua'],
                    'title_ru' => $article['title_ru'],
                    'content_sk' => $article['content_sk'],
                    'content_en' => $article['content_en'],
                    'content_ua' => $article['content_ua'],
                    'content_ru' => $article['content_ru'],
                    'is_published' => $article['is_published'],
                    'users_id' => $authorId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        if (Schema::hasTable('articles_has_canteens') && Schema::hasTable('canteens')) {
            $canteenIds = DB::table('canteens')->pluck('id')->values()->all();
            $articleIds = DB::table('articles')->whereIn('slug', collect($articles)->pluck('slug'))->pluck('id')->values()->all();

            foreach ($articleIds as $index => $articleId) {
                if (!$canteenIds) {
                    break;
                }

                $primary = $canteenIds[$index % count($canteenIds)];
                DB::table('articles_has_canteens')->updateOrInsert(
                    ['articles_id' => $articleId, 'canteens_id' => $primary],
                    ['articles_id' => $articleId, 'canteens_id' => $primary]
                );
            }
        }
    }
}
