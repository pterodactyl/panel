<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'validation' => [
        'fqdn_not_resolvable' => '提供的域名或地址没有解析到一个合法的IP地址.',
        'fqdn_required_for_ssl' => '这个节点要求解析到一个公共IP的域名必须使用SSL',
    ],
    'notices' => [
        'allocations_added' => '配额已经成功的被添加到这个节点.',
        'node_deleted' => '节点成功从面板中移除.',
        'location_required' => '在你可以添加一个节点之前必须至少有一个可用区配置。',
        'node_created' => '节点新建成功！ 使用 \'Configuration\' 标签，你可以在此节点上自动配置受控端. <strong>在你可以创建服务器之前，你必须至少分配一个IP和端口</strong>',
        'node_updated' => '节点信息更新成功！如果任何节点受控端的设置更改了，您需要重启受控端来使设置生效.',
        'unallocated_deleted' => '已删除 <code>:ip</code> 上的所有未分配的端口',
    ],
];
