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
        'ivopetkov/html-server-components-bearframework-addon',
        'ivopetkov/form-bearframework-addon',
        'ivopetkov/users-bearframework-addon',
        'ivopetkov/server-requests-bearframework-addon',
        'ivopetkov/client-packages-bearframework-addon',
        'ivopetkov/form-elements-bearframework-addon',
        'ivopetkov/js-lightbox-bearframework-addon',
        'ivopetkov/html5-dom-document-js-bearframework-addon'
    ]
]);
