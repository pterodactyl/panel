<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'exceptions' => [
        'no_new_default_allocation' => '您正在尝试删除此服务器的默认分配地址，但此服务器可用的备选分配地址。',
        'marked_as_failed' => '此服务器被标记为安装失败。当前状态无法在面板中被改变。',
        'bad_variable' => '变量 :name 有验证错误。',
        'daemon_exception' => '连接守护程序时返回 HTTP/:code 反馈码。此错误已被记录。',
        'default_allocation_not_found' => '未在此服务器上找到请求的默认分配地址。',
    ],
    'alerts' => [
        'startup_changed' => '已更新此服务器的启动配置。若此服务器的启动模板被更改，其将被重新安装。',
        'server_deleted' => '已成功从系统中删除服务器。',
        'server_created' => '已成功在面板中创建服务器。请稍等面板完全安装服务器完毕。',
        'build_updated' => '已更新此服务器的构建参数。部分更改可能需要重启才能生效。启动参数已更改。',
        'suspension_toggled' => '服务器停用状态已更改为 :status.',
        'rebuild_on_boot' => '此服务器已被标记为需要重新构建 Docker 容器。此操作会在下次启动服务器后生效。',
        'install_toggled' => '此服务器的安装状态已被更改。',
        'server_reinstalled' => '此服务器已置于即将开始的重装队列中。',
        'details_updated' => '已成功更新服务器信息。',
        'docker_image_updated' => '已成功更改此服务器使用的默认 Docker 镜像。此操作需要重启以应用更改。',
        'node_required' => '您需要配置至少一个节点以添加服务器至面板。',
    ],
];
