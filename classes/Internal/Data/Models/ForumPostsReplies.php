<?php

/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS\Internal\Data\Models;

use BearCMS\Internal\Data\Utilities\ForumPostsReplies as UtilitiesForumPostsReplies;

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
                $tempList = UtilitiesForumPostsReplies::createRepliesCollection($rawData['replies'], $rawData['id']);
                foreach ($tempList as $reply) {
                    $result[] = $reply;
                }
            }
        }
        return $result;
    }

    /**
     * 
     * @param string $forumPostID
     * @param string $replyID
     * @return \BearCMS\Internal\Data\Models\ForumPostReply|null
     */
    public function get(string $forumPostID, string $replyID): ?\BearCMS\Internal\Data\Models\ForumPostReply
    {
        $forumPosts = new \BearCMS\Internal\Data\Models\ForumPosts();
        $forumPost = $forumPosts->get($forumPostID);
        if ($forumPost !== null) {
            foreach ($forumPost->replies as $reply) {
                if ($reply->id === $replyID) {
                    return $reply;
                }
            }
        }
        return null;
    }
}
