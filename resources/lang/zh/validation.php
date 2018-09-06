<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ' :attribute 被接受.',
    'active_url' => ' :attribute 不是一个有效的URL.',
    'after' => ' :attribute 必须是一个位于 :date 之后的日期.',
    'after_or_equal' => ' :attribute 必须是 :date 之后或同样的日期.',
    'alpha' => ' :attribute 只能含有字母.',
    'alpha_dash' => ':attribute 只能含有数字字母和分隔线.',
    'alpha_num' => ' :attribute 只能含有数字和字母.',
    'array' => ' :attribute 必须是个数组.',
    'before' => ' :attribute 必须是一个位于 :date 之前的日前.',
    'before_or_equal' => ' :attribute 必须是 :date 之前或同样的日期.',
    'between' => [
        'numeric' => ' :attribute 必须在 :min 到 :max 之间.',
        'file' => ' :attribute 必须在 :min 到 :max KB 之间.',
        'string' => ' :attribute m必须在 :min 到 :max 个字符之间.',
        'array' => ' :attribute 必须在 :min 到 :max 个项目之间.',
    ],
    'boolean' => ' :attribute 填入的必须为 true 或 false.',
    'confirmed' => ' :attribute 确认不匹配.',
    'date' => ' :attribute 不是一个合法的日期.',
    'date_format' => ' :attribute 不是正确的格式： :format.',
    'different' => ' :attribute 和 :other 必须不同.',
    'digits' => ' :attribute 必须为 :digits 个数字.',
    'digits_between' => ' :attribute 必须在 :min 到 :max 个数字间.',
    'dimensions' => ' :attribute 有一个非法的镜像大小.',
    'distinct' => ' :attribute 填入了一个重复的值.',
    'email' => ' :attribute 必须是一个合法的Email地址.',
    'exists' => '所选择的 :attribute 无效.',
    'file' => ' :attribute 必须为一个文件.',
    'filled' => ' :attribute 为必填项目.',
    'image' => ' :attribute 必须是一个镜像.',
    'in' => '所选择的 :attribute 无效.',
    'in_array' => ' :attribute 填入的信息在 :other 不存在.',
    'integer' => ' :attribute 必须是一个整数.',
    'ip' => ' :attribute 必须是一个合法的IP地址.',
    'json' => ' :attribute 必须是一个合法的JSON字符串.',
    'max' => [
        'numeric' => ' :attribute 不能大于 :max.',
        'file' => ' :attribute 不能大于 :max KB.',
        'string' => ' :attribute 不能多于 :max 个字符.',
        'array' => ' :attribute 不能多于 :max 个项目.',
    ],
    'mimes' => ' :attribute 文件类型必须为: :values.',
    'mimetypes' => ' :attribute 文件类型必须为: :values.',
    'min' => [
        'numeric' => ' :attribute 至少应在 :min.',
        'file' => ' :attribute 至少应在 :min KB.',
        'string' => ' :attribute 至少应在 :min 个字符.',
        'array' => ' :attribute 至少应有 :min 个项目.',
    ],
    'not_in' => '所选择的 :attribute 不正确.',
    'numeric' => ' :attribute 必须是个数字.',
    'present' => ' :attribute 填入的必须存在.',
    'regex' => ' :attribute 格式不正确.',
    'required' => ' :attribute 为必填.',
    'required_if' => ' :attribute 被要求填入， 当 :other 为 :value 的时候.',
    'required_unless' => ' :attribute 被要求填入，除非 :other 为 :values.',
    'required_with' => ' :attribute 被要求填入，当 :values 存在的时候.',
    'required_with_all' => ' :attribute 被要求填入，当 :values 存在.',
    'required_without' => ' :attribute 被要求填入，当 :values 不存在.',
    'required_without_all' => ' :attribute 被要求填入，当  :values 都不存在.',
    'same' => ' :attribute 和 :other 必须相同.',
    'size' => [
        'numeric' => ' :attribute 必须为 :size.',
        'file' => ' :attribute 必须为 :size KB.',
        'string' => ' :attribute 必须为 :size 个字符.',
        'array' => ' :attribute 必须包含 :size 个项目.',
    ],
    'string' => ' :attribute 必须为字符串.',
    'timezone' => ' :attribute 必须是一个有效的时区.',
    'unique' => ' :attribute 已经被使用.',
    'uploaded' => ' :attribute 上传失败.',
    'url' => ' :attribute 格式不合法.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],

    // Internal validation logic for Pterodactyl
    'internal' => [
        'variable_value' => ':env variable',
    ],
];
