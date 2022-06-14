<?php

/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS\Internal\Data\Models;

use BearCMS\Internal\ForumsData;
use BearCMS\Internal\ForumsUtilities;
use BearFramework\App;

/**
 * @property string $id
 * @property string $status
 * @property array $author
 * @property ?string $title
 * @property ?string $text
 * @property string $categoryID
 * @property ?int $createdTime
 * @property \IvoPetkov\DataList|\BearCMS\Internal\Data\Models\ForumPostReply[] $replies
 * @property ?int $lastChangeTime
 * @internal
 */
class ForumPost
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
            ->defineProperty('title', [
                'type' => '?string'
            ])
            ->defineProperty('text', [
                'type' => '?string'
            ])
            ->defineProperty('categoryID', [
                'type' => 'string'
            ])
            ->defineProperty('createdTime', [
                'type' => '?int'
            ])
            ->defineProperty('replies', [
                'type' => '\IvoPetkov\DataList',
                'init' => function () {
                    return new \IvoPetkov\DataList();
                }
            ])
            ->defineProperty('lastChangeTime', [
                'type' => '?int'
            ]);
    }

    /**
     * 
     * @return string
     */
    public function getURLPath(): string
    {
        return ForumsData::$forumPagesPathPrefix . ForumsUtilities::getSlug($this->id, $this->title) . '/';
    }

    /**
     * 
     * @return string
     */
    public function getURL(): string
    {
        $app = App::get();
        return $app->urls->get($this->getURLPath());
    }
}
