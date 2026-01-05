<?php

return [
    'notices' => [
        'created' => '新しい Nest「:name」が作成されました。',
        'deleted' => '指定された Nest をパネルから削除しました。',
        'updated' => 'Nest の設定が更新されました。',
    ],
    'eggs' => [
        'notices' => [
            'imported' => 'この Egg と関連変数をインポートしました。',
            'updated_via_import' => '提供されたファイルでこの Egg を更新しました。',
            'deleted' => '指定された Egg をパネルから削除しました。',
            'updated' => 'Egg の設定が正常に更新されました。',
            'script_updated' => 'Egg のインストールスクリプトが更新され、サーバーのインストール時に実行されます。',
            'egg_created' => '新しい Egg が作成されました。適用するにはデーモンの再起動が必要です。',
        ],
    ],
    'variables' => [
        'notices' => [
            'variable_deleted' => '変数「:variable」は削除され、サーバー再構築後に利用できなくなります。',
            'variable_updated' => '変数「:variable」は更新されました。変更を適用するにはこの変数を使用しているサーバーの再構築が必要です。',
            'variable_created' => '新しい変数が作成され、この Egg に割り当てられました。',
        ],
    ],
];
