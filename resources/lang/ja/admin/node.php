<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'validation' => [
        'fqdn_not_resolvable' => '指定されたFQDNまたはIPアドレスは、有効なIPアドレスに解決できません。',
        'fqdn_required_for_ssl' => 'このノードでSSLを使用するには、パブリックIPアドレスに解決される完全装飾ドメイン名が必要です。',
    ],
    'notices' => [
        'allocations_added' => 'このノードにアロケーションが正常に追加されました。',
        'node_deleted' => 'ノートがコントロールパネルから正常に削除されました。',
        'location_required' => 'このコントロールパネルにノードを追加する前に、少なくとも1つのロケーションを構成しておく必要があります。',
        'node_created' => '新しいノードが正常に作成されました。このマシンでデーモンを自動的に構成するには、「構成」タブにアクセスします。<strong>サーバーを追加する前に、まず少なくとも1つのIPアドレスとポートを割り当てる必要があります。</strong>',
        'node_updated' => 'ノード情報を更新しました。デーモン設定が変更された場合、それらの変更を有効にするために再起動する必要があります。',
        'unallocated_deleted' => '<code>:ip</code>の未割り当てポートをすべて削除しました。',
    ],
];
