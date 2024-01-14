<?php

return [
    'location' => [
        'no_location_found' => '提供されたショートコードに一致する記録を見つけることができませんでした',
        'ask_short' => 'ロケーションショートコード',
        'ask_long' => 'ロケーションの説明',
        'created' => '新しいロケーション（:name）を正常に作成しました。IDは:idです',
        'deleted' => '要求されたロケーションを正常に削除しました',
    ],
    'user' => [
        'search_users' => 'ユーザー名、ユーザーID、またはメールアドレスを入力してください',
        'select_search_user' => '削除するユーザーのID（\'0\'を入力して再検索）',
        'deleted' => 'ユーザーがパネルから正常に削除されました',
        'confirm_delete' => 'このユーザーをパネルから削除してもよろしいですか？',
        'no_users_found' => '提供された検索語に対してユーザーが見つかりませんでした',
        'multiple_found' => '提供されたユーザーに対して複数のアカウントが見つかりました。--no-interactionフラグのため、ユーザーを削除できません',
        'ask_admin' => 'このユーザーは管理者ですか？',
        'ask_email' => 'メールアドレス',
        'ask_username' => 'ユーザー名',
        'ask_name_first' => '名',
        'ask_name_last' => '姓',
        'ask_password' => 'パスワード',
        'ask_password_tip' => 'ユーザーにランダムなパスワードをメールで送信してアカウントを作成したい場合は、このコマンドを再実行（CTRL+C）し、`--no-password`フラグを渡します',
        'ask_password_help' => 'パスワードは最低8文字で、大文字と数字を少なくとも1つ含む必要があります',
        '2fa_help_text' => [
            'このコマンドは、有効になっている場合、ユーザーのアカウントの2要素認証を無効にします。これは、ユーザーがアカウントからロックアウトされた場合のアカウント回復コマンドとしてのみ使用する必要があります',
            'これがあなたがしたいことではない場合は、CTRL+Cを押してこのプロセスを終了します',
        ],
        '2fa_disabled' => ':emailの2要素認証が無効になりました',
    ],
    'schedule' => [
        'output_line' => '`:schedule`の最初のタスクのジョブをディスパッチします（:hash）',
    ],
    'maintenance' => [
        'deleting_service_backup' => 'サービスバックアップファイル:fileを削除しています',
    ],
    'server' => [
        'rebuild_failed' => '":name"（#:id）のノード":node"での再構築要求がエラーで失敗しました：:message',
        'reinstall' => [
            'failed' => '":name"（#:id）のノード":node"での再インストール要求がエラーで失敗しました：:message',
            'confirm' => 'あなたは一群のサーバーに対して再インストールしようとしています。続行しますか？',
        ],
        'power' => [
            'confirm' => 'あなたは:countサーバーに対して:actionを実行しようとしています。続行しますか？',
            'action_failed' => '":name"（#:id）のノード":node"での電源アクション要求がエラーで失敗しました：:message',
        ],
    ],
    'environment' => [
        'mail' => [
            'ask_smtp_host' => 'SMTPホスト（例 smtp.gmail.com）',
            'ask_smtp_port' => 'SMTPポート',
            'ask_smtp_username' => 'SMTPユーザー名',
            'ask_smtp_password' => 'SMTPパスワード',
            'ask_mailgun_domain' => 'Mailgunドメイン',
            'ask_mailgun_endpoint' => 'Mailgunエンドポイント',
            'ask_mailgun_secret' => 'Mailgunシークレット',
            'ask_mandrill_secret' => 'Mandrillシークレット',
            'ask_postmark_username' => 'Postmark APIキー',
            'ask_driver' => 'メール送信にどのドライバーを使用するべきですか？',
            'ask_mail_from' => 'メールが発信元となるメールアドレス',
            'ask_mail_name' => 'メールが表示される名前',
            'ask_encryption' => '使用する暗号化方法',
        ],
    ],
];

