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

    'accepted' => ' :attribute 必须被接受。',
    'active_url' => ' :attribute 为非法 URL。',
    'after' => ' :attribute 必须为 :date 之后的日期。',
    'after_or_equal' => ' :attribute 必须为 :date 之后或相同的日期。',
    'alpha' => ' :attribute 只能含有字母。',
    'alpha_dash' => ':attribute 只能含有数字、字母和分隔线。',
    'alpha_num' => ' :attribute 只能含有数字和字母。',
    'array' => ' :attribute 必须为数组。',
    'before' => ' :attribute 必须为 :date 之前的日期。',
    'before_or_equal' => ' :attribute 必须为 :date 之前或相同日期。',
    'between' => [
        'numeric' => ' :attribute 必须介于 :min 与 :max 之间。',
        'file' => ' :attribute 必须介于 :min 与 :max KB 之间。',
        'string' => ' :attribute 必须介于 :min 与 :max 个字符之间。',
        'array' => ' :attribute 必须介于 :min 与 :max 个项之间。',
    ],
    'boolean' => ' :attribute 字段必须为 true 或 false。',
    'confirmed' => ' :attribute 与确认项不匹配。',
    'date' => ' :attribute 为非法日期。',
    'date_format' => ' :attribute 不匹配格式 :format。',
    'different' => ' :attribute 必须异于 :other。',
    'digits' => ' :attribute 必须为 :digits 位数字。',
    'digits_between' => ' :attribute 必须介于 :min 与 :max 位数字之间。',
    'dimensions' => ' :attribute 的图像大小非法。',
    'distinct' => ' :attribute 含有重复值。',
    'email' => ' :attribute 必须为合法电子邮件地址。',
    'exists' => '所选择的 :attribute 无效。',
    'file' => ' :attribute 必须为文件。',
    'filled' => ' :attribute 为必填项。',
    'image' => ' :attribute 必须为图像。',
    'in' => '所选择的 :attribute 无效。',
    'in_array' => ' :attribute 字段不存在于 :other。',
    'integer' => ' :attribute 必须为整数。',
    'ip' => ' :attribute 必须为合法 IP 地址。',
    'json' => ' :attribute 必须为合法 JSON 字符串。',
    'max' => [
        'numeric' => ' :attribute 不能大于 :max。',
        'file' => ' :attribute 不能大于 :max KB。',
        'string' => ' :attribute 不能多于 :max 字符。',
        'array' => ' :attribute 不能多于 :max 项。',
    ],
    'mimes' => ' :attribute 必须为 :values 类型文件。',
    'mimetypes' => ' :attribute 必须为 :values 类型文件。',
    'min' => [
        'numeric' => ' :attribute 应至少为 :min。',
        'file' => ' :attribute 应至少为 :min KB。',
        'string' => ' :attribute 应至少有 :min 个字符。',
        'array' => ' :attribute 应至少有 :min 项。',
    ],
    'not_in' => '所选择的 :attribute 非法。',
    'numeric' => ' :attribute 必须为数字。',
    'present' => ' :attribute 字段必须存在。',
    'regex' => ' :attribute 格式非法。',
    'required' => ' :attribute 为必填项。',
    'required_if' => '除非 :other 为 :value，否则 :attribute 为必填项。',
    'required_unless' => '除非 :other 位于 :value 中，否则 :attribute 为必填项。',
    'required_with' => '除非 :values 存在，否则 :attribute 为必填项。',
    'required_with_all' => '除非 :values 存在，否则 :attribute 为必填项。',
    'required_without' => '除非 :values 不存在，否则 :attribute 为必填项。',
    'required_without_all' => '除非 :values 均存在，否则 :attribute 为必填项。',
    'same' => ' :attribute 和 :other 必须匹配。',
    'size' => [
        'numeric' => ' :attribute 必须为 :size。',
        'file' => ' :attribute 必须为 :size KB。',
        'string' => ' :attribute 必须为 :size 个字符。',
        'array' => ' :attribute 必须包含 :size 个项目。',
    ],
    'string' => ' :attribute 必须为字符串。',
    'timezone' => ' :attribute 必须为有效时区。',
    'unique' => ' :attribute 已被使用。',
    'uploaded' => ' :attribute 上传失败。',
    'url' => ' :attribute 格式不合法。',

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
        'variable_value' => ':env 变量',
    ],
];