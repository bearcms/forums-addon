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
 * @property ?string $title
 * @property ?string $text
 * @property string $categoryID
 * @property ?int $createdTime
 * @property \IvoPetkov\DataList|\BearCMS\Internal\Data\Models\ForumPostReply[] $replies
 * @internal
 */
class ForumPostReply
{

    use \IvoPetkov\DataObjectTrait;
    use \IvoPetkov\DataObjectToArrayTrait;

    function __construct()
    {
        $this->defineProperty('id', [
            'type' => 'string'
        ]);
        $this->defineProperty('status', [
            'type' => 'string'
        ]);
        $this->defineProperty('author', [
            'type' => 'array'
        ]);
        $this->defineProperty('text', [
            'type' => '?string'
        ]);
        $this->defineProperty('createdTime', [
            'type' => '?int'
        ]);
    }

}
