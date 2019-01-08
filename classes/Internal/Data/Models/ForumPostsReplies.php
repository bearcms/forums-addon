<?php

/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS\Internal\Data\Models;

/**
 * @internal
 */
class ForumPostsReplies
{

    /**
     * Retrieves a list of all forum replies
     * 
     * @return \IvoPetkov\DataList List containing all forum replies data
     */
    public function getList()
    {
        $list = \BearCMS\Internal\Data::getList('bearcms/forums/posts/post/');

        $result = new \IvoPetkov\DataList();
        foreach ($list as $value) {
            $rawData = json_decode($value, true);
            if (isset($rawData['id'], $rawData['replies'])) {
                foreach ($rawData['replies'] as $replyData) {
                    $reply = new \BearCMS\Internal\Data\Models\ForumPostReply();
                    $reply->id = $replyData['id'];
                    $reply->status = $replyData['status'];
                    $reply->author = $replyData['author'];
                    $reply->text = $replyData['text'];
                    $reply->createdTime = $replyData['createdTime'];
                    $reply->forumPostID = $rawData['id'];
                    $result[] = $reply;
                }
            }
        }
        return $result;
    }

}
