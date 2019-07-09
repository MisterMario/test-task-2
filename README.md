## Информация
Наименование: Динамика курса доллара США к русскому рублю<br />
Тип: веб-приложение.<br />
Фронденд: HTML, CSS, JS.<br />
Бэкенд: PHP.<br />

## Описание
Приложение позволяет получить график изменения курса доллара США в русских рублях за заданный период.<br />
Период за который будут выбираться данные задает пользователь.<br />
Данные о курсе валюты на конкретные даты извлекаются с сайта Центрального банка России.<br />
Все данные, запрашиваемые с сайта Центрбанка кэшируются на сервере и впоследствии берутся уже из кэша.<br />

Описание кэша:
- формат хранимых данных: JSON;
- файл: cache.json.

По умолчанию все запросы к серверу сохраняются лог (/php/log.txt).<br />
В лог записывается:
- когда был выполнен запрос (mm:hh dd:mm:YYYY);
- период за который запрошены данные;
- откуда были получены данные: кэш или сервер ЦБР.
<br />
Для построения графика на клиенте используется библиотека: Chart.js<br />

## Установка и использование
Условия:
- PHP 5.5+

Для того, чтобы использовать приложение, достаточно загрузить файлы проекта на сервер.<br />
После чего обратиться к индексной странице "index.html", заполнить поля:
- начало периода;
- конец периода;

и нажать кнопку "Сформировать график".
