<?php

/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

use BearFramework\App;

$app = App::get();
$context = $app->contexts->get(__DIR__);

$outputType = (string) $component->getAttribute('output-type');
$outputType = isset($outputType[0]) ? $outputType : 'full-html';
$isFullHtmlOutputType = $outputType === 'full-html';

$count = strlen((string)$component->count) > 0 ? (int) $component->count : 5;
if ($count < 1) {
    $count = 1;
}
$showRepliesCount = strlen((string)$component->showRepliesCount) > 0 ? (int) $component->showRepliesCount > 0 : true;
$categoryID = $component->categoryID;

$forumPosts = new \BearCMS\Internal\Data\Models\ForumPosts();
$posts = $forumPosts->getList()
    ->filterBy('categoryID', $categoryID)
    ->filter(function ($forumPost) use ($app) {
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
    $postUrl = $app->urls->get(BearCMS\Internal\ForumsData::$forumPagesPathPrefix . \BearCMS\Internal\Utilities::getSlug($post->id, $post->title) . '/');
    $repliesCount = $post->replies->count();
    echo '<div' . ($isFullHtmlOutputType ? ' class="bearcms-forum-posts-post"' : '') . '>';
    $statusText = '';
    if ($post->status === 'pendingApproval') {
        $statusText = ' (' . __('bearcms.forumPosts.pending approval') . ')';
    }
    echo '<a' . ($isFullHtmlOutputType ? ' class="bearcms-forum-posts-post-title"' : '') . ' href="' . htmlentities($postUrl) . '">' . htmlspecialchars($post->title) . $statusText . '</a>';
    if ($showRepliesCount) {
        echo '<div' . ($isFullHtmlOutputType ? ' class="bearcms-forum-posts-post-replies-count"' : '') . '>' . ($repliesCount === 1 ? __('bearcms.forumPosts.1 reply') : sprintf(__('bearcms.forumPosts.%s replies'), $repliesCount)) . '</div>';
    }
    echo '</div>';
    $counter++;
    if ($counter >= $count) {
        break;
    }
}
if ($isFullHtmlOutputType && $count < $posts->count()) {
    $component = '<component src="file:' . $context->dir . '/components/bearcmsForumPostsElement/forumPostsList.php" count="' . htmlentities($count + 10) . '" categoryID="' . htmlentities((string)$categoryID) . '" />';
    $loadMoreData = [
        'serverData' => \BearCMS\Internal\TempClientData::set(['componentHTML' => $component])
    ];
    $onClick = 'bearCMS.forumPostsElement.loadMore(this,' . json_encode($loadMoreData) . ');';
    echo '<div class="bearcms-forum-posts-show-more-button-container">';
    echo '<a class="bearcms-forum-posts-show-more-button" href="javascript:void(0);" onclick="' . htmlentities($onClick) . '">' . __('bearcms.forumPosts.Show more') . '</a>';
    echo '</div>';
}
echo '</div>';
