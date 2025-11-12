<?php

return [
    [
        // 中文：顶级菜单项，放在 Configure 之后，不覆盖现有菜单
        'key'   => 'ai_review',
        'name'  => 'AI Review',
        'route' => 'admin.ai_review.index',
        'sort'  => 10,
        'icon'  => 'icon-magic',
    ],
];