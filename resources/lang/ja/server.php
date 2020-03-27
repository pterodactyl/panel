<?php

return [
    'index' => [
        'title' => 'サーバー:nameを表示しています',
        'header' => 'サーバーコンソール',
        'header_sub' => 'あなたのサーバーをリアルタイムで操作します。',
    ],
    'schedule' => [
        'header' => 'スケジュール マネージャー',
        'header_sub' => 'このサーバーの全てのスケジュールを1か所で管理します。',
        'current' => '現在のスケジュール',
        'new' => [
            'header' => '新しいスケジュールを作成',
            'header_sub' => 'このサーバーにスケジュールされたタスクの新しいセットを作成します。',
            'submit' => 'スケジュールを作成',
        ],
        'manage' => [
            'header' => 'スケジュールを管理',
            'submit' => 'スケジュールを更新',
            'delete' => 'スケジュールを削除',
        ],
        'task' => [
            'time' => '遅延',
            'action' => '実行する動作',
            'payload' => 'ペイロード',
            'add_more' => '他のタスクを追加',
        ],
        'actions' => [
            'command' => 'コマンドを送信',
            'power' => '電源動作',
        ],
        'toggle' => '有効化/無効化',
        'run_now' => 'スケジュールを実行',
        'schedule_created' => 'このサーバーの新しいスケジュールが正常に作成されました。',
        'schedule_updated' => 'スケジュールは更新されました。',
        'unnamed' => '無名のスケジュール',
        'setup' => 'スケジュールのセットアップ',
        'day_of_week' => '曜日',
        'day_of_month' => '日',
        'hour' => '時',
        'minute' => '分',
        'time_help' => 'スケジュールシステムは、タスクの実行を開始するタイミングを定義するときに、CronJob構文を使うことができます。上記の入力欄を使用して、これらのタスクの実行を開始するタイミングを指定するか、メニューからオプションを選択します。',
        'task_help' => 'タスクの時間は、以前に定義されたタスクを基準にしています。各スケジュールに割り当てられているタスクは5つまでで、タスクの間隔は15分以下である必要があります。',
    ],
    'tasks' => [
        'task_created' => 'コントロールパネルに新しいタスクが正常に作成されました。',
        'task_updated' => 'タスクは正常に更新されました。現在キューに入れられているタスク動作はキャンセルされ、次の定義された時間に実行されます。',
        'header' => 'スケジュールされたタスク',
        'header_sub' => 'あなたのサーバーを自動化します。',
        'current' => '現在スケジュールされているタスク',
        'actions' => [
            'command' => 'コマンドを送信',
            'power' => '電源動作を送信',
        ],
        'new_task' => '新しいタスクを追加する',
        'toggle' => '有効化/無効化',
        'new' => [
            'header' => '新しいタスク',
            'header_sub' => 'このサーバーに新しいタスクを追加します',
            'task_name' => 'タスク名',
            'day_of_week' => '曜日',
            'custom' => 'カスタム値',
            'day_of_month' => '日',
            'hour' => '時',
            'minute' => '分',
            'sun' => '日',
            'mon' => '月',
            'tues' => '火',
            'wed' => '水',
            'thurs' => '木',
            'fri' => '金',
            'sat' => '土',
            'submit' => 'タスクを作成',
            'type' => 'タスクの種類',
            'chain_then' => '後に', //Although it is grammatically incorrect, it cannot be resolved due to the language file specification.
            'chain_do' => '行う', //Likewise
            'chain_arguments' => '引数と',
            'payload' => 'タスクのペイロード',
            'payload_help' => '例えば、<code>コマンドを送信</code>を選択した場合は、ここにコマンドを入力します。<code>電源動作を送信</code>を選択した場合は、ここに電源動作を入力します(例えば<code>restart</code>など)。',
        ],
        'edit' => [
            'header' => 'タスクを管理',
            'submit' => 'タスクを更新',
        ],
    ],
    'users' => [
        'header' => 'ユーザーを管理',
        'header_sub' => 'サーバーにアクセスできるユーザーを制御します。',
        'configure' => '権限を設定',
        'list' => 'アクセス可能なアカウント',
        'add' => 'サブユーザーを新規追加',
        'update' => 'サブユーザーを更新',
        'user_assigned' => 'このサーバーに新しいサブユーザーを割り当てました。',
        'user_updated' => '権限を更新しました。',
        'edit' => [
            'header' => 'サブユーザーを編集',
            'header_sub' => 'ユーザーのサーバーへのアクセスを変更します。',
        ],
        'new' => [
            'header' => 'ユーザーを新規追加',
            'header_sub' => 'このサーバーへのアクセス許可を持つ新しいユーザーを追加します。',
            'email' => 'メールアドレス',
            'email_help' => 'このサーバーの管理に招待するユーザーのメールアドレスを入力します。',
            'power_header' => '電源の管理',
            'file_header' => 'ファイルの管理',
            'subuser_header' => 'サブユーザーの管理',
            'server_header' => 'サーバーの管理',
            'task_header' => 'スケジュールの管理',
            'database_header' => 'データベースの管理',
            'power_start' => [
                'title' => 'サーバーの起動',
                'description' => 'ユーザーがサーバーを起動できるようにします。',
            ],
            'power_stop' => [
                'title' => 'サーバーの停止',
                'description' => 'ユーザーがサーバーを停止できるようにします。',
            ],
            'power_restart' => [
                'title' => 'サーバーの再起動',
                'description' => 'ユーザーがサーバーを再起動できるようにします。',
            ],
            'power_kill' => [
                'title' => 'サーバーの強制終了',
                'description' => 'ユーザーがサーバープロセスを強制終了できるようにします。',
            ],
            'send_command' => [
                'title' => 'コンソールコマンドの送信',
                'description' => 'コンソールからコマンドを送信できます。ユーザーに停止または再起動のアクセス許可が無い場合、アプリケーションの停止コマンドを送信できません。',
            ],
            'access_sftp' => [
                'title' => 'SFTPを許可',
                'description' => 'ユーザーがSFTPサーバーに接続することを許可します。',
            ],
            'list_files' => [
                'title' => 'ファイルの一覧表示',
                'description' => 'ユーザーはサーバー上のすべてのファイルを一覧表示できますが、ファイルの内容は表示できません。',
            ],
            'edit_files' => [
                'title' => 'ファイルの編集',
                'description' => 'ユーザーがファイルを開いて閲覧のみできるようにします。SFTPではこの権限の影響を受けません。',
            ],
            'save_files' => [
                'title' => 'ファイルの保存',
                'description' => 'ユーザーが変更されたファイルの内容を保存できるようにします。SFTPではこの権限の影響を受けません。',
            ],
            'move_files' => [
                'title' => 'ファイルの名前変更と移動',
                'description' => 'ユーザーがファイルシステム上のファイルとフォルダーを移動して名前を変更できるようにします。',
            ],
            'copy_files' => [
                'title' => 'ファイルの複製',
                'description' => 'ユーザーがファイルシステム上のファイルとフォルダーを複製できるようにします。',
            ],
            'compress_files' => [
                'title' => 'ファイルの圧縮',
                'description' => 'ユーザーがシステム上のファイルとフォルダーのアーカイブを作成できるようにします。',
            ],
            'decompress_files' => [
                //Traditionally in Japan, deployment is sometimes referred to as "解凍" However, it is commonly called "展開"
                'title' => 'ファイルの展開',
                'description' => 'ユーザーが.zipおよび.tar(.gz)アーカイブを展開できるようにします。',
            ],
            'create_files' => [
                'title' => 'ファイルの作成',
                'description' => 'ユーザーがコントロールパネル内で新しいファイルを作成できるようにします。',
            ],
            'upload_files' => [
                'title' => 'ファイルのアップロード',
                'description' => 'ユーザーがファイルマネージャーを介してファイルをアップロードできるようにします。',
            ],
            'delete_files' => [
                'title' => 'ファイルの削除',
                'description' => 'ユーザーがシステムからファイルを削除できるようにします。',
            ],
            'download_files' => [
                'title' => 'ファイルのダウンロード',
                'description' => 'ユーザーがファイルをダウンロードできるようにします。ユーザーにこの権限が付与されている場合、その権限がコントロールパネルに割り当てられていなくても、ファイルのコンテンツをダウンロードして表示できます。',
            ],
            'list_subusers' => [
                'title' => 'サブユーザーの一覧表示',
                'description' => 'ユーザーがサーバーに割り当てられたすべてのサブユーザーのリストを表示できるようにします。',
            ],
            'view_subuser' => [
                'title' => 'サブユーザーの閲覧',
                'description' => 'ユーザーがサブユーザーに割り当てられた権限を表示できるようにします。',
            ],
            'edit_subuser' => [
                'title' => 'サブユーザーの編集',
                'description' => 'ユーザーが他のサブユーザーに割り当てられた権限を編集できるようにします。',
            ],
            'create_subuser' => [
                'title' => 'サブユーザーの作成',
                'description' => 'ユーザーがサーバー上に追加のサブユーザーを作成できるようにします。',
            ],
            'delete_subuser' => [
                'title' => 'サブユーザーの削除',
                'description' => 'ユーザーがサーバー上の他のサブユーザーを削除できるようにします。',
            ],
            //"割り当て" can be used instead of "アロケーション". However, this can be misleading.
            //This is pronounced "アロケーション" when reading katakana in English.
            //When translated into Japanese, it becomes an "割り当て", which may be unnatural.
            'view_allocations' => [
                'title' => 'アロケーションの閲覧',
                'description' => 'ユーザーはサーバーに割り当てられたすべてのIPとポートを表示できます。',
            ],
            'edit_allocation' => [
                'title' => 'アロケーションの編集',
                'description' => 'ユーザーがサーバーに使用するデフォルトの接続割り当てを変更できるようにします。',
            ],
            //"起動" can be used instead of "スタートアップ". However, this can be misleading.
            //This is pronounced "スタートアップ" when reading katakana in English.
            //When translated into Japanese, it becomes an "起動", which may be unnatural.
            'view_startup' => [
                'title' => 'スタートアップコマンドの閲覧',
                'description' => 'ユーザーがサーバーのスタートアップコマンドと関連する変数を表示できるようにします。',
            ],
            'edit_startup' => [
                'title' => 'スタートアップコマンドの編集',
                'description' => 'ユーザーがサーバーのスタートアップ変数を編集できるようにします。',
            ],
            'list_schedules' => [
                'title' => 'スケジュールの一覧表示',
                'description' => 'ユーザーがこのサーバーのすべてのスケジュール(有効および無効)を一覧表示できるようにします。',
            ],
            'view_schedule' => [
                'title' => 'スケジュールの閲覧',
                'description' => 'ユーザーは、割り当てられたすべてのタスクを含む特定のスケジュールの詳細を表示できます。',
            ],
            'toggle_schedule' => [
                'title' => 'スケジュールを実行',
                'description' => 'ユーザーは、スケジュールを有効および無効に切り替えることができます。',
            ],
            'queue_schedule' => [
                'title' => 'スケジュールのキュー',
                'description' => 'ユーザーがスケジュールをキューに入れて、次のプロセスサイクルでタスクを実行できるようにします。',
            ],
            'edit_schedule' => [
                'title' => 'スケジュールを編集',
                'description' => 'ユーザーは、スケジュールのすべてのタスクを含むスケジュールを編集できます。これにより、ユーザーは個々のタスクを削除できますが、スケジュール自体を削除することはできません。',
            ],
            'create_schedule' => [
                'title' => 'スケジュールを作成',
                'description' => 'ユーザーが新しいスケジュールを作成できるようにします。',
            ],
            'delete_schedule' => [
                'title' => 'スケジュールを削除',
                'description' => 'ユーザーがサーバーからスケジュールを削除できるようにします。',
            ],
            'view_databases' => [
                'title' => 'データベースの詳細を閲覧',
                'description' => 'ユーザーは、データベースのユーザー名とパスワードを含め、このサーバーに関連付けら®ているすべてのデータベースを表示できます。',
            ],
            'reset_db_password' => [
                'title' => 'データベースのパスワードを初期化',
                'description' => 'ユーザーがデータベースのパスワードを初期化できるようにします。',
            ],
            'delete_database' => [
                'title' => 'データベースの削除',
                'description' => 'ユーザーがコントロールパネルからサーバーのデータベースを削除できるようにします。',
            ],
            'create_database' => [
                'title' => 'データベースの作成',
                'description' => 'ユーザーがこのサーバー用に追加のデータベースを作成できるようにします。',
            ],
        ],
    ],
    'allocations' => [
        'mass_actions' => '一括動作',
        'delete' => 'アロケーションを削除',
    ],
    'files' => [
        'exceptions' => [
            'invalid_mime' => 'この種類のファイルは、コントロールパネルの組み込みエディターでは編集できません。',
            'max_size' => 'このファイルは大きすぎて、コントロールパネルの組み込みエディターでは編集できません。',
        ],
        'header' => 'ファイルマネージャー',
        'header_sub' => 'すべてのファイルをウェブから直接管理します。',
        'loading' => '初期ファイル構造を読み込んでいます。これには数秒かかる場合があります。',
        'path' => 'サーバープラグインまたは設定でファイルパスを構成するときは、ベースパスとして:pathを使用する必要があります。このノードへのウェブベースのファイルアップロードの最大サイズは:sizeです。',
        'seconds_ago' => '秒前',
        'file_name' => 'ファイル名',
        'size' => 'サイズ',
        'last_modified' => '最終編集',
        'add_new' => '新しいファイルを作成',
        'add_folder' => '新しいフォルダを作成',
        'mass_actions' => '一括動作',
        'delete' => 'ファイルを削除',
        'edit' => [
            'header' => 'ファイルを編集',
            'header_sub' => 'ウェブからファイルに変更を加えます。',
            'save' => 'ファイルを保存',
            'return' => 'ファイルマネージャーに戻る',
        ],
        'add' => [
            'header' => 'ファイルを新規作成',
            'header_sub' => 'あなたのサーバーにファイルを新規作成します。',
            'name' => 'ファイル名',
            'create' => 'ファイルを作成',
        ],
    ],
    'config' => [
        'name' => [
            'header' => 'サーバー名',
            'header_sub' => 'サーバーの名前を変更します。',
            'details' => 'サーバー名はコントロールパネル上のこのサーバーへの参照にすぎず、ゲームでユーザーに表示される可能性のあるサーバー固有の構成には影響しません。',
        ],
        'startup' => [
            'header' => 'スタートアップの設定',
            'header_sub' => 'サーバーのスタートアップ設定を制御します。',
            'command' => 'スタートアップコマンド',
            'edit_params' => 'パラメーターを編集',
            'update' => 'スタートアップパラメーターを更新',
            'startup_regex' => '入力ルール',
            'edited' => 'スタートアップ変数が正常に編集されました。これらは、次回このサーバーが起動したときに有効になります。',
        ],
        'sftp' => [
            'header' => 'SFTPの設定',
            'header_sub' => 'SFTP接続のアカウント情報',
            'details' => 'SFTPの詳細',
            'conn_addr' => '接続アドレス',
            'warning' => 'SFTPパスワードは、アカウントのパスワードです。クライアントが接続にFTPまたはFTPSではなくSFTPを使用するように設定されていることを確認してください。プロトコルには違いがあります。',
        ],
        'database' => [
            'header' => 'データベース',
            'header_sub' => 'このサーバーで使用可能なすべてのデータベース。',
            'your_dbs' => 'データベースの情報',
            'host' => 'MySQLホスト',
            'reset_password' => 'パスワードを初期化',
            'no_dbs' => 'このサーバーに一覧表示されているデータベースはありません。',
            'add_db' => 'データベースを作成',
        ],
        'allocation' => [
            'header' => 'サーバーのアロケーション',
            'header_sub' => 'このサーバーで使用可能なIPとポートを制御します。',
            'available' => '使用可能なアロケーション',
            'help' => 'アロケーションのヘルプ',
            'help_text' => '左側のリストには、サーバーが着信接続に使用するために開いているすべての使用可能なIPとポートが含まれています。',
        ],
    ],
];
