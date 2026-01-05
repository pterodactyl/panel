<?php

return [
    'auth' => [
        'fail' => 'ログインに失敗しました',
        'success' => 'ログインしました',
        'password-reset' => 'パスワードをリセットしました',
        'reset-password' => 'パスワードリセットを要求しました',
        'checkpoint' => '二段階認証が要求されました',
        'recovery-token' => '二段階認証のリカバリートークンを使用しました',
        'token' => '二段階認証を通過しました',
        'ip-blocked' => ':identifier からの未登録IPアドレスのリクエストをブロックしました',
        'sftp' => [
            'fail' => 'SFTP ログインに失敗しました',
        ],
    ],
    'user' => [
        'account' => [
            'email-changed' => 'メールを :old から :new に変更しました',
            'password-changed' => 'パスワードを変更しました',
        ],
        'api-key' => [
            'create' => '新しいAPIキー :identifier を作成しました',
            'delete' => 'APIキー :identifier を削除しました',
        ],
        'ssh-key' => [
            'create' => 'SSHキー :fingerprint をアカウントに追加しました',
            'delete' => 'SSHキー :fingerprint をアカウントから削除しました',
        ],
        'two-factor' => [
            'create' => '二段階認証を有効にしました',
            'delete' => '二段階認証を無効にしました',
        ],
    ],
    'server' => [
        'reinstall' => 'サーバーを再インストールしました',
        'console' => [
            'command' => 'サーバー上で「:command」を実行しました',
        ],
        'power' => [
            'start' => 'サーバーを起動しました',
            'stop' => 'サーバーを停止しました',
            'restart' => 'サーバーを再起動しました',
            'kill' => 'サーバープロセスを強制終了しました',
        ],
        'backup' => [
            'download' => ':name バックアップをダウンロードしました',
            'delete' => ':name バックアップを削除しました',
            'restore' => ':name バックアップを復元しました（削除されたファイル: :truncate）',
            'restore-complete' => ':name バックアップの復元が完了しました',
            'restore-failed' => ':name バックアップの復元に失敗しました',
            'start' => '新しいバックアップ :name を開始しました',
            'complete' => ':name バックアップを完了としてマークしました',
            'fail' => ':name バックアップを失敗としてマークしました',
            'lock' => ':name バックアップをロックしました',
            'unlock' => ':name バックアップのロックを解除しました',
        ],
        'database' => [
            'create' => '新しいデータベース :name を作成しました',
            'rotate-password' => 'データベース :name のパスワードを更新しました',
            'delete' => 'データベース :name を削除しました',
        ],
        'file' => [
            'compress_one' => ':directory:file を圧縮しました',
            'compress_other' => ':directory 内の :count ファイルを圧縮しました',
            'read' => ':file の内容を表示しました',
            'copy' => ':file のコピーを作成しました',
            'create-directory' => ':directory:name ディレクトリを作成しました',
            'decompress' => ':directory の :files を展開しました',
            'delete_one' => ':directory:files.0 を削除しました',
            'delete_other' => ':directory 内の :count ファイルを削除しました',
            'download' => ':file をダウンロードしました',
            'pull' => ':url から :directory にリモートファイルをダウンロードしました',
            'rename_one' => ':directory:files.0.from を :directory:files.0.to に名前変更しました',
            'rename_other' => ':directory 内の :count ファイルの名前を変更しました',
            'write' => ':file に新しい内容を書き込みました',
            'upload' => 'ファイルのアップロードを開始しました',
            'uploaded' => ':directory:file をアップロードしました',
        ],
        'sftp' => [
            'denied' => '権限により SFTP アクセスをブロックしました',
            'create_one' => ':files.0 を作成しました',
            'create_other' => ':count 個の新しいファイルを作成しました',
            'write_one' => ':files.0 の内容を変更しました',
            'write_other' => ':count ファイルの内容を変更しました',
            'delete_one' => ':files.0 を削除しました',
            'delete_other' => ':count ファイルを削除しました',
            'create-directory_one' => ':files.0 ディレクトリを作成しました',
            'create-directory_other' => ':count 個のディレクトリを作成しました',
            'rename_one' => ':files.0.from を :files.0.to に名前変更しました',
            'rename_other' => ':count 個のファイルを名前変更または移動しました',
        ],
        'allocation' => [
            'create' => ':allocation をサーバーに追加しました',
            'notes' => ':allocation のメモを「:old」から「:new」に更新しました',
            'primary' => ':allocation をプライマリ割当として設定しました',
            'delete' => ':allocation 割当を削除しました',
        ],
        'schedule' => [
            'create' => ':name スケジュールを作成しました',
            'update' => ':name スケジュールを更新しました',
            'execute' => ':name スケジュールを手動実行しました',
            'delete' => ':name スケジュールを削除しました',
        ],
        'task' => [
            'create' => ':name スケジュールの新しい「:action」タスクを作成しました',
            'update' => ':name スケジュールの「:action」タスクを更新しました',
            'delete' => ':name スケジュールのタスクを削除しました',
        ],
        'settings' => [
            'rename' => 'サーバー名を :old から :new に変更しました',
            'description' => 'サーバーの説明を :old から :new に変更しました',
        ],
        'startup' => [
            'edit' => ':variable 変数を「:old」から「:new」に変更しました',
            'image' => 'サーバーの Docker イメージを :old から :new に更新しました',
        ],
        'subuser' => [
            'create' => ':email をサブユーザーとして追加しました',
            'update' => ':email のサブユーザー権限を更新しました',
            'delete' => ':email をサブユーザーから削除しました',
        ],
    ],
];
