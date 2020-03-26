<?php

return [
    'validation_error' => 'リクエストの検証に失敗しました。',
    'errors' => [
        'return' => '前のページに戻る',
        'home' => 'ホームに戻る',
        '403' => [
            //There is a more correct expression "禁じられた", but this can be misleading.
            'header' => 'Forbidden',
            'desc' => 'リソースにアクセスする権限がありません。',
        ],
        '404' => [
            'header' => 'ファイルが存在しません',
            'desc' => '要求されたリソースを見つけることができませんでした。',
        ],
        'installing' => [
            'header' => 'サーバーをインストールしています',
            'desc' => 'このサーバーはまだインストール中です。数分後にもう一度確認してください。この処理が完了するとすぐにメールが届きます。',
        ],
        'suspended' => [
            'header' => 'サーバーは一時停止されています',
            'desc' => 'このサーバーは一時停止されているため、アクセスすることができません。',
        ],
        'maintenance' => [
            'header' => 'ノードはメンテナンス中です。',
            'title' => '一時的に利用できません',
            'desc' => 'このノードはメンテナンス中のため、一時的にサーバーにアクセスできません。',
        ],
    ],
    'index' => [
        'header' => 'あなたのサーバー',
        'header_sub' => 'あなたがアクセスできるサーバー',
        'list' => 'サーバーの一覧',
    ],
    'api' => [
        'index' => [
            'list' => 'あなたのAPIキー',
            'header' => 'アカウントAPI',
            'header_sub' => 'コントロールパネルに対して操作できるAPIキーを管理します。',
            'create_new' => 'APIキーを新しく生成する',
            'keypair_created' => 'APIキーが正常に生成され、以下に表示されています。',
        ],
        'new' => [
            'header' => '新しいAPIキー',
            'header_sub' => '新しくアカウントAPIキーを作成します。',
            'form_title' => "詳細",
            'descriptive_memo' => [
                'title' => '説明',
                'description' => 'このAPIキーの簡単な説明を入力します。',
            ],
            'allowed_ips' => [
                'title' => '許可IP',
                'description' => 'このAPIキーを使用してAPIへのアクセスを許可するIPの行区切りリストを入力します。CIDRに準拠したIPを使用できます。全てのIPを許可するには、空白のままにします。',
            ],
        ],
    ],
    'account' => [
        'details_updated' => 'アカウントの情報が更新されました。',
        'invalid_password' => '指定されたパスワードが無効です。',
        'header' => 'あなたのアカウント',
        'header_sub' => 'アカウントの情報を管理します。',
        'update_pass' => 'パスワードを更新',
        'update_email' => 'メールアドレスを更新',
        'current_password' => '現在のパスワード',
        'new_password' => '新しいパスワード',
        'new_password_again' => '新しいパスワード(確認)',
        'new_email' => '新しいメールアドレス',
        'first_name' => '姓',
        'last_name' => '名',
        'update_identity' => '情報を更新',
        'username_help' => 'ユーザー名は重複しない必要があり、:requirementsのみを含めることができます。',
        'language' => '言語',
    ],
    'security' => [
        'session_mgmt_disabled' => '管理者は、これを介してアカウントのセッションを管理する機能を有効にしていません。',
        'header' => 'アカウントのセキュリティ',
        'header_sub' => '有効なセッションと2FAを制御します。',
        'sessions' => '有効なセッション',
        '2fa_header' => '2FA認証',
        //Google Authenticator has a Japanese app, "Google Authenticator" on the App Store, but "Google 認証システム" on the Play Store.
        //https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=ja
        '2fa_token_help' => 'アプリ(Google Authenticator、Google 認証システム、Authyなど)によって生成された2FAトークンを入力します。',
        'disable_2fa' => '2FA認証を無効にする',
        '2fa_enabled' => 'このアカウントでは2FA認証が有効になっていて、コントロールパネルにログインするために必要になります。2FA認証を無効にする場合は、以下に有効なトークンを入力して送信してください。',
        '2fa_disabled' => 'アカウントで2FAに認証が無効になっています！アカウントのセキュリティを高めるには、2FA認証を有効にする必要があります。',
        'enable_2fa' => '2FA認証を有効にする',
        '2fa_qr' => 'デバイスで2FA認証を設定する',
        '2fa_checkpoint_help' => 'デバイスの2FAアプリケーションを使用して、左側のQRコードを撮影するか、下にコードを入力します。完了したら、2FAトークンを生成し以下に入力します。',
        '2fa_disable_error' => '指定された2FAトークンは無効です。2FAによる保護が無効になりました。',
    ],
];
