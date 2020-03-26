<?php

return [
    'daemon_connection_failed' => 'デーモンとの通信を試みているときに、HTTP/:codeが返されました。この例外はログに記録されています。',
    'node' => [
        'servers_attached' => 'ノードに設定されているサーバーがある場合は、ノードを削除できません。',
        //Using <strong> tags can be grammatically unnatural.
        //<strong>If you must use <strong> tags, you can use "デーモンの設定は更新されました</strong>".
        'daemon_off_config_updated' => 'デーモンの設定は更新されましたが、デーモンの設定ファイルを自動的に更新しようとしたときにエラーが発生しました。行われた変更を適用するには、デーモンの設定ファイル(core.json)を手動で更新する必要があります。',
    ],
    //"割り当て" can be used instead of "アロケーション". However, this can be misleading.
    //This is pronounced "アロケーション" when reading katakana in English.
    //When translated into Japanese, it becomes an "割り当て", which may be unnatural.
    'allocations' => [
        'server_using' => '現在、このアロケーションはサーバーに使用されています。アロケーションは、使用されていない場合のみ削除できます。',
        'too_many_ports' => '1度で1つの範囲に1000を超えるポートを追加することはできません。',
        'invalid_mapping' => ':portに設定されたマッピングは無効であり、処理はできませんでした。',
        'cidr_out_of_range' => 'CIDRでは、/25から/32のマスクのみが使用できます。',
        'port_out_of_range' => 'アロケーションのポートは、1024以上65535以下である必要があります。',
    ],
    'nest' => [
        'delete_has_servers' => '有効なサーバーに使用されているネストはコントロールパネルから削除できません。',
        //"卵" can be used instead of "エッグ". However, this can be misleading.
        //This is pronounced "エッグ" when reading katakana in English.
        //When translated into Japanese, it becomes an "卵", which may be unnatural.
        'egg' => [
            'delete_has_servers' => '有効なサーバーに使用されているエッグはコントロールパネルから削除できません。',
            'invalid_copy_id' => 'スクリプトをコピーするために指定されたエッグが存在しないか、自身をコピーしています。',
            'must_be_child' => 'このエッグの「設定のコピー元」項目は、指定したネストの子オプションである必要があります。',
            'has_children' => 'このエッグは、1つ以上の他のエッグの親です。このエッグを削除する前に、それらのエッグを削除してください。',
        ],
        'variables' => [
            'env_not_unique' => '環境変数:nameは、このエッグに固有である必要があります。',
            'reserved_name' => '環境変数:nameは保護されているため、変数にアロケーションることはできません。',
            'bad_validation_rule' => ':ruleはこのアプリケーションで有効なルールではありません。',
        ],
        'importer' => [
            'json_error' => 'JSONファイルの解析中に:errorが発生しました。',
            'file_error' => '指定されたJSONファイルは無効です。',
            'invalid_json_provided' => '指定されたJSONファイルは、認識できる形式ではありません。',
        ],
    ],
    'packs' => [
        'delete_has_servers' => '有効なサーバーに使用されているパックは削除できません。',
        'update_has_servers' => 'パックがサーバーに使用されている場合、関連するオプションIDを変更できません。',
        'invalid_upload' => '指定されたファイルは無効です。',
        'invalid_mime' => '指定されたファイルは、:typeではありません。',
        'unreadable' => '指定されたアーカイブをサーバーで開けませんでした。',
        //Traditionally in Japan, deployment is sometimes referred to as "解凍" However, it is commonly called "展開"
        'zip_extraction' => 'サーバーに指定されたアーカイブを展開しようとしたときにエラーが発生しました。',
        'invalid_archive_exception' => '指定されたパックのアーカイブには、ベースディレクトリに必要なarchive.tar.gzまたはimport.jsonファイルが存在しません。',
    ],
    'subusers' => [
        'editing_self' => '自分自身のサブユーザーアカウントを編集することはできません。',
        'user_is_owner' => 'サーバーの所有者をサブユーザーとして追加することはできません。',
        'subuser_exists' => 'そのメールアドレスを持つユーザーは、すでにこのサーバーのサブユーザーとして設定されています。',
    ],
    'databases' => [
        'delete_has_databases' => '有効なサーバーに使用されているデータベースサーバーは削除できません。',
    ],
    'tasks' => [
        'chain_interval_too_long' => '連鎖タスクの最大間隔は15分です。',
    ],
    'locations' => [
        //"場所" can be used instead of "ロケーション". However, this can be misleading.
        //This is pronounced "ロケーション" when reading katakana in English.
        //When translated into Japanese, it becomes an "場所", which may be unnatural.
        'has_nodes' => '有効なノードに使用されているロケーションは削除できません。',
    ],
    'users' => [
        'node_revocation_failed' => 'エラーにより、<a href=":link">ノード #:node</a>のキーを取り消すことに失敗しました。エラー内容：:error',
    ],
    'deployment' => [
        'no_viable_nodes' => '自動デプロイに指定された要件を満たすノードが見つかりませんでした。',
        'no_viable_allocations' => '自動デプロイの要件を満たすアロケーションが見つかりませんでした。',
    ],
    'api' => [
        'resource_not_found' => '要求されたリソースは、このサーバーに存在しません。',
    ],
];
