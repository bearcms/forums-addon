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

$content = '<div' . ($isFullHtmlOutputType ? ' class="bearcms-forum-posts-element"' : '') . '>';

$content .= '<component src="file:' . $context->dir . '/components/bearcmsForumPostsElement/forumPostsList.php" count="' . htmlentities($count) . '" categoryID="' . htmlentities((string)$categoryID) . '" showRepliesCount="' . (int) $showRepliesCount . '" output-type="' . $outputType . '"/>';

if ($isFullHtmlOutputType) {
    $content .= '<div class="bearcms-forum-posts-new-post-button-container">';
    $newPostData = [
        'serverData' => \BearCMS\Internal\TempClientData::set(['categoryID' => $categoryID])
    ];
    $onClick = 'bearCMS.forumPostsElement.openNewPost(' . json_encode($newPostData) . ');';
    $content .= '<a class="bearcms-forum-posts-new-post-button" href="javascript:void(0);" onclick="' . htmlentities($onClick) . '">' . __('bearcms.forumPosts.New post') . '</a>';
    $content .= '</div>';
}

$content .= '</div>';

echo '<html>';
echo '<head>';
if ($isFullHtmlOutputType) {
    echo '<link rel="client-packages-embed" name="-bearcms-forums-element">';
    echo '<style>';
    echo '.bearcms-forum-posts-element{word-wrap:break-word;}';
    echo '.bearcms-forum-posts-post-replies-count{float:right;}';
    echo '</style>';
}
echo '</head>';
echo '<body>';
echo $content;
echo '</body>';
echo '</html>';
