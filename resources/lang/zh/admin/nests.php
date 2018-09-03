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
        'created' => '一个新的管理模块, :name, 已成功创建。',
        'deleted' => '成功从面板删除指定的管理模块。',
        'updated' => '成功更新管理模块的选项。',
    ],
    'eggs' => [
        'notices' => [
            'imported' => '成功导入一个管理模板。',
            'updated_via_import' => '该管理模板已按照上传的文件完成更新。',
            'deleted' => '成功删除指定的管路模板。',
            'updated' => '成功更新管理模板的配置。',
            'script_updated' => '管理模板的安装脚本已经成功更新并且会在安装新服务器时被执行。',
            'egg_created' => '一个管理模板已经成功创建. 你需要重启所有正在运行的节点受控端来使该模板生效。',
        ],
    ],
    'variables' => [
        'notices' => [
            'variable_deleted' => '参数 ":variable" 已被移除，在服务器重装之后将不在有效。',
            'variable_updated' => '参数 ":variable" 已更新。 你需要重装所有服务器来使该参数生效.',
            'variable_created' => '新的参数已经创建并被赋值，该操作会影响此管理模板下的所有服务器',
        ],
    ],
];
