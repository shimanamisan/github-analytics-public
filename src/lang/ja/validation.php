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

    'accepted' => ':attributeを承認してください。',
    'accepted_if' => ':otherが:valueの場合、:attributeを承認してください。',
    'active_url' => ':attributeは有効なURLではありません。',
    'after' => ':attributeには:dateより後の日付を指定してください。',
    'after_or_equal' => ':attributeには:date以降の日付を指定してください。',
    'alpha' => ':attributeには英字のみ使用できます。',
    'alpha_dash' => ':attributeには英数字、ダッシュ、アンダースコアのみ使用できます。',
    'alpha_num' => ':attributeには英数字のみ使用できます。',
    'array' => ':attributeは配列でなければなりません。',
    'ascii' => ':attributeには半角英数字と記号のみ使用できます。',
    'before' => ':attributeには:dateより前の日付を指定してください。',
    'before_or_equal' => ':attributeには:date以前の日付を指定してください。',
    'between' => [
        'array' => ':attributeは:min個から:max個の間でなければなりません。',
        'file' => ':attributeは:min KBから:max KBの間でなければなりません。',
        'numeric' => ':attributeは:minから:maxの間でなければなりません。',
        'string' => ':attributeは:min文字から:max文字の間でなければなりません。',
    ],
    'boolean' => ':attributeはtrueかfalseでなければなりません。',
    'can' => ':attributeに許可されていない値が含まれています。',
    'confirmed' => ':attributeの確認が一致しません。',
    'contains' => ':attributeに必須の値が含まれていません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attributeは有効な日付ではありません。',
    'date_equals' => ':attributeは:dateと等しい日付でなければなりません。',
    'date_format' => ':attributeは:format形式と一致しません。',
    'decimal' => ':attributeは小数点以下:decimal桁でなければなりません。',
    'declined' => ':attributeを拒否してください。',
    'declined_if' => ':otherが:valueの場合、:attributeを拒否してください。',
    'different' => ':attributeと:otherは異なっていなければなりません。',
    'digits' => ':attributeは:digits桁でなければなりません。',
    'digits_between' => ':attributeは:min桁から:max桁の間でなければなりません。',
    'dimensions' => ':attributeの画像サイズが無効です。',
    'distinct' => ':attributeに重複した値があります。',
    'doesnt_end_with' => ':attributeは次のいずれかで終わってはいけません: :values',
    'doesnt_start_with' => ':attributeは次のいずれかで始まってはいけません: :values',
    'email' => ':attributeは有効なメールアドレスでなければなりません。',
    'ends_with' => ':attributeは次のいずれかで終わらなければなりません: :values',
    'enum' => '選択された:attributeは無効です。',
    'exists' => '選択された:attributeは無効です。',
    'extensions' => ':attributeは次のいずれかの拡張子でなければなりません: :values',
    'file' => ':attributeはファイルでなければなりません。',
    'filled' => ':attributeは必須です。',
    'gt' => [
        'array' => ':attributeは:value個より多くなければなりません。',
        'file' => ':attributeは:value KBより大きくなければなりません。',
        'numeric' => ':attributeは:valueより大きくなければなりません。',
        'string' => ':attributeは:value文字より多くなければなりません。',
    ],
    'gte' => [
        'array' => ':attributeは:value個以上でなければなりません。',
        'file' => ':attributeは:value KB以上でなければなりません。',
        'numeric' => ':attributeは:value以上でなければなりません。',
        'string' => ':attributeは:value文字以上でなければなりません。',
    ],
    'hex_color' => ':attributeは有効な16進数カラーコードでなければなりません。',
    'image' => ':attributeは画像でなければなりません。',
    'in' => '選択された:attributeは無効です。',
    'in_array' => ':attributeは:otherに存在しません。',
    'integer' => ':attributeは整数でなければなりません。',
    'ip' => ':attributeは有効なIPアドレスでなければなりません。',
    'ipv4' => ':attributeは有効なIPv4アドレスでなければなりません。',
    'ipv6' => ':attributeは有効なIPv6アドレスでなければなりません。',
    'json' => ':attributeは有効なJSON文字列でなければなりません。',
    'list' => ':attributeはリストでなければなりません。',
    'lowercase' => ':attributeは小文字でなければなりません。',
    'lt' => [
        'array' => ':attributeは:value個より少なくなければなりません。',
        'file' => ':attributeは:value KBより小さくなければなりません。',
        'numeric' => ':attributeは:valueより小さくなければなりません。',
        'string' => ':attributeは:value文字より少なくなければなりません。',
    ],
    'lte' => [
        'array' => ':attributeは:value個以下でなければなりません。',
        'file' => ':attributeは:value KB以下でなければなりません。',
        'numeric' => ':attributeは:value以下でなければなりません。',
        'string' => ':attributeは:value文字以下でなければなりません。',
    ],
    'mac_address' => ':attributeは有効なMACアドレスでなければなりません。',
    'max' => [
        'array' => ':attributeは:max個以下でなければなりません。',
        'file' => ':attributeは:max KB以下でなければなりません。',
        'numeric' => ':attributeは:max以下でなければなりません。',
        'string' => ':attributeは:max文字以下でなければなりません。',
    ],
    'max_digits' => ':attributeは:max桁以下でなければなりません。',
    'mimes' => ':attributeは次のファイル形式でなければなりません: :values',
    'mimetypes' => ':attributeは次のファイル形式でなければなりません: :values',
    'min' => [
        'array' => ':attributeは:min個以上でなければなりません。',
        'file' => ':attributeは:min KB以上でなければなりません。',
        'numeric' => ':attributeは:min以上でなければなりません。',
        'string' => ':attributeは:min文字以上でなければなりません。',
    ],
    'min_digits' => ':attributeは:min桁以上でなければなりません。',
    'missing' => ':attributeは存在してはいけません。',
    'missing_if' => ':otherが:valueの場合、:attributeは存在してはいけません。',
    'missing_unless' => ':otherが:valueでない場合、:attributeは存在してはいけません。',
    'missing_with' => ':valuesが存在する場合、:attributeは存在してはいけません。',
    'missing_with_all' => ':valuesが存在する場合、:attributeは存在してはいけません。',
    'multiple_of' => ':attributeは:valueの倍数でなければなりません。',
    'not_in' => '選択された:attributeは無効です。',
    'not_regex' => ':attributeの形式が無効です。',
    'numeric' => ':attributeは数値でなければなりません。',
    'password' => [
        'letters' => ':attributeは少なくとも1つの文字を含む必要があります。',
        'mixed' => ':attributeは少なくとも1つの大文字と1つの小文字を含む必要があります。',
        'numbers' => ':attributeは少なくとも1つの数字を含む必要があります。',
        'symbols' => ':attributeは少なくとも1つの記号を含む必要があります。',
        'uncompromised' => 'この:attributeは情報漏洩で発見されています。別の:attributeを選択してください。',
    ],
    'present' => ':attributeが存在しなければなりません。',
    'present_if' => ':otherが:valueの場合、:attributeが存在しなければなりません。',
    'present_unless' => ':otherが:valueでない場合、:attributeが存在しなければなりません。',
    'present_with' => ':valuesが存在する場合、:attributeが存在しなければなりません。',
    'present_with_all' => ':valuesが存在する場合、:attributeが存在しなければなりません。',
    'prohibited' => ':attributeは禁止されています。',
    'prohibited_if' => ':otherが:valueの場合、:attributeは禁止されています。',
    'prohibited_unless' => ':otherが:valuesでない場合、:attributeは禁止されています。',
    'prohibits' => ':attributeが存在する場合、:otherは禁止されています。',
    'regex' => ':attributeの形式が無効です。',
    'required' => ':attributeは必須です。',
    'required_array_keys' => ':attributeには次のエントリが含まれている必要があります: :values',
    'required_if' => ':otherが:valueの場合、:attributeは必須です。',
    'required_if_accepted' => ':otherが承認された場合、:attributeは必須です。',
    'required_if_declined' => ':otherが拒否された場合、:attributeは必須です。',
    'required_unless' => ':otherが:valuesでない場合、:attributeは必須です。',
    'required_with' => ':valuesが存在する場合、:attributeは必須です。',
    'required_with_all' => ':valuesが存在する場合、:attributeは必須です。',
    'required_without' => ':valuesが存在しない場合、:attributeは必須です。',
    'required_without_all' => ':valuesがすべて存在しない場合、:attributeは必須です。',
    'same' => ':attributeと:otherは一致しなければなりません。',
    'size' => [
        'array' => ':attributeは:size個でなければなりません。',
        'file' => ':attributeは:size KBでなければなりません。',
        'numeric' => ':attributeは:sizeでなければなりません。',
        'string' => ':attributeは:size文字でなければなりません。',
    ],
    'starts_with' => ':attributeは次のいずれかで始まらなければなりません: :values',
    'string' => ':attributeは文字列でなければなりません。',
    'timezone' => ':attributeは有効なタイムゾーンでなければなりません。',
    'unique' => ':attributeはすでに使用されています。',
    'uploaded' => ':attributeのアップロードに失敗しました。',
    'uppercase' => ':attributeは大文字でなければなりません。',
    'url' => ':attributeは有効なURLでなければなりません。',
    'ulid' => ':attributeは有効なULIDでなければなりません。',
    'uuid' => ':attributeは有効なUUIDでなければなりません。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'カスタムメッセージ',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'name' => '名前',
        'username' => 'ユーザー名',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード確認',
        'current_password' => '現在のパスワード',
        'new_password' => '新しいパスワード',
        'remember' => 'ログイン状態を保持',
        'title' => 'タイトル',
        'content' => '内容',
        'description' => '説明',
        'excerpt' => '抜粋',
        'date' => '日付',
        'time' => '時刻',
        'available' => '利用可能',
        'size' => 'サイズ',
        'is_admin' => '管理者権限',
        'is_active' => 'ステータス',
        'github_token' => 'GitHubトークン',
        'github_owner' => 'GitHubオーナー',
    ],

];

