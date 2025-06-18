<?php

return [
    'auth' => [
        'fail' => 'Неудачный вход в систему',
        'success' => 'Вход в систему выполнен',
        'password-reset' => 'Сброс пароля',
        'reset-password' => 'Запрошен сброс пароля',
        'checkpoint' => 'Запрошена двухфакторная аутентификация',
        'recovery-token' => 'Использован токен восстановления 2FA',
        'token' => 'Проверка двухфакторной аутентификации пройдена',
        'ip-blocked' => 'Блокирован запрос с неразрешённого IP-адреса для :identifier',
        'sftp' => [
            'fail' => 'Неудачный вход через SFTP',
        ],
    ],
    'user' => [
        'account' => [
            'email-changed' => 'Email изменён с :old на :new',
            'password-changed' => 'Пароль изменён',
        ],
        'api-key' => [
            'create' => 'Создан новый API-ключ :identifier',
            'delete' => 'Удалён API-ключ :identifier',
        ],
        'ssh-key' => [
            'create' => 'Добавлен SSH-ключ :fingerprint в аккаунт',
            'delete' => 'Удалён SSH-ключ :fingerprint из аккаунта',
        ],
        'two-factor' => [
            'create' => 'Двухфакторная аутентификация включена',
            'delete' => 'Двухфакторная аутентификация отключена',
        ],
    ],
    'server' => [
        'reinstall' => 'Переустановлен сервер',
        'console' => [
            'command' => 'Выполнена команда ":command" на сервере',
        ],
        'power' => [
            'start' => 'Сервер запущен',
            'stop' => 'Сервер остановлен',
            'restart' => 'Сервер перезапущен',
            'kill' => 'Процесс сервера завершён принудительно',
        ],
        'backup' => [
            'download' => 'Скачана резервная копия :name',
            'delete' => 'Удалена резервная копия :name',
            'restore' => 'Восстановлена резервная копия :name (удалено файлов: :truncate)',
            'restore-complete' => 'Завершено восстановление резервной копии :name',
            'restore-failed' => 'Не удалось восстановить резервную копию :name',
            'start' => 'Запущено создание новой резервной копии :name',
            'complete' => 'Резервная копия :name помечена как завершённая',
            'fail' => 'Резервная копия :name помечена как неудачная',
            'lock' => 'Резервная копия :name заблокирована',
            'unlock' => 'Резервная копия :name разблокирована',
        ],
        'database' => [
            'create' => 'Создана новая база данных :name',
            'rotate-password' => 'Пароль для базы данных :name обновлён',
            'delete' => 'Удалена база данных :name',
        ],
        'file' => [
            'compress_one' => 'Сжат файл :directory:file',
            'compress_other' => 'Сжато файлов: :count в :directory',
            'read' => 'Просмотрено содержимое :file',
            'copy' => 'Создана копия файла :file',
            'create-directory' => 'Создана директория :directory:name',
            'decompress' => 'Распаковано :files в :directory',
            'delete_one' => 'Удалён файл :directory:files.0',
            'delete_other' => 'Удалено файлов: :count в :directory',
            'download' => 'Скачан файл :file',
            'pull' => 'Скачан удалённый файл с :url в :directory',
            'rename_one' => 'Переименован :directory:files.0.from в :directory:files.0.to',
            'rename_other' => 'Переименовано файлов: :count в :directory',
            'write' => 'Записано новое содержимое в :file',
            'upload' => 'Начата загрузка файла',
            'uploaded' => 'Загружен файл :directory:file',
        ],
        'sftp' => [
            'denied' => 'Доступ по SFTP заблокирован из-за прав',
            'create_one' => 'Создан файл :files.0',
            'create_other' => 'Создано новых файлов: :count',
            'write_one' => 'Изменено содержимое файла :files.0',
            'write_other' => 'Изменено содержимое файлов: :count',
            'delete_one' => 'Удалён файл :files.0',
            'delete_other' => 'Удалено файлов: :count',
            'create-directory_one' => 'Создана директория :files.0',
            'create-directory_other' => 'Создано директорий: :count',
            'rename_one' => 'Переименован файл :files.0.from в :files.0.to',
            'rename_other' => 'Переименовано или перемещено файлов: :count',
        ],
        'allocation' => [
            'create' => 'Добавлено выделение :allocation на сервер',
            'notes' => 'Обновлены примечания для :allocation с ":old" на ":new"',
            'primary' => ':allocation установлено как основное выделение сервера',
            'delete' => 'Удалено выделение :allocation',
        ],
        'schedule' => [
            'create' => 'Создано расписание :name',
            'update' => 'Обновлено расписание :name',
            'execute' => 'Ручной запуск расписания :name',
            'delete' => 'Удалено расписание :name',
        ],
        'task' => [
            'create' => 'Создана новая задача ":action" для расписания :name',
            'update' => 'Обновлена задача ":action" для расписания :name',
            'delete' => 'Удалена задача из расписания :name',
        ],
        'settings' => [
            'rename' => 'Переименован сервер с :old на :new',
            'description' => 'Описание сервера изменено с :old на :new',
        ],
        'startup' => [
            'edit' => 'Изменена переменная :variable с ":old" на ":new"',
            'image' => 'Docker-образ сервера обновлён с :old на :new',
        ],
        'subuser' => [
            'create' => 'Добавлен дополнительный пользователь :email',
            'update' => 'Обновлены права дополнительного пользователя :email',
            'delete' => 'Удалён дополнительный пользователь :email',
        ],
    ],
];
