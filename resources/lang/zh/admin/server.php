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
        'no_new_default_allocation' => '你正在尝试删除此服务器的默认配额，但是该服务器没有足够的后备配额。',
        'marked_as_failed' => '这个服务器目前被标记为安装失败。 当前状态不能改变为此状态。',
        'bad_variable' => '变量 :name 有一个已确认的错误 。',
        'daemon_exception' => '连接受控端时发生意外 返回错误码 HTTP/:code response code. 此错误已被记录。',
        'default_allocation_not_found' => '请求的默认配额没有在这台服务器上找到。',
    ],
    'alerts' => [
        'startup_changed' => '该服务器的启动配置已被更新. 如果此服务器所属的管理模块或管理模板更改，此时将发生一次配置重设',
        'server_deleted' => '成功从系统中删除服务器',
        'server_created' => '创建服务器成功。 请稍后几分钟，受控端将尽快完成服务器安装',
        'build_updated' => '启动参数已更改。 一些修改需要重启该服务器后生效。',
        'suspension_toggled' => '服务器状态已更改为 :status.',
        'rebuild_on_boot' => '此服务器已被标记为需要在Docker容器中启动。 此操作会在下次重启后生效。',
        'install_toggled' => '此服务器的安装状态已被更改',
        'server_reinstalled' => '此服务器目前已置于重装队列中，即将开始重装',
        'details_updated' => '服务器信息成功被更新',
        'docker_image_updated' => '成功更改用于该服务器的默认的Docker镜像。 此操作需要重启后生效',
        'node_required' => '你需要至少一个节点才能开始添加服务器',
    ],
];
