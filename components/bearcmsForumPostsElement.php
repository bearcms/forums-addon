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
$content .= '<script id="bearcms-bearframework-addon-script-9" src="' . htmlentities($context->assets->getURL('assets/forumPostsElement.min.js', ['cacheMaxAge' => 999999999, 'version' => 1])) . '" async></script>';
$content .= '<script id="bearcms-bearframework-addon-script-4" src="' . htmlentities($context->assets->getURL('assets/HTML5DOMDocument.min.js', ['cacheMaxAge' => 999999999, 'version' => 1])) . '" async></script>';

$newPostUrl = $app->request->base . '/f/' . $categoryID . '/';
$content .= '<div class="bearcms-forum-posts-new-post-button-container">';
$content .= '<a class="bearcms-forum-posts-new-post-button" href="' . htmlentities($newPostUrl) . '">' . __('bearcms.forumPosts.New post') . '</a>';
$content .= '</div>';
$content .= '</div>';
?><html>
    <head><style>
            .bearcms-forum-posts-element{word-wrap:break-word;}
            .bearcms-forum-posts-post-replies-count{float:right;}
        </style></head>
    <body><?= $content ?></body>
</html>