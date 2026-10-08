# Проект

Сайт студии дизайна интерьеров «Дизайн-Цех Ольги Бараевой» (Екатеринбург). Боевой домен — `дизайн-цех.рф` (`xn----7sblghfj6a4ei.xn--p1ai`).

## Стек

- Статический HTML, собирается gulp 4: `gulp-file-include`, typograf, htmlclean.
- SCSS → `css/main.css` (точка входа `src/scss/main.scss`, базовые стили в `src/scss/base`, блоки в `src/scss/blocks`).
- JS: webpack + babel → `js/index.bundle.js` (точка входа `src/js/index.js`). Слайдеры на Splide.
- Форма заявки: `src/files/mail.php`, отправляет через PHPMailer по SMTP mail.ru на `ekb.design@mail.ru`.
- Галерея — WordPress, подробно в [wordpress-gallery.md](wordpress-gallery.md).

## Структура

```
src/
  html/            страницы; *.html собираются в корень сборки
    blocks/        подключаемые блоки (@@include), в сборку отдельно не попадают
    gallery/       статические страницы проектов (в WordPress-сборку не попадают)
  scss/  js/  img/  fonts/
  files/           mail.php + PHPMailer
  .htaccess        для чистой статики
  wp/
    .htaccess      для сайта с WordPress
    theme/         тема WordPress «Дизайн-Цех»
gulp/              задачи: dev.js (build/), docs.js (docs/), wp.js (тема)
docs/              результат сборки (в .gitignore)
build/             результат dev-сборки (в .gitignore)
agents/            эта документация
```

## Страницы

Главная, цены (`prices`, `planning`, `project`, `turnkey`), контакты, политики — статика. Подключение блоков видно в начале каждого `src/html/*.html`.

Галерея:
- `/gallery` — разделы («Визуализации / Реальность», «Дизайн квартир», «Дизайн загородных домов», «Современный интерьер», «Классика»), в каждом слайдер карточек. Статическая версия — `src/html/blocks/gallery/gallery__content.html`.
- `/gallery/<slug>` — страница проекта: заголовок, описание, сетка фото с просмотром в модальном окне, иногда «история проекта». Статические версии — `src/html/gallery/*.html` (19 штук).

## Вёрстка галереи, на которую опирается JS

- Карточки: `.gallery__section > .splide.gallery__slider > .splide__track > ul.splide__list.gallery__list > li.splide__slide > a.gallery__item`. Слайдер включается на ширине < 820px (`src/js/blocks/gallery/gallery.js`).
- Страница проекта: `.splide.works__slider … ul.works__gallery > li.works__gallery-item > img.works__img.js-modal-image`. Слайдер на ширине < 768px. Модальное окно (`src/js/blocks/modal/image-modal.js`) берёт `data-full-src` или `src`.
- Тема WordPress выводит ту же разметку, поэтому CSS и JS общие. Меняешь классы — меняй и тему, и статику.

## Аналитика

Google Analytics (`G-0RHHHNYSGF`) грузится только после согласия на cookie. Код в блоке `src/html/blocks/analytics.html` (подключён в теме). В статических страницах он пока вставлен инлайном.
