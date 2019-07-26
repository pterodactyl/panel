<?php

return [
    'daemon_connection_failed' => '尝试连接守护程序是发生错误，状态码 HTTP/:code。此错误已被记录。',
    'node' => [
        'servers_attached' => '要删除节点，您必须先取消其与其他服务器的关联。',
        'daemon_off_config_updated' => '<strong>已更新</strong>守护程序配置，但在尝试自动更新守护程序配置文件时发生错误。您需要手动更新守护程序的配置文件（core.json）以应用更改。',
    ],
    'allocations' => [
        'server_using' => '已有服务器被分配到该地址。您必须先解除关联才能删除此地址。',
        'too_many_ports' => '不支持单次添加多于 1000 个端口。',
        'invalid_mapping' => '所提供的端口 :port 无效，无法继续操作。',
        'cidr_out_of_range' => '类别域间路由仅允许介于 /25 和 /32 之间的掩码。',
        'port_out_of_range' => '分配端口的范围必须介于 1024 至 65535 之间。',
    ],
    'nest' => [
        'delete_has_servers' => '无法删除附着到活跃服务器上的管理模块。',
        'egg' => [
            'delete_has_servers' => '无法删除附着到活跃服务器上的管理模块。',
            'invalid_copy_id' => '用于复制脚本的管理模板不存在，或脚本本身不存在。',
            'must_be_child' => '“复制设置自”选项指定的目标必须为所选管理模块的子选项。',
            'has_children' => '此管理模版为一个或多个管理模板的母模板。请先删除其他管理模板再删除此模板。',
        ],
        'variables' => [
            'env_not_unique' => '此管理面板的环境变量 :name 必须唯一。',
            'reserved_name' => '环境变量 :name 被保护的且无法被分配至其他变量。',
            'bad_validation_rule' => '验证规则 “:rule” 不是此应用程序的有效规则。',
        ],
        'importer' => [
            'json_error' => '导入 JSON 文件时发生错误：:error.',
            'file_error' => '所提供的 JSON 文件无效。',
            'invalid_json_provided' => '所提供的 JSON 文件格式无法被解析。',
        ],
    ],
    'packs' => [
        'delete_has_servers' => '无法删除依附到活跃服务器的整合包。',
        'update_has_servers' => '无法在有服务器依附至整合包时修改关联选项编号。',
        'invalid_upload' => '所提供的文件格式无效。',
        'invalid_mime' => '提供的文件不符合所需文件类型 :type',
        'unreadable' => '服务器无法打开所提供的归档文件。',
        'zip_extraction' => '提取归档文件至服务器时发生错误。',
        'invalid_archive_exception' => '整合包归档文件的根目录似乎缺少 archive.tar.gz 或 import.json。',
    ],
    'subusers' => [
        'editing_self' => '您无法作为子用户编辑您自己的子用户账号。',
        'user_is_owner' => '您无法作为子用户来添加为此服务器的服主。',
        'subuser_exists' => '使用该电子邮件地址的用户已被分配为此服务器的子用户。',
    ],
    'databases' => [
        'delete_has_databases' => '无法删除关联至活跃数据库的数据库服务器。',
    ],
    'tasks' => [
        'chain_interval_too_long' => '连环任务的最大时间间隔为 15 分钟。',
    ],
    'locations' => [
        'has_nodes' => '无法删除被依附活动节点的区域。',
    ],
    'users' => [
        'node_revocation_failed' => '注销<a href=":link">节点 #:node</a> 的密钥失败：:error',
    ],
    'deployment' => [
        'no_viable_nodes' => '无法找到满足自动化部署需求的节点。',
        'no_viable_allocations' => '无法找到满足自动化部署需求的分配地址。',
    ],
    'api' => [
        'resource_not_found' => '服务器上不存在所请求的资源。',
    ],
];
