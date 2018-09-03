<?php

return [
    'validation_error' => '请求中有一个或多个字段出错',
    'errors' => [
        'return' => '返回上一个页面',
        'home' => '返回主页',
        '403' => [
            'header' => '禁止访问',
            'desc' => '您没有权限访问此服务器上的资源.',
        ],
        '404' => [
            'header' => 'Not Found',
            'desc' => '未找到资源.',
        ],
        'installing' => [
            'header' => '服务器正在安装',
            'desc' => '请求的服务器仍然在部署中，请稍等几分钟，完成后您将收到一封电子邮件',
        ],
        'suspended' => [
            'header' => '服务器已暂停',
            'desc' => '此服务器已被暂停，无法访问，请联系管理员',
        ],
        'maintenance' => [
            'header' => '节点维护中',
            'title' => '暂时不可用',
            'desc' => '此节点正在维护，当前无法访问.',
        ],
    ],
    'index' => [
        'header' => '您的服务器',
        'header_sub' => '您当前可访问的服务器.',
        'list' => '服务器列表',
    ],
    'api' => [
        'index' => [
            'list' => '您的密钥',
            'header' => '账户 API',
            'header_sub' => '管理访问密钥允许您使用API操作面板.',
            'create_new' => '新建 API 密钥',
            'keypair_created' => '新建API密钥成功.',
        ],
        'new' => [
            'header' => '新建 API 密钥',
            'header_sub' => '创建一个新的账户API密钥.',
            'form_title' => '选项',
            'descriptive_memo' => [
                'title' => '描述',
                'description' => '添加一个关于此密钥的描述.',
            ],
            'allowed_ips' => [
                'title' => '允许的IP',
                'description' => '添加IP地址限制来保护API安全. CIDR 标记是被允许的. 留空将允许所有IP.',
            ],
        ],
    ],
    'account' => [
        'details_updated' => '您账户的信息成功更新.',
        'invalid_password' => '您提供的密码不正确.',
        'header' => '您的账户',
        'header_sub' => '管理您的账户信息.',
        'update_pass' => '修改密码',
        'update_email' => '修改 Email 地址',
        'current_password' => '当前密码',
        'new_password' => '新密码',
        'new_password_again' => '重复密码',
        'new_email' => '新 Email 地址',
        'first_name' => '姓',
        'last_name' => '名',
        'update_identity' => '更新个人信息',
        'username_help' => '您的用户名必须唯一（未被使用），并满足以下要求: :requirements.',
    ],
    'security' => [
        'session_mgmt_disabled' => '为了安全原因，您的此次会话无法访问用户管理.',
        'header' => '账户安全',
        'header_sub' => '管理活动会话和两步认证.',
        'sessions' => '活动中的会话',
        '2fa_header' => '两步验证',
        '2fa_token_help' => '填入您两步验证生成器生成的密码 (Google Authenticator, Authy, etc.).',
        'disable_2fa' => '关闭两步验证',
        '2fa_enabled' => '两步验证已开启，在您登陆面板时会要求两步验证.如果您想关闭两步验证，只需输入两步验证的密码即可',
        '2fa_disabled' => '两步验证已关闭! 您应该开启两步验证将其作为您账户的额外防护',
        'enable_2fa' => '开启两步验证',
        '2fa_qr' => '在您的设备上上配置两步验证',
        '2fa_checkpoint_help' => '使用两步验证需要用您的应用扫左侧二维码, 或手动输入下方的代码.完成后请将生成的密码输入下方方框.',
        '2fa_disable_error' => '两步验证密码错误. 关闭两步验证失败.',
    ],
];
