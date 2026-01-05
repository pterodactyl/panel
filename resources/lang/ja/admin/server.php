<?php

return [
    'exceptions' => [
        'no_new_default_allocation' => 'このサーバーのデフォルト割当を削除しようとしていますが、代替の割当がありません。',
        'marked_as_failed' => 'このサーバーは以前のインストールで失敗としてマークされています。現在の状態では切り替えできません。',
        'bad_variable' => ':name 変数に検証エラーがあります。',
        'daemon_exception' => 'デーモンと通信中に例外が発生し、HTTP/:code レスポンスが返されました。この例外はログに記録されています（リクエストID: :request_id）。',
        'default_allocation_not_found' => '要求されたデフォルト割当がこのサーバーの割当に見つかりません。',
    ],
    'alerts' => [
        'startup_changed' => 'このサーバーの起動設定が更新されました。nest または egg が変更された場合は再インストールが行われます。',
        'server_deleted' => 'サーバーはシステムから正常に削除されました。',
        'server_created' => 'サーバーがパネル上で正常に作成されました。デーモンがサーバーを完全にインストールするまで数分かかる場合があります。',
        'build_updated' => 'このサーバーのビルド情報が更新されました。一部の変更は再起動が必要です。',
        'suspension_toggled' => 'サーバーのサスペンド状態が :status に変更されました。',
        'rebuild_on_boot' => 'このサーバーは Docker コンテナの再構築が必要とマークされました。次回サーバー起動時に実行されます。',
        'install_toggled' => 'このサーバーのインストール状態が切り替えられました。',
        'server_reinstalled' => 'このサーバーは再インストールのキューに追加されました。',
        'details_updated' => 'サーバーの詳細が正常に更新されました。',
        'docker_image_updated' => 'このサーバーで使用するデフォルトの Docker イメージを変更しました。適用するには再起動が必要です。',
        'node_required' => 'サーバーを追加するには少なくとも1つのノードが必要です。',
        'transfer_nodes_required' => 'サーバーを転送するには少なくとも2つのノードが必要です。',
        'transfer_started' => 'サーバー転送が開始されました。',
        'transfer_not_viable' => '選択したノードはこのサーバーを受け入れるのに十分なディスク容量またはメモリを持っていません。',
    ],
];
