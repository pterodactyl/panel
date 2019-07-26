<?php

return [
    'validation_error' => '请求中有一个或多个字段出错',
    'errors' => [
        'return' => '返回上页',
        'home' => '返回主页',
        '403' => [
            'header' => '禁止访问',
            'desc' => '您没有访问此服务器上的资源的权限。',
        ],
        '404' => [
            'header' => '文件未找到',
            'desc' => '我们无法在此服务器上找到所请求的资源。',
        ],
        'installing' => [
            'header' => '服务器安装中',
            'desc' => '请求的服务器正在完成安装进程。请几分钟后再来查看，您将在此过程完成后收到电子邮件提醒。',
        ],
        'suspended' => [
            'header' => '服务器已停用',
            'desc' => '此服务器已停用且无法访问。',
        ],
        'maintenance' => [
            'header' => '节点维护中',
            'title' => '暂时不可用',
            'desc' => '此节点正在维护，当前无法访问。',
        ],
    ],
    'index' => [
        'header' => '您的服务器',
        'header_sub' => '您有权限访问的服务器。',
        'list' => '服务器列表',
    ],
    'api' => [
        'index' => [
            'list' => '您的密钥',
            'header' => '账户 API',
            'header_sub' => '管理允许您对面板执行操作的 API 密钥。',
            'create_new' => '新建 API 密钥',
            'keypair_created' => '已成功生成 API 密钥并列于下方。',
        ],
        'new' => [
            'header' => '新建 API 密钥',
            'header_sub' => '新建账户访问密钥。',
            'form_title' => '详细信息',
            'descriptive_memo' => [
                'title' => '描述',
                'description' => '请输入便于分辨此密钥的描述信息。',
            ],
            'allowed_ips' => [
                'title' => '许可 IP',
                'description' => '输入允许使用此密钥的 IP 地址列表。此功能支持无类别域间路由。留空将允许所有 IP 使用。',
            ],
        ],
    ],
    'account' => [
        'details_updated' => '已成功更新您的账户信息。',
        'invalid_password' => '您提供的密码无效。',
        'header' => '您的账户',
        'header_sub' => '管理您的账户信息.',
        'update_pass' => '修改密码',
        'update_email' => '修改电子邮件地址',
        'current_password' => '当前密码',
        'new_password' => '新密码',
        'new_password_again' => '重复密码',
        'new_email' => '新电子邮件地址',
        'first_name' => '姓氏',
        'last_name' => '名称',
        'update_identity' => '更新个人信息',
        'username_help' => '您的用户名必须未被他人使用，且仅包含下列字符：:requirements。',
        'language' => '语言',
    ],
    'security' => [
        'session_mgmt_disabled' => '您的托管商未启用此界面来管理账户会话。',
        'header' => '账户安全',
        'header_sub' => '管理活跃中的会话与两步验证。',
        'sessions' => '活跃会话',
        '2fa_header' => '两步验证',
        '2fa_token_help' => '请填入由应用程序所生成的两步验证密钥（Google 身份验证器、Authy 等）。',
        'disable_2fa' => '关闭两步验证',
        '2fa_enabled' => '已为此账户启用两步验证，您将需要验证以登录至此账户。若您想关闭两步验证，您只需在下方输入密钥并提交即可。',
        '2fa_disabled' => '已关闭两步验证！您应启用此功能来作为此账户的附加防护手段。',
        'enable_2fa' => '启用两步验证',
        '2fa_qr' => '在您的设备上配置两步验证',
        '2fa_checkpoint_help' => '在您的手机上使用两步验证应用程序扫描左侧的二维码或直接输入下方的代码。录入后，请在下方输入应用程序生成的密码。',
        '2fa_disable_error' => '提供的两步验证密钥无效。未关闭此账户的两步验证。两步验证密码错误. 关闭两步验证失败.',
    ],
];
