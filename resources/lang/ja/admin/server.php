<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'exceptions' => [
        'no_new_default_allocation' => 'このサーバーのデフォルトの割り当てを削除しようとしていますが、使用するフォールバック割り当てがありません。',
        'marked_as_failed' => 'このサーバーは、以前のインストールに失敗したとマークされました。この状態では、現在のステータスを切り替えることはできません。',
        'bad_variable' => ':name変数で検証エラーが発生しました。',
        'daemon_exception' => 'デーモンとの通信を試みているときに、HTTP/:codeが返されました。この例外はログに記録されています。',
        'default_allocation_not_found' => '要求されたデフォルトの割り当てがこのサーバーの割り当てに見つかりませんでした。',
    ],
    'alerts' => [
        'startup_changed' => 'このサーバーのスタートアップ設定が更新されました。このサーバーのパックまたはエッグが変更された場合、再インストールが行われます。',
        'server_deleted' => 'サーバーはシステムから正常に削除されました。',
        'server_created' => 'コントロールパネルにサーバーが正常に作成されました。デーモンがこのサーバーを完全にインストールするまで数分お待ちください。',
        'build_updated' => 'このサーバーのビルド設定が更新されました。一部の変更を有効にするには、再起動が必要な場合があります。',
        'suspension_toggled' => 'サーバーの一時停止状態が:statusに変更されました。',
        'rebuild_on_boot' => 'このサーバーは、Dockerコンテナの再構築が必要とマークされていて、次回サーバーが起動したときに再構築されます。',
        'install_toggled' => 'このサーバーのインストール状態が切り替えられました。',
        'server_reinstalled' => 'このサーバーは、現在再インストールのキューに入っています。',
        'details_updated' => 'サーバーの設定が正常に更新されました。',
        'docker_image_updated' => 'このサーバーで使用するデフォルトのDockerイメージを正常に変更しました。この変更を適用するには再起動が必要です。',
        'node_required' => 'このコントロールパネルにサーバーを追加する前に、少なくとも1つのノードを作成しておく必要があります。',
    ],
];
