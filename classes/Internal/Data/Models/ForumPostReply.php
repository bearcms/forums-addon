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
 * @property string $status
 * @property array $author
 * @property ?string $text
 * @property ?int $createdTime
 * @internal
 */
class ForumPostReply
{

    use \IvoPetkov\DataObjectTrait;
    use \IvoPetkov\DataObjectToArrayTrait;

    function __construct()
    {
        $this
            ->defineProperty('id', [
                'type' => 'string'
            ])
            ->defineProperty('status', [
                'type' => 'string'
            ])
            ->defineProperty('author', [
                'type' => 'array'
            ])
            ->defineProperty('text', [
                'type' => '?string'
            ])
            ->defineProperty('createdTime', [
                'type' => '?int'
            ]);
    }
}
