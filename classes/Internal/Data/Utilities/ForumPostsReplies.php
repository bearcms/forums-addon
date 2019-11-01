<?php

/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS\Internal\Data\Utilities;

use BearFramework\App;

/**
 * @internal
 */
class ForumPostsReplies
{

    /**
     * 
     * @param string $forumPostID
     * @param array $author
     * @param string $text
     * @param string $status
     * @return void
     */
    static function add(string $forumPostID, array $author, string $text, string $status): void
    {
        $app = App::get();
        $dataKey = 'bearcms/forums/posts/post/' . md5($forumPostID) . '.json';
        $data = $app->data->getValue($dataKey);
        $data = $data !== null ? json_decode($data, true) : [];
        if (empty($data['id'])) {
            return;
        }
        if (empty($data['replies'])) {
            $data['replies'] = [];
        }
        $forumPostReplyID = md5(uniqid());
        $data['replies'][] = [
            'id' => $forumPostReplyID,
            'status' => $status,
            'author' => $author,
            'text' => $text,
            'createdTime' => time()
        ];
        $data['lastChangeTime'] = time();
        $app->data->set($app->data->make($dataKey, json_encode($data)));

        if (\BearCMS\Internal\Config::hasFeature('NOTIFICATIONS')) {
            if (!$app->tasks->exists('bearcms-send-new-forum-post-reply-notification')) {
                $app->tasks->add('bearcms-send-new-forum-post-reply-notification', [
                    'forumPostID' => $forumPostID,
                    'forumPostReplyID' => $forumPostReplyID
                ], ['id' => 'bearcms-send-new-forum-post-reply-notification']);
            }
        }

        $eventDetails = new \BearCMS\Internal\AddForumPostReplyEventDetails($forumPostID, $forumPostReplyID);
        $app->bearCMS->dispatchEvent('internalAddForumPostReply', $eventDetails);
    }

    /**
     * 
     * @param string $forumPostID
     * @param string $replyID
     * @param string $status
     * @return void
     */
    static function setStatus(string $forumPostID, string $replyID, string $status): void
    {
        $app = App::get();
        $dataKey = 'bearcms/forums/posts/post/' . md5($forumPostID) . '.json';
        $data = $app->data->getValue($dataKey);
        $hasChange = false;
        if ($data !== null) {
            $forumPostData = json_decode($data, true);
            if (is_array($forumPostData['replies']) && isset($forumPostData['replies'])) {
                foreach ($forumPostData['replies'] as $i => $reply) {
                    if (isset($reply['id']) && $reply['id'] === $replyID) {
                        if (isset($reply['status']) && $reply['status'] === $status) {
                            break;
                        }
                        $reply['status'] = $status;
                        $forumPostData['replies'][$i] = $reply;
                        $hasChange = true;
                        break;
                    }
                }
            }
        }
        if ($hasChange) {
            $forumPostData['lastChangeTime'] = time();
            $app->data->set($app->data->make($dataKey, json_encode($forumPostData)));
        }
    }

    /**
     * 
     * @param string $forumPostID
     * @param string $replyID
     * @return void
     */
    static function deleteReplyForever(string $forumPostID, string $replyID): void
    {
        $app = App::get();
        $dataKey = 'bearcms/forums/posts/post/' . md5($forumPostID) . '.json';
        $data = $app->data->getValue($dataKey);
        $hasChange = false;
        if ($data !== null) {
            $forumPostData = json_decode($data, true);
            if (is_array($forumPostData['replies']) && isset($forumPostData['replies'])) {
                foreach ($forumPostData['replies'] as $i => $reply) {
                    if (isset($reply['id']) && $reply['id'] === $replyID) {
                        unset($forumPostData['replies'][$i]);
                        $hasChange = true;
                        break;
                    }
                }
            }
        }
        if ($hasChange) {
            $forumPostData['lastChangeTime'] = time();
            $forumPostData['replies'] = array_values($forumPostData['replies']);
            $app->data->set($app->data->make($dataKey, json_encode($forumPostData)));
        }
    }
}
