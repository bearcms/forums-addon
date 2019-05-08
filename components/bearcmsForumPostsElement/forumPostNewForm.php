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
        $this->throwError('Please, login!');
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

    $slug = \BearCMS\Internal\Utilities::getSlug($forumPostID, $title);
    return [
        'success' => 1,
        'redirectUrl' => $app->urls->get('/f/' . $slug . '/')
    ];
};
?><html>
    <head>
        <style>
            .bearcms-forums-new-post-form .ivopetkov-form-elements-textbox-element-input, .bearcms-forums-new-post-form .ivopetkov-form-elements-textarea-element-textarea{
                width:calc(100vw - 50px);
                max-width: 600px;
                font-size:15px;
                padding:13px 15px;
                font-family:Arial,Helvetica,sans-serif;
                background-color:#eee;
                border-radius:2px;
                color:#000;
                box-sizing: border-box;
                display:block;
                margin-bottom: 21px;
                border:0;
            }
            .bearcms-forums-new-post-form .ivopetkov-form-elements-textarea-element-textarea{
                height:100px;
            }
            .bearcms-forums-new-post-form .ivopetkov-form-elements-element-label{
                font-family:Arial,Helvetica,sans-serif;
                font-size:15px;
                color:#fff;
                padding-bottom: 9px;
                cursor: default;
                display:block;
            }
            .bearcms-forums-new-post-form .ivopetkov-form-elements-submit-button-element-button{
                box-sizing: border-box;
                width:calc(100vw - 50px);
                max-width: 600px;
                font-family:Arial,Helvetica,sans-serif;
                background-color:#fff;
                font-size:15px;
                border-radius:2px;
                padding:13px 15px;
                color:#000;
                margin-top:25px;
                display:block;
                text-align:center;
            }
            .bearcms-forums-new-post-form .ivopetkov-form-elements-submit-button-element-button[disabled]{
                background-color:#ddd;
            }
            .bearcms-forums-new-post-form .ivopetkov-form-elements-submit-button-element-button:not([disabled]):hover{
                background-color:#f5f5f5;
            }
            .bearcms-forums-new-post-form .ivopetkov-form-elements-submit-button-element-button:not([disabled]):active{
                background-color:#eeeeee;
            }
        </style>
    </head>
    <body><?php
        $onSubmitSuccess = 'window.location=event.result.redirectUrl;';
        echo '<form class="bearcms-forums-new-post-form" onsubmitsuccess="' . htmlentities($onSubmitSuccess) . '">';
        echo '<form-element-textbox name="fptitle" label="' . htmlentities(__('bearcms.forumPosts.Title')) . '" />';
        echo '<form-element-textarea name="fptext" label="' . htmlentities(__('bearcms.forumPosts.Content')) . '" />';
        echo '<form-element-submit-button text="' . htmlentities(__('bearcms.forumPosts.Post')) . '"  waitingText="' . htmlentities(__('bearcms.forumPosts.Posting ...')) . '" />';
        echo '</form>';
        ?></body>
</html>