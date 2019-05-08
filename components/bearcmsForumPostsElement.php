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

$content = '<div class="bearcms-forum-posts-element">';
$content .= '<component src="file:' . $context->dir . '/components/bearcmsForumPostsElement/forumPostsList.php" count="' . htmlentities($count) . '" categoryID="' . htmlentities($categoryID) . '" />';

$newPostUrl = $app->request->base . '/f/' . $categoryID . '/';
$content .= '<div class="bearcms-forum-posts-new-post-button-container">';
$newPostData = [
    'serverData' => \BearCMS\Internal\TempClientData::set(['categoryID' => $categoryID])
];
$onClick = 'bearCMS.forumPostsElement.openNewPost(' . json_encode($newPostData) . ');';
$content .= '<a class="bearcms-forum-posts-new-post-button" href="javascript:void(0);" onclick="' . htmlentities($onClick) . '">' . __('bearcms.forumPosts.New post') . '</a>';
$content .= '</div>';
$content .= '</div>';
?><html>
    <head>
        <link rel="client-shortcuts-embed" name="-bearcms-forums-element">
        <style>
            .bearcms-forum-posts-element{word-wrap:break-word;}
            .bearcms-forum-posts-post-replies-count{float:right;}
        </style>
    </head>
    <body><?= $content ?></body>
</html>