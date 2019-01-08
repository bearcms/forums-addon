<?php

/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

BearFramework\Addons::register('bearcms/forums-addon', __DIR__, [
    'require' => [
        'bearcms/bearframework-addon',
        'bearframework/localization-addon',
        'ivopetkov/html-server-components-bearframework-addon'
    ]
]);
