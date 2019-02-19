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

$form->constraints->setRequired('fprtext');

$form->onSubmit = function($values) use ($component, $app, $context) {
    $contextData = json_decode($values['fprcontext'], true);
    if (is_array($contextData) && isset($contextData['listElementID'])) {
        $listElementID = (string) $contextData['listElementID'];
    } else {
        $this->throwError();
    }
    if (!$app->currentUser->exists()) {
        $this->throwError();
    }

    $forumPostID = $component->forumPostID;
    $author = [
        'type' => 'user',
        'provider' => $app->currentUser->provider,
        'id' => $app->currentUser->id
    ];
    $text = $values['fprtext'];
    $status = 'approved';
    $cancel = false;
    $cancelMessage = '';

    if ($app->bearCMS->hasEventListeners('internalBeforeAddForumPostReply')) {
        $eventDetails = new \BearCMS\Internal\BeforeAddForumPostReplyEventDetails($forumPostID, $author, $text, $status);
        $app->bearCMS->dispatchEvent('internalBeforeAddForumPostReply', $eventDetails);
        $author = $eventDetails->author;
        $text = $eventDetails->text;
        $status = $eventDetails->status;
        $cancel = $eventDetails->cancel;
        $cancelMessage = $eventDetails->cancelMessage;
    }
    if ($cancel) {
        $this->throwError($cancelMessage);
    }
    \BearCMS\Internal\Data\Utilities\ForumPostsReplies::add($forumPostID, $author, $text, $status);

    $listContent = $app->components->process('<component src="file:' . $context->dir . '/components/bearcmsForumPostsElement/forumPostRepliesList.php" includePost="true" forumPostID="' . htmlentities($forumPostID) . '" />');
    return [
        'listElementID' => $listElementID,
        'listContent' => $listContent,
        'success' => 1
    ];
};
?><html>
    <head>
        <style>
            .bearcms-forum-post-page-text-input{display:block;resize:none;}
            .bearcms-forum-post-page-send-button{cursor:pointer;}
        </style>
    </head>
    <body><?php
        echo '<form'
        . ' onbeforesubmit="bearCMS.forumPostReplyForm.onBeforeSubmitForm(event);"'
        . ' onsubmitdone="bearCMS.forumPostReplyForm.onSubmitFormDone(event);"'
        . ' onrequestsent="bearCMS.forumPostReplyForm.onFormRequestSent(event);"'
        . ' onresponsereceived="bearCMS.forumPostReplyForm.onFormResponseReceived(event);"'
        . '>';
        echo '<input type="hidden" name="fprcontext"/>';
        echo '<textarea placeholder="' . __('bearcms.forumPosts.Your reply') . '" name="fprtext" class="bearcms-forum-post-page-text-input" onfocus="bearCMS.forumPostReplyForm.onFocusTextarea(event);"></textarea>';
        echo '<span onclick="this.parentNode.submit();" class="bearcms-forum-post-page-send-button">' . __('bearcms.forumPosts.Send') . '</span>';
        echo '<span style="display:none;" class="bearcms-forum-post-page-send-button bearcms-forum-post-page-send-button-waiting">' . __('bearcms.forumPosts.Sending ...') . '</span>';
        echo '</form>';
        echo '<script id="bearcms-bearframework-addon-script-8" src="' . htmlentities($context->assets->getURL('assets/forumPostReplyForm.min.js', ['cacheMaxAge' => 999999999, 'version' => 2])) . '" async></script>';
        ?></body>
</html>