<?php

/**
 * アクティビティログのさまざまなイベントの翻訳文字列をすべて含みます。これらは、イベント名のコロン（:）の前にある値でキー化する必要があります。
 * コロンが存在しない場合、それらはトップレベルに存在する必要があります。
 */
return [
    'auth' => [
        'fail' => 'ログインに失敗しました',
        'success' => 'ログインしました',
        'password-reset' => 'パスワードをリセットしました',
        'reset-password' => 'パスワードリセットを要求しました',
        'checkpoint' => '二要素認証が要求されました',
        'recovery-token' => '二要素回復トークンを使用しました',
        'token' => '二要素認証チャレンジを解決しました',
        'ip-blocked' => ':identifierの未リストIPアドレスからのリクエストをブロックしました',
        'sftp' => [
            'fail' => 'SFTPログインに失敗しました',
        ],
    ],
    'user' => [
        'account' => [
            'email-changed' => ':oldから:newへのメール変更',
            'password-changed' => 'パスワードを変更しました',
        ],
        'api-key' => [
            'create' => '新しいAPIキー:identifierを作成しました',
            'delete' => 'APIキー:identifierを削除しました',
        ],
        'ssh-key' => [
            'create' => 'SSHキー:fingerprintをアカウントに追加しました',
            'delete' => 'SSHキー:fingerprintをアカウントから削除しました',
        ],
        'two-factor' => [
            'create' => '二要素認証を有効にしました',
            'delete' => '二要素認証を無効にしました',
        ],
    ],
    'server' => [
        'reinstall' => 'サーバーを再インストールしました',
        'console' => [
            'command' => 'サーバーで":command"を実行しました',
        ],
        'power' => [
            'start' => 'サーバーを起動しました',
            'stop' => 'サーバーを停止しました',
            'restart' => 'サーバーを再起動しました',
            'kill' => 'サーバープロセスを強制終了しました',
        ],
        'backup' => [
            'download' => ':nameバックアップをダウンロードしました',
            'delete' => ':nameバックアップを削除しました',
            'restore' => ':nameバックアップを復元しました（削除されたファイル: :truncate）',
            'restore-complete' => ':nameバックアップの復元を完了しました',
            'restore-failed' => ':nameバックアップの復元に失敗しました',
            'start' => '新しいバックアップ:nameを開始しました',
            'complete' => ':nameバックアップを完了とマークしました',
            'fail' => ':nameバックアップを失敗とマークしました',
            'lock' => ':nameバックアップをロックしました',
            'unlock' => ':nameバックアップのロックを解除しました',
        ],
        'database' => [
            'create' => '新しいデータベース:nameを作成しました',
            'rotate-password' => 'データベース:nameのパスワードをローテーションしました',
            'delete' => 'データベース:nameを削除しました',
        ],
        'file' => [
            'compress_one' => ':directory:fileを圧縮しました',
            'compress_other' => ':directoryで:countファイルを圧縮しました',
            'read' => ':fileの内容を表示しました',
            'copy' => ':fileのコピーを作成しました',
            'create-directory' => 'ディレクトリ:directory:nameを作成しました',
            'decompress' => ':directoryで:filesを解凍しました',
            'delete_one' => ':directory:files.0を削除しました',
            'delete_other' => ':directoryで:countファイルを削除しました',
            'download' => ':fileをダウンロードしました',
            'pull' => ':urlからリモートファイルを:directoryにダウンロードしました',
            'rename_one' => ':directory:files.0.fromを:directory:files.0.toに名前を変更しました',
            'rename_other' => ':directoryで:countファイルの名前を変更しました',
            'write' => ':fileに新しい内容を書き込みました',
            'upload' => 'ファイルのアップロードを開始しました',
            'uploaded' => ':directory:fileをアップロードしました',
        ],
        'sftp' => [
            'denied' => '権限によりSFTPアクセスがブロックされました',
            'create_one' => ':files.0を作成しました',
            'create_other' => ':count新しいファイルを作成しました',
            'write_one' => ':files.0の内容を変更しました',
            'write_other' => ':countファイルの内容を変更しました',
            'delete_one' => ':files.0を削除しました',
            'delete_other' => ':countファイルを削除しました',
            'create-directory_one' => ':files.0ディレクトリを作成しました',
            'create-directory_other' => ':countディレクトリを作成しました',
            'rename_one' => ':files.0.fromを:files.0.toに名前を変更しました',
            'rename_other' => ':countファイルの名前を変更または移動しました',
        ],
        'allocation' => [
            'create' => 'サーバーに:allocationを追加しました',
            'notes' => ':allocationのメモを":old"から":new"に更新しました',
            'primary' => ':allocationをサーバーの主要な割り当てとして設定しました',
            'delete' => ':allocation割り当てを削除しました',
        ],
        'schedule' => [
            'create' => ':nameスケジュールを作成しました',
            'update' => ':nameスケジュールを更新しました',
            'execute' => ':nameスケジュールを手動で実行しました',
            'delete' => ':nameスケジュールを削除しました',
        ],
        'task' => [
            'create' => ':nameスケジュールの新しい":action"タスクを作成しました',
            'update' => ':nameスケジュールの":action"タスクを更新しました',
            'delete' => ':nameスケジュールのタスクを削除しました',
        ],
        'settings' => [
            'rename' => 'サーバーの名前を:oldから:newに変更しました',
            'description' => 'サーバーの説明を:oldから:newに変更しました',
        ],
        'startup' => [
            'edit' => ':variable変数を":old"から":new"に変更しました',
            'image' => 'サーバーのDocker Imageを:oldから:newに更新しました',
        ],
        'subuser' => [
            'create' => ':emailをサブユーザーとして追加しました',
            'update' => ':emailのサブユーザー権限を更新しました',
            'delete' => ':emailをサブユーザーから削除しました',
        ],
    ],
];

