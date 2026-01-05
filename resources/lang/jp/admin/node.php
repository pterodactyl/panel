<?php

return [
    'validation' => [
        'fqdn_not_resolvable' => '指定された FQDN または IP アドレスが有効な IP アドレスに解決されません。',
        'fqdn_required_for_ssl' => 'このノードで SSL を使用するには、パブリック IP に解決される完全修飾ドメイン名が必要です。',
    ],
    'notices' => [
        'allocations_added' => '割当がこのノードに正常に追加されました。',
        'node_deleted' => 'ノードがパネルから正常に削除されました。',
        'location_required' => 'ノードを追加する前に少なくとも1つのロケーションを設定する必要があります。',
        'node_created' => '新しいノードを作成しました。デーモンの構成は「構成」タブから自動的に行うことができます。<strong>サーバーを追加する前に少なくとも1つのIPアドレスとポートを割り当てる必要があります。</strong>',
        'node_updated' => 'ノード情報が更新されました。デーモンの設定が変更された場合は再起動が必要です。',
        'unallocated_deleted' => '<code>:ip</code> の未割当ポートをすべて削除しました。',
    ],
];
