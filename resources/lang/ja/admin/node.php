<?php

return [
    'validation' => [
        'fqdn_not_resolvable' => '提供されたFQDNまたはIPアドレスが有効なIPアドレスに解決されません',
        'fqdn_required_for_ssl' => 'このノードでSSLを使用するためには、公開IPアドレスに解決する完全修飾ドメイン名が必要です',
    ],
    'notices' => [
        'allocations_added' => 'このノードに割り当てが正常に追加されました',
        'node_deleted' => 'ノードがパネルから正常に削除されました',
        'location_required' => 'パネルにノードを追加する前に、少なくとも1つのロケーションを設定する必要があります',
        'node_created' => '新しいノードを正常に作成しました。\'設定\'タブを訪れることで、このマシン上のデーモンを自動的に設定できます。<strong>サーバーを追加する前に、少なくとも1つのIPアドレスとポートを割り当てる必要があります。</strong>',
        'node_updated' => 'ノード情報が更新されました。デーモンの設定が変更された場合、それらの変更を有効にするためには再起動が必要です',
        'unallocated_deleted' => '<code>:ip</code>の未割り当てポートをすべて削除しました',
    ],
];
