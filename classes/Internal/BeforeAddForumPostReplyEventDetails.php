<?php

/*
 * Bear CMS addon for Bear Framework
 * https://bearcms.com/
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS\Internal;

/**
 * @property string $forumPostID
 * @property array $author
 * @property string $text
 * @property string $status
 * @property bool $cancel
 * @property string $cancelMessage
 * @internal
 */
class BeforeAddForumPostReplyEventDetails
{

    use \IvoPetkov\DataObjectTrait;

    /**
     * 
     * @param string $forumPostID
     * @param array $author
     * @param string $text
     * @param string $status
     */
    public function __construct(string $forumPostID, array $author, string $text, string $status)
    {
        $this
                ->defineProperty('forumPostID', [
                    'type' => 'string'
                ])
                ->defineProperty('author', [
                    'type' => 'array'
                ])
                ->defineProperty('text', [
                    'type' => 'string'
                ])
                ->defineProperty('status', [
                    'type' => 'string'
                ])
                ->defineProperty('cancel', [
                    'type' => 'bool'
                ])
                ->defineProperty('cancelMessage', [
                    'type' => 'string'
                ])
        ;
        $this->forumPostID = $forumPostID;
        $this->author = $author;
        $this->text = $text;
        $this->status = $status;
    }

}
