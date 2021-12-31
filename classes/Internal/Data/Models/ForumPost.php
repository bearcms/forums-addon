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
        $this->defineProperty('id', [
            'type' => 'string'
        ]);
        $this->defineProperty('status', [
            'type' => 'string'
        ]);
        $this->defineProperty('author', [
            'type' => 'array'
        ]);
        $this->defineProperty('title', [
            'type' => '?string'
        ]);
        $this->defineProperty('text', [
            'type' => '?string'
        ]);
        $this->defineProperty('categoryID', [
            'type' => 'string'
        ]);
        $this->defineProperty('createdTime', [
            'type' => '?int'
        ]);
        $this->defineProperty('replies', [
            'type' => '\IvoPetkov\DataList',
            'init' => function () {
                return new \IvoPetkov\DataList();
            }
        ]);
        $this->defineProperty('lastChangeTime', [
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
