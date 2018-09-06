<?php

return [
    'daemon_connection_failed' => '连接受控端时发生意外 返回错误码 HTTP/:code response code. 此错误已被记录',
    'node' => [
        'servers_attached' => '节点删除必须按先移除其所有的服务器.',
        'daemon_off_config_updated' => '受控端配置 <strong>已被更新</strong>, 但是自动更新受控端上的配置文件时发生错误. 你需要手动将配置文件 (core.json) 更新至受控端来完成更新.',
    ],
    'allocations' => [
        'server_using' => '一个服务器已分配该地址. 一个地址只有在无服务器使用时才能删除.',
        'too_many_ports' => '一次添加1000个以上的端口是不被支持的.',
        'invalid_mapping' => '提供的端口： :port 无效，无法继续操作.',
        'cidr_out_of_range' => 'CIDR 标记 只允许掩码在 /25 到 /32之间。',
        'port_out_of_range' => '端口超过范围，范围必须在  1024 到 65535 之间.',
    ],
    'nest' => [
        'delete_has_servers' => '活动服务器使用的管理模块不能被删除.',
        'egg' => [
            'delete_has_servers' => '活动服务器使用的管理模板不能被删除.',
            'invalid_copy_id' => '管理模板复制的脚本ID无效.',
            'must_be_child' => ' "复制设置自"选项指定的目标必须是管理模块的附属.',
            'has_children' => '此管理模版附属有一个或多个管理模板. 在删除之前请先删除所有附属.',
        ],
        'variables' => [
            'env_not_unique' => '环境变量 :name 必须唯一.',
            'reserved_name' => '环境变量 :name 是被保护的，无法指定为变量.',
            'bad_validation_rule' => '环境变量规则 ":rule" 对于这个应用不是一个有效的规则.',
        ],
        'importer' => [
            'json_error' => '尝试导入JSON 文件时发生错误: :error.',
            'file_error' => '提供的JSON文件不合法.',
            'invalid_json_provided' => '提供的JSON文件格式不正确，无法被解析。',
        ],
    ],
    'packs' => [
        'delete_has_servers' => '活动服务器使用的整合包不能被删除',
        'update_has_servers' => '当前有服务器附属于包时无法修改关联选项的ID.',
        'invalid_upload' => '上传的文件不合法.',
        'invalid_mime' => '上传的文件不符合要求的文件类型 :type',
        'unreadable' => '服务器无法打开该压缩包.',
        'zip_extraction' => '解压时发生错误.',
        'invalid_archive_exception' => '压缩包缺失archive.tar.gz 或 import.json 文件在根目录.',
    ],
    'subusers' => [
        'editing_self' => '编辑您自己的子用户时不被允许的.',
        'user_is_owner' => '子用户无法添加服主.',
        'subuser_exists' => '那个电子邮件的用户已经是此服务器的子用户了.',
    ],
    'databases' => [
        'delete_has_databases' => '无法删除一个拥有活跃数据库的数据库服务器.',
    ],
    'tasks' => [
        'chain_interval_too_long' => '链接任务的最大间隔时间为15分钟。',
    ],
    'locations' => [
        'has_nodes' => '活动节点附属的可用区无法被删除.',
    ],
    'users' => [
        'node_revocation_failed' => '吊销密钥失败 <a href=":link">节点 #:node</a>. :error',
    ],
    'deployment' => [
        'no_viable_nodes' => '没有合适的节点来自动部署服务器',
        'no_viable_allocations' => '没有合适的地址来自动部署服务器',
    ],
    'api' => [
        'resource_not_found' => '需求的资源未找到.',
    ],
];
