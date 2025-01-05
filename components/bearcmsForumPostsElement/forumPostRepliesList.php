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
$forumPostID = (string)$component->forumPostID;
$elementID = 'frl' . md5($forumPostID);
?><html>
    <head>
        <link rel="client-packages-embed" name="users">
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

            $urlsToHTML = function($text) {
                $letters = 'абвгдежзийклмнопрстуфхчцшщьъюяАБВГДЕЖЗИЙКЛМНОПРСТУФХЧЦШЩЬЪЮЯ';
                $exp = '/(http|https|ftp|ftps)\:\/\/[' . $letters . 'a-zA-Z0-9\-\.]+\.[' . $letters . 'a-zA-Z]+[^\s]*/';
                $matches = null;
                preg_match_all($exp, $text, $matches);
                if (empty($matches[0])) {
                    return $text;
                }
                $parts = [];
                foreach ($matches[0] as $i => $url) {
                    $matches[0][$i] = rtrim($url, '.,?!');
                }
                $tempText = $text;
                foreach ($matches[0] as $url) {
                    $temp = explode($url, $tempText, 2);
                    $parts[] = $temp[0];
                    $tempText = $temp[1];
                }
                $parts[] = $temp[1];
                $newTextParts = [];
                foreach ($parts as $i => $part) {
                    $newTextParts[] = $part;
                    if (isset($matches[0][$i])) {
                        $newTextParts[] = '<a href="' . htmlentities($matches[0][$i]) . '" rel="nofollow noreferrer noopener">' . $matches[0][$i] . '</a>';
                    }
                }
                return implode('', $newTextParts);
            };

            $renderItem = function($reply) use ($app, $urlsToHTML): void {
                $statusText = '';
                if ($reply->status === 'pendingApproval') {
                    $statusText = __('bearcms.forumPosts.pending approval') . ', ';
                }
                $author = $reply->author;
                $profile = \BearCMS\Internal\PublicProfile::getFromAuthor($author);

                $onClick = 'clientPackages.get("users").then(function(users){users.openPreview("' . $author['provider'] . '","' . $author['id'] . '");});';
                $linkAttributes = ' title="' . htmlentities($profile->name) . '" href="javascript:void(0);" onclick="' . htmlentities($onClick) . '"';
                echo '<div class="bearcms-forum-post-page-reply">';
                echo '<a class="bearcms-forum-post-page-reply-author-image"' . $linkAttributes . (strlen((string)$profile->imageSmall) > 0 ? ' style="background-image:url(' . htmlentities($profile->imageSmall) . ');background-size:cover;"' : ' style="background-color:rgba(0,0,0,0.2);"') . '></a>';
                echo '<a class="bearcms-forum-post-page-reply-author-name"' . $linkAttributes . '>' . htmlspecialchars($profile->name) . '</a> <span class="bearcms-forum-post-page-reply-date">' . $statusText . $app->localization->formatDate($reply->createdTime, ['timeAgo']) . '</span>';
                echo '<div class="bearcms-forum-post-page-reply-text">' . nl2br($urlsToHTML(htmlspecialchars($reply->text))) . '</div>';
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
