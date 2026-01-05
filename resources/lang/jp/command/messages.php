<?php

return [
    'location' => [
        'no_location_found' => '指定されたショートコードに一致するレコードが見つかりませんでした。',
        'ask_short' => 'ロケーションのショートコード',
        'ask_long' => 'ロケーションの説明',
        'created' => '新しいロケーション「:name」が作成され、IDは :id です。',
        'deleted' => '指定されたロケーションを削除しました。',
    ],
    'user' => [
        'search_users' => 'ユーザー名、ユーザーID、またはメールアドレスを入力してください',
        'select_search_user' => '削除するユーザーのID（再検索する場合は「0」を入力）',
        'deleted' => 'ユーザーをパネルから削除しました。',
        'confirm_delete' => 'このユーザーをパネルから削除してもよいですか？',
        'no_users_found' => '検索語に一致するユーザーは見つかりませんでした。',
        'multiple_found' => '複数のアカウントが見つかったため、--no-interaction フラグにより削除できませんでした。',
        'ask_admin' => 'このユーザーは管理者ですか？',
        'ask_email' => 'メールアドレス',
        'ask_username' => 'ユーザー名',
        'ask_name_first' => '名',
        'ask_name_last' => '姓',
        'ask_password' => 'パスワード',
        'ask_password_tip' => 'ランダムパスワードを生成してユーザーにメールで送信する場合は、このコマンドを再実行し（CTRL+C）、`--no-password` フラグを指定してください。',
        'ask_password_help' => 'パスワードは最低8文字で、少なくとも1つの大文字と数字を含める必要があります。',
        '2fa_help_text' => [
            'このコマンドは、ユーザーがアカウントにアクセスできない場合のアカウント回復としてのみ使用してください。',
            '続行したくない場合は CTRL+C でこの操作を中止してください。',
        ],
        '2fa_disabled' => ':email の二段階認証を無効化しました。',
    ],
    'schedule' => [
        'output_line' => '`:schedule` (:hash) の最初のタスクのジョブをディスパッチします。',
    ],
    'maintenance' => [
        'deleting_service_backup' => 'サービスバックアップファイル :file を削除しています。',
    ],
    'server' => [
        'rebuild_failed' => 'ノード ":node" 上の ":name" (#:id) の再構築リクエストはエラーで失敗しました: :message',
        'reinstall' => [
            'failed' => 'ノード ":node" 上の ":name" (#:id) の再インストールリクエストはエラーで失敗しました: :message',
            'confirm' => '複数のサーバーに対して再インストールを行います。本当に続行しますか？',
        ],
        'power' => [
            'confirm' => ':count 台のサーバーに対して :action を実行します。本当に続行しますか？',
            'action_failed' => 'ノード ":node" 上の ":name" (#:id) に対する電源操作はエラーで失敗しました: :message',
        ],
    ],
    'environment' => [
        'mail' => [
            'ask_smtp_host' => 'SMTP ホスト (例: smtp.gmail.com)',
            'ask_smtp_port' => 'SMTP ポート',
            'ask_smtp_username' => 'SMTP ユーザー名',
            'ask_smtp_password' => 'SMTP パスワード',
            'ask_mailgun_domain' => 'Mailgun ドメイン',
            'ask_mailgun_endpoint' => 'Mailgun エンドポイント',
            'ask_mailgun_secret' => 'Mailgun シークレット',
            'ask_mandrill_secret' => 'Mandrill シークレット',
            'ask_postmark_username' => 'Postmark API キー',
            'ask_driver' => 'メール送信に使用するドライバはどれですか？',
            'ask_mail_from' => '送信元メールアドレス',
            'ask_mail_name' => '送信元名',
            'ask_encryption' => '使用する暗号化方式',
        ],
    ],
];
