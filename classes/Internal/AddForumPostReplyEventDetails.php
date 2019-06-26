<?php

/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS\Internal;

/**
 * @property string $forumPostID
 * @property string $forumPostReplyID
 * @internal
 */
class AddForumPostReplyEventDetails
{

    use \IvoPetkov\DataObjectTrait;

    /**
     * 
     * @param string $forumPostID
     * @param string $forumPostReplyID
     */
    public function __construct(string $forumPostID, string $forumPostReplyID)
    {
        $this
                ->defineProperty('forumPostID', [
                    'type' => 'string'
                ])
                ->defineProperty('forumPostReplyID', [
                    'type' => 'string'
                ])
        ;
        $this->forumPostID = $forumPostID;
        $this->forumPostReplyID = $forumPostReplyID;
    }

}
