# Iris Machine

Landing page de venda da consultoria em IA (agentes e skills) para devs júnior. Laravel 13 + Blade + Vite + GSAP.

## Rodando

Com PHP 8.3+ e Composer instalados:

```sh
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate
npm install && npm run build   # ou `npm run dev` para hot reload
php artisan serve
```

Sem PHP na máquina, via Docker:

```sh
npm install && npm run build
docker compose up              # http://localhost:8000
docker compose run --rm app php artisan test
```

## Onde mexer

| O quê | Onde |
| --- | --- |
| Textos, módulos, formatos, preços, FAQ | `config/iris.php` |
| Seções da página | `resources/views/partials/` |
| Estilos e tokens de cor | `resources/css/app.css` |
| Animações (GSAP + ScrollTrigger) | `resources/js/app.js` |
| Captura de leads | `LeadController`, `StoreLeadRequest`, tabela `leads` |

Os leads ficam na tabela `leads` (SQLite por padrão, em `database/database.sqlite`).
A página original de arquivo único está preservada em `legacy/index.html`.
