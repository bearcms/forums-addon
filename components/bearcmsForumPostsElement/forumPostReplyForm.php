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
    $text = trim($values['fprtext']);
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
        <link rel="client-packages-embed" name="-bearcms-forums-element-reply">
        <style>
            .bearcms-forum-post-page-text-input{display:block;resize:none;}
            .bearcms-forum-post-page-send-button{cursor:pointer;}
        </style>
    </head>
    <body><?php
        $formID = 'bfrmfrm' . uniqid();
        echo '<form id="' . $formID . '">';
        echo '<form-element-hidden name="fprcontext" />';
        echo '<form-element-textarea readonly="true" placeholder="' . __('bearcms.forumPosts.Your reply') . '" name="fprtext" class="bearcms-forum-post-page-text-input" />';
        echo '<form-element-submit-button text="' . __('bearcms.forumPosts.Send') . '" waitingText="' . __('bearcms.forumPosts.Sending ...') . '" style="display:none;" class="bearcms-forum-post-page-send-button" waitingClass="bearcms-forum-post-page-send-button bearcms-forum-post-page-send-button-waiting"/>';
        echo '</form>';
        echo '<script>bearCMS.forumPostReply.initializeForm("' . $formID . '",' . (int) $app->currentUser->exists() . ');</script>';
        ?></body>
</html>