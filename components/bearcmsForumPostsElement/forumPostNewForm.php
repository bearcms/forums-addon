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

$form->constraints->setRequired('fptitle');
$form->constraints->setRequired('fptext');

$form->onSubmit = function($values) use ($component, $app) {

    $categoryID = $component->categoryID;

    if (!$app->currentUser->exists()) {
        $this->throwError();
    }

    $author = [
        'type' => 'user',
        'provider' => $app->currentUser->provider,
        'id' => $app->currentUser->id
    ];
    $title = $values['fptitle'];
    $text = $values['fptext'];
    $status = 'approved';
    $cancel = false;
    $cancelMessage = '';
    if ($app->bearCMS->hasEventListeners('internalBeforeAddForumPost')) {
        $eventDetails = new \BearCMS\Internal\BeforeAddForumPostEventDetails($categoryID, $author, $title, $text, $status);
        $app->bearCMS->dispatchEvent('internalBeforeAddForumPost', $eventDetails);
        $author = $eventDetails->author;
        $title = $eventDetails->title;
        $text = $eventDetails->text;
        $status = $eventDetails->status;
        $cancel = $eventDetails->cancel;
        $cancelMessage = $eventDetails->cancelMessage;
    }
    if ($cancel) {
        $this->throwError($cancelMessage);
    }
    $forumPostID = \BearCMS\Internal\Data\Utilities\ForumPosts::add($categoryID, $author, $title, $text, $status);

    $slug = $forumPostID; //todo
    return [
        'success' => 1,
        'redirectUrl' => $app->urls->get('/f/' . $slug . '/' . $forumPostID . '/')
    ];
};
?><html>
    <head>
        <style>
            .bearcms-new-forum-post-page-title-input{display:block;}
            .bearcms-new-forum-post-page-text-input{display:block;resize:none;}
            .bearcms-new-forum-post-page-send-button{cursor:pointer;}
        </style>
    </head>
    <body><?php
        echo '<form'
        . ' onbeforesubmit="bearCMS.forumPostNewForm.onBeforeSubmitForm(event);"'
        . ' onsubmitdone="bearCMS.forumPostNewForm.onSubmitFormDone(event);"'
        . ' onrequestsent="bearCMS.forumPostNewForm.onFormRequestSent(event);"'
        . ' onresponsereceived="bearCMS.forumPostNewForm.onFormResponseReceived(event);"'
        . '>';
        echo '<label class="bearcms-new-forum-post-page-title-label">' . __('bearcms.forumPosts.Title') . '</label>';
        echo '<input type="text" name="fptitle" class="bearcms-new-forum-post-page-title-input" onfocus="bearCMS.forumPostNewForm.onFocusTitle(event);"/>';
        echo '<label class="bearcms-new-forum-post-page-text-label">' . __('bearcms.forumPosts.Content') . '</label>';
        echo '<textarea name="fptext" class="bearcms-new-forum-post-page-text-input" onfocus="bearCMS.forumPostNewForm.onFocusTextarea(event);"></textarea>';
        echo '<span onclick="this.parentNode.submit();" class="bearcms-new-forum-post-page-send-button">' . __('bearcms.forumPosts.Post') . '</span>';
        echo '<span style="display:none;" class="bearcms-new-forum-post-page-send-button bearcms-new-forum-post-page-send-button-waiting">' . __('bearcms.forumPosts.Posting ...') . '</span>';
        echo '</form>';
        echo '<script id="bearcms-bearframework-addon-script-7" src="' . htmlentities($context->assets->getURL('assets/forumPostNewForm.min.js', ['cacheMaxAge' => 999999999, 'version' => 2])) . '" async></script>';
        ?></body>
</html>