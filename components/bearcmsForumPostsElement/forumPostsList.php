<?php

/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

use BearFramework\App;

$app = App::get();
$context = $app->contexts->get(__FILE__);

$count = strlen($component->count) > 0 ? (int) $component->count : 5;
if ($count < 1) {
    $count = 1;
}
$categoryID = $component->categoryID;

$forumPosts = new \BearCMS\Internal\Data\Models\ForumPosts();
$posts = $forumPosts->getList()
        ->filterBy('categoryID', $categoryID)
        ->filter(function($forumPost) use ($app) {
            if ($forumPost->status === 'approved') {
                return true;
            }
            if ($forumPost->status === 'pendingApproval') {
                if ($app->currentUser->exists()) {
                    return $app->currentUser->provider === $forumPost->author['provider'] && $app->currentUser->id === $forumPost->author['id'];
                }
            }
            return false;
        })
        ->sortBy('createdTime', 'desc');
$counter = 0;
echo '<div>';
foreach ($posts as $post) {
    $postUrl = $app->request->base . '/f/' . $post->id . '/' . $post->id . '/';
    $repliesCount = $post->replies->count();
    echo '<div class="bearcms-forum-posts-post">';
    $statusText = '';
    if ($post->status === 'pendingApproval') {
        $statusText = ' (' . __('bearcms.forumPosts.pending approval') . ')';
    }
    echo '<a class="bearcms-forum-posts-post-title" href="' . htmlentities($postUrl) . '">' . htmlspecialchars($post->title) . $statusText . '</a>';
    echo '<div class="bearcms-forum-posts-post-replies-count">' . ($repliesCount === 1 ? __('bearcms.forumPosts.1 reply') : sprintf(__('bearcms.forumPosts.%s replies'), $repliesCount)) . '</div>';
    echo '</div>';
    $counter++;
    if ($counter >= $count) {
        break;
    }
}
if ($count < $posts->count()) {
    $component = '<component src="file:' . $context->dir . '/components/bearcmsForumPostsElement/forumPostsList.php" count="' . htmlentities($count + 10) . '" categoryID="' . htmlentities($categoryID) . '" />';
    $loadMoreData = [
        'serverData' => \BearCMS\Internal\TempClientData::set(['componentHTML' => $component])
    ];
    $onClick = 'bearCMS.forumPostsElement.loadMore(event,' . json_encode($loadMoreData) . ');';
    echo '<div class="bearcms-forum-posts-show-more-button-container">';
    echo '<a class="bearcms-forum-posts-show-more-button" href="javascript:void(0);" onclick="' . htmlentities($onClick) . '">' . __('bearcms.forumPosts.Show more') . '</a>';
    echo '</div>';
}
echo '</div>';
