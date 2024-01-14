<?php

return [
    'daemon_connection_failed' => 'デーモンとの通信中に例外が発生し、HTTP/:codeの応答コードが返されました。この例外はログに記録されました',
    'node' => [
        'servers_attached' => 'ノードを削除するためには、それにリンクされたサーバーがない状態でなければなりません',
        'daemon_off_config_updated' => 'デーモンの設定は<strong>更新されました</strong>が、デーモンの設定ファイルを自動的に更新しようとしたときにエラーが発生しました。これらの変更を適用するためには、デーモンの設定ファイル（config.yml）を手動で更新する必要があります',
    ],
    'allocations' => [
        'server_using' => '現在この割り当てにサーバーが割り当てられています。サーバーが現在割り当てられていない場合にのみ、割り当てを削除できます',
        'too_many_ports' => '一度に1000以上のポートを範囲に追加することはサポートされていません',
        'invalid_mapping' => ':portのために提供されたマッピングは無効で、処理できませんでした',
        'cidr_out_of_range' => 'CIDR表記法では、マスクは/25から/32までしか許可されていません',
        'port_out_of_range' => '割り当てのポートは1024以上65535以下でなければなりません',
    ],
    'nest' => [
        'delete_has_servers' => 'アクティブなサーバーが接続されているNestは、パネルから削除できません',
        'egg' => [
            'delete_has_servers' => 'アクティブなサーバーが接続されているEggは、パネルから削除できません',
            'invalid_copy_id' => 'スクリプトのコピー元として選択されたEggは存在しないか、自身がスクリプトをコピーしています',
            'must_be_child' => 'このEggの"Copy Settings From"指示は、選択されたNestの子オプションでなければなりません',
            'has_children' => 'このEggは一つ以上の他のEggの親です。このEggを削除する前に、それらのEggを削除してください',
        ],
        'variables' => [
            'env_not_unique' => '環境変数:nameは、このEggに固有でなければなりません',
            'reserved_name' => '環境変数:nameは保護されており、変数に割り当てることはできません',
            'bad_validation_rule' => '検証ルール":rule"は、このアプリケーションの有効なルールではありません',
        ],
        'importer' => [
            'json_error' => 'JSONファイルの解析中にエラーが発生しました: :error',
            'file_error' => '提供されたJSONファイルは有効ではありません',
            'invalid_json_provided' => '提供されたJSONファイルは認識できる形式ではありません',
        ],
    ],
    'subusers' => [
        'editing_self' => '自分自身のサブユーザーアカウントを編集することは許可されていません',
        'user_is_owner' => 'サーバーの所有者をこのサーバーのサブユーザーとして追加することはできません',
        'subuser_exists' => 'そのメールアドレスのユーザーは既にこのサーバーのサブユーザーとして割り当てられています',
    ],
    'databases' => [
        'delete_has_databases' => 'アクティブなデータベースがリンクされているデータベースホストサーバーは削除できません',
    ],
    'tasks' => [
        'chain_interval_too_long' => '連鎖タスクの最大間隔時間は15分です',
    ],
    'locations' => [
        'has_nodes' => 'アクティブなノードが接続されている場所は削除できません',
    ],
    'users' => [
        'node_revocation_failed' => '<a href=":link">Node #:node</a>のキーの取り消しに失敗しました。:error',
    ],
    'deployment' => [
        'no_viable_nodes' => '自動デプロイの要件を満たすノードが見つかりませんでした',
        'no_viable_allocations' => '自動デプロイの要件を満たす割り当てが見つかりませんでした',
    ],
    'api' => [
        'resource_not_found' => '要求されたリソースはこのサーバー上に存在しません',
    ],
];
