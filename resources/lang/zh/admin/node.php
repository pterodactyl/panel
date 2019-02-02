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
        'fqdn_not_resolvable' => '提供的正式域名（FQDN）或 IP 地址未解析到有效的 IP 地址。',
        'fqdn_required_for_ssl' => '此节点需要解析到公网 IP 地址的正式域名才能使用 SSL。',
    ],
    'notices' => [
        'allocations_added' => '已成功为此节点分配地址。',
        'node_deleted' => '已成功从面板中移除节点。',
        'location_required' => '您必须至少配置一个区域才能添加节点至面板。',
        'node_created' => '已成功新建节点！您可通过\'配置\'选项卡已自动配置此机器上的守护程序。<strong>在您添加服务器前，您必须先分配一个 IP 地址及端口。</strong>',
        'node_updated' => '已更新节点信息。若守护程序设置更改，您需要重启守护程序才能生效。',
        'unallocated_deleted' => '已为 <code>:ip</code> 删除所有未分配的端口。',
    ],
];
