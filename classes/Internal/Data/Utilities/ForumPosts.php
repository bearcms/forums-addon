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
class ForumPosts
{

    /**
     * 
     * @param string $categoryID
     * @param array $author
     * @param string $title
     * @param string $text
     * @param string $status
     * @return string
     */
    static function add(string $categoryID, array $author, string $title, string $text, string $status): string
    {
        $app = App::get();
        $id = md5(uniqid());
        $currentTime = time();
        $data = [
            'id' => $id,
            'status' => $status,
            'author' => $author,
            'title' => $title,
            'text' => $text,
            'categoryID' => $categoryID,
            'createdTime' => $currentTime,
            'lastChangeTime' => $currentTime
        ];

        $dataKey = self::getDataKey($id);
        $app->data->set($app->data->make($dataKey, json_encode($data)));

        if (\BearCMS\Internal\Config::hasFeature('NOTIFICATIONS')) {
            if (!$app->tasks->exists('bearcms-send-new-forum-post-notification')) {
                $app->tasks->add('bearcms-send-new-forum-post-notification', [
                    'categoryID' => $categoryID,
                    'forumPostID' => $id
                ], ['id' => 'bearcms-send-new-forum-post-notification']);
            }
        }

        return $id;
    }

    /**
     * 
     * @param string $forumPostID
     * @param string $status
     * @return void
     */
    static function setStatus(string $forumPostID, string $status): void
    {
        $app = App::get();
        $dataKey = self::getDataKey($forumPostID);
        $data = $app->data->getValue($dataKey);
        $hasChange = false;
        if ($data !== null) {
            $forumPostData = json_decode($data, true);
            $forumPostData['status'] = $status;
            $hasChange = true;
        }
        if ($hasChange) {
            $app->data->set($app->data->make($dataKey, json_encode($forumPostData)));
        }
    }

    static function getDataKey(string $id)
    {
        return 'bearcms/forums/posts/post/' . md5($id) . '.json';
    }

    static function getLastModifiedDetails(string $forumPostID)
    {
        $details = ['dates' => [], 'dataKeys' => []];
        $details['dataKeys'][] = self::getDataKey($forumPostID);
        $forumPosts = new \BearCMS\Internal\Data\Models\ForumPosts();
        $forumPost = $forumPosts->get($forumPostID);
        if ($forumPost !== null) {
            $details['dates'][] = $forumPost->lastChangeTime;
        }
        return $details;
    }
}
