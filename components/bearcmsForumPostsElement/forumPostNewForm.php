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

$form->constraints->setRequired('fptitle');
$form->constraints->setRequired('fptext');

$form->onSubmit = function ($values) use ($component, $app) {

    $categoryID = $component->categoryID;

    if (!$app->currentUser->exists()) {
        $this->throwError('Please, login!');
    }

    $author = [
        'type' => 'user',
        'provider' => $app->currentUser->provider,
        'id' => $app->currentUser->id
    ];
    $title = trim($values['fptitle']);
    $text = trim($values['fptext']);
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

    $slug = \BearCMS\Internal\ForumsUtilities::getSlug($forumPostID, $title);
    return [
        'success' => 1,
        'redirectURL' => $app->urls->get(BearCMS\Internal\ForumsData::$forumPagesPathPrefix . $slug . '/')
    ];
};
echo '<html>';
echo '<body>';

$onSubmitSuccess = 'window.location=event.result.redirectURL;';
echo '<form class="bearcms-forums-new-post-form" onsubmitsuccess="' . htmlentities($onSubmitSuccess) . '">';
echo '<form-element-textbox name="fptitle" label="' . htmlentities(__('bearcms.forumPosts.Title')) . '" />';
echo '<form-element-textarea name="fptext" label="' . htmlentities(__('bearcms.forumPosts.Content')) . '" />';
echo '<form-element-submit-button text="' . htmlentities(__('bearcms.forumPosts.Post')) . '" waitingText="' . htmlentities(__('bearcms.forumPosts.Posting ...')) . '" />';
echo '</form>';

echo '</body>';
echo '</html>';
