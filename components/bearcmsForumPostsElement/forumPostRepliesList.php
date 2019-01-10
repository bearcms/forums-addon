<?php
/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

use BearFramework\App;

$app = App::get();

$includePost = $component->includePost === 'true';
$forumPostID = $component->forumPostID;
$elementID = 'frl' . md5($forumPostID);
?><html>
    <head>
        <style>
            .bearcms-forum-post-page-reply{display:block;clear:both;zoom:1;word-wrap:break-word;}
            .bearcms-forum-post-page-reply:after{visibility:hidden;display:block;font-size:0;content:" ";clear:both;height:0;}
            .bearcms-forum-post-page-reply-author-image{display:inline-block;float:left;}
            .bearcms-forum-post-page-reply-date{float:right;}
        </style>
    </head>
    <body><?php
        echo '<div id="' . $elementID . '">';
        $forumPosts = new \BearCMS\Internal\Data\Models\ForumPosts();
        $forumPost = $forumPosts->get($forumPostID);
        if ($forumPost !== null) {

            $renderItem = function($reply) use ($app) {
                $statusText = '';
                if ($reply->status === 'pendingApproval') {
                    $statusText = __('bearcms.forumPosts.pending approval') . ', ';
                }
                $profile = \BearCMS\Internal\PublicProfile::getFromAuthor($reply->author);
                $linkAttributes = '';
                if (strlen($profile->url) > 0) {
                    $tagName = 'a';
                    $linkAttributes .= ' href="' . htmlentities($profile->url) . '" target="_blank" rel="nofollow noopener"';
                } else {
                    $tagName = 'span';
                    $linkAttributes .= ' href="javascript:void(0);"';
                }
                $linkAttributes .= ' title="' . htmlentities($profile->name) . '"';
                echo '<div class="bearcms-forum-post-page-reply">';
                echo '<' . $tagName . ' class="bearcms-forum-post-page-reply-author-image"' . $linkAttributes . (strlen($profile->imageSmall) > 0 ? ' style="background-image:url(' . htmlentities($profile->imageSmall) . ');background-size:cover;"' : ' style="background-color:rgba(0,0,0,0.2);"') . '></' . $tagName . '>';
                echo '<' . $tagName . ' class="bearcms-forum-post-page-reply-author-name"' . $linkAttributes . '>' . htmlspecialchars($profile->name) . '</' . $tagName . '> <span class="bearcms-forum-post-page-reply-date">' . $statusText . $app->localization->formatDate($reply->createdTime, ['timeAgo']) . '</span>';
                echo '<div class="bearcms-forum-post-page-reply-text">' . nl2br(htmlspecialchars($reply->text)) . '</div>';
                echo '</div>';
            };

            if ($includePost) {
                $renderItem($forumPost);
            }
            foreach ($forumPost->replies as $reply) {
                $render = false;
                if ($reply->status === 'approved') {
                    $render = true;
                } elseif ($reply->status === 'pendingApproval') {
                    if ($app->currentUser->exists()) {
                        $render = $app->currentUser->provider === $reply->author['provider'] && $app->currentUser->id === $reply->author['id'];
                    }
                }
                if ($render) {
                    $renderItem($reply);
                }
            }
        }
        echo '</div>';
        ?></body>
</html>
