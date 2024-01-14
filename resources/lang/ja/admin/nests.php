<?php

return [
    'notices' => [
        'created' => '新しいネスト、:nameが正常に作成されました',
        'deleted' => 'パネルから要求されたネストを正常に削除しました',
        'updated' => 'ネストの設定オプションを正常に更新しました',
    ],
    'eggs' => [
        'notices' => [
            'imported' => 'このEggとその関連変数を正常にインポートしました',
            'updated_via_import' => 'このEggは提供されたファイルを使用して更新されました',
            'deleted' => 'パネルから要求されたEggを正常に削除しました',
            'updated' => 'Eggの設定が正常に更新されました',
            'script_updated' => 'Eggのインストールスクリプトが更新され、サーバーがインストールされるたびに実行されます',
            'egg_created' => '新しいEggが正常に作成されました。この新しいEggを適用するには、実行中のデーモンを再起動する必要があります',
        ],
    ],
    'variables' => [
        'notices' => [
            'variable_deleted' => '変数":variable"が削除され、再構築後にサーバーで利用できなくなりました',
            'variable_updated' => '変数":variable"が更新されました。この変数を使用しているサーバーを再構築する必要があります',
            'variable_created' => '新しい変数が正常に作成され、このEggに割り当てられました',
        ],
    ],
];
