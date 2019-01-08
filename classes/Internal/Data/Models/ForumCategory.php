<?php

/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS\Internal\Data\Models;

/**
 * @property string $id
 * @property string $name
 * @property string $status
 * @internal
 */
class ForumCategory
{

    use \IvoPetkov\DataObjectTrait;
    use \IvoPetkov\DataObjectToArrayTrait;

    function __construct()
    {
        $this->defineProperty('id', [
            'type' => 'string'
        ]);
        $this->defineProperty('name', [
            'type' => 'string'
        ]);
        $this->defineProperty('status', [
            'type' => 'string'
        ]);
    }

}
