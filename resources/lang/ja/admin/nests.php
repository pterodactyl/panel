<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'notices' => [
        'created' => '新しいネスト:nameが作成されました。',
        'deleted' => '要求されたネストをコントロールパネルから正常に削除しました。',
        'updated' => '要求されたネストの設定を正常に更新しました。',
    ],
    'eggs' => [
        'notices' => [
            'imported' => 'このエッグとそれに関連する変数を正常にインポートしました。',
            'updated_via_import' => 'このエッグは、提供されたファイルを使用して更新されています。',
            'deleted' => '要求されたエッグをコントロールパネルから正常に削除しました。',
            'updated' => '要求されたエッグの設定を正常に更新しました。',
            'script_updated' => 'エッグのインストールスクリプトが更新され、サーバーがインストールされるたびに実行されます。',
            'egg_created' => '新しいエッグが無事に産まれました。この新しいエッグを適用するには、実行中のデーモンを再起動する必要があります。',
        ],
    ],
    'variables' => [
        'notices' => [
            'variable_deleted' => '変数":variable"は削除されており、再構築するとサーバーで使用できなくなります。',
            'variable_updated' => '変数":variable"が更新されました。変更を適用するには、この変数を使用してサーバーを再構築する必要があります。',
            'variable_created' => '新しい変数が正常に作成され、このエッグに割り当てられました。',
        ],
    ],
];
