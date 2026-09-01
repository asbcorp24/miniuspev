# MiniUspev

Система учета успеваемости и посещаемости студентов на Laravel 9 + SQLite + Bootstrap + JavaScript/AJAX.

## Возможности MVP

- группы и студенты;
- дисциплины;
- занятия по датам;
- посещаемость: присутствовал / отсутствовал / опоздал / уважительная причина;
- оценки 2–5;
- комментарий преподавателя;
- средний балл студента;
- процент посещаемости;
- журнал группы по дисциплине;
- быстрый ввод данных без перезагрузки страницы;
- сводный дашборд.

## Установка

```bash
composer install
cp .env.example .env
php artisan key:generate
mkdir -p database
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Открыть: http://127.0.0.1:8000

Тестовый преподаватель после seed:

- Email: `teacher@example.com`
- Пароль: `password`

> Для первого запуска требуется PHP 8.0.2+, Composer и расширение PDO SQLite.
