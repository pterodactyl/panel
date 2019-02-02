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
        'created' => '已成功创建 :name 。',
        'deleted' => '已成功从面板删除指定的管理模块。',
        'updated' => '已成功更新管理模块选项。',
    ],
    'eggs' => [
        'notices' => [
            'imported' => '已成功导入管理模板。',
            'updated_via_import' => '此管理模板已按照上传的文件完成更新。',
            'deleted' => '已成功删除指定的管路模板。',
            'updated' => '已成功更新管理模板的配置。',
            'script_updated' => '已成功更新孵化蛋安装脚本且将于服务器安装时自动执行。',
            'egg_created' => '一个管理模板已经成功创建. 你需要重启所有正在运行的节点受控端来使该模板生效。',
        ],
    ],
    'variables' => [
        'notices' => [
            'variable_deleted' => '已移除变量 ":variable" 且其在重构服务器镜像后将会失效。 ',
            'variable_updated' => '已更新变量 ":variable" 。您需要重构使用此变量的服务器以应用更改。',
            'variable_created' => '已成功创建新变量并分配给此孵化蛋。',
        ],
    ],
];
