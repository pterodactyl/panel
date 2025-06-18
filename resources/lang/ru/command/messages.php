<?php

return [
    'location' => [
        'no_location_found' => 'Не удалось найти запись, соответствующую заданному короткому коду.',
        'ask_short' => 'Короткий код местоположения',
        'ask_long' => 'Описание местоположения',
        'created' => 'Новый локейшн (:name) успешно создан с ID :id.',
        'deleted' => 'Запрошенное местоположение успешно удалено.',
    ],
    'user' => [
        'search_users' => 'Введите имя пользователя, ID или email',
        'select_search_user' => 'ID пользователя для удаления (введите \'0\' для повторного поиска)',
        'deleted' => 'Пользователь успешно удалён из панели.',
        'confirm_delete' => 'Вы уверены, что хотите удалить этого пользователя из панели?',
        'no_users_found' => 'По запросу не найдено пользователей.',
        'multiple_found' => 'Найдено несколько аккаунтов, нельзя удалить из-за флага --no-interaction.',
        'ask_admin' => 'Это администратор?',
        'ask_email' => 'Email',
        'ask_username' => 'Имя пользователя',
        'ask_name_first' => 'Имя',
        'ask_name_last' => 'Фамилия',
        'ask_password' => 'Пароль',
        'ask_password_tip' => 'Если хотите создать аккаунт с рандомным паролем, отправленным по email, перезапустите команду (CTRL+C) с флагом `--no-password`.',
        'ask_password_help' => 'Пароль должен быть не менее 8 символов, содержать хотя бы одну заглавную букву и цифру.',
        '2fa_help_text' => [
            'Эта команда отключит двухфакторную аутентификацию для аккаунта пользователя, если она включена. Используйте только для восстановления доступа при блокировке.',
            'Если это не то, что вы хотели сделать, нажмите CTRL+C для выхода.',
        ],
        '2fa_disabled' => 'Двухфакторная аутентификация отключена для :email.',
    ],
    'schedule' => [
        'output_line' => 'Запуск задачи для первого действия в `:schedule` (:hash).',
    ],
    'maintenance' => [
        'deleting_service_backup' => 'Удаление файла бэкапа сервиса :file.',
    ],
    'server' => [
        'rebuild_failed' => 'Ошибка при перестройке ":name" (#:id) на узле ":node": :message',
        'reinstall' => [
            'failed' => 'Ошибка при переустановке ":name" (#:id) на узле ":node": :message',
            'confirm' => 'Вы собираетесь переустановить группу серверов. Продолжить?',
        ],
        'power' => [
            'confirm' => 'Вы собираетесь выполнить :action на :count серверах. Продолжить?',
            'action_failed' => 'Ошибка выполнения действия питания для ":name" (#:id) на узле ":node": :message',
        ],
    ],
    'environment' => [
        'mail' => [
            'ask_smtp_host' => 'SMTP сервер (например smtp.gmail.com)',
            'ask_smtp_port' => 'SMTP порт',
            'ask_smtp_username' => 'SMTP имя пользователя',
            'ask_smtp_password' => 'SMTP пароль',
            'ask_mailgun_domain' => 'Домен Mailgun',
            'ask_mailgun_endpoint' => 'Точка входа Mailgun',
            'ask_mailgun_secret' => 'Секрет Mailgun',
            'ask_mandrill_secret' => 'Секрет Mandrill',
            'ask_postmark_username' => 'Ключ API Postmark',
            'ask_driver' => 'Какой драйвер использовать для отправки почты?',
            'ask_mail_from' => 'Email, от которого будут отправляться письма',
            'ask_mail_name' => 'Имя, от которого будут приходить письма',
            'ask_encryption' => 'Метод шифрования',
        ],
    ],
];
