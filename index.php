<?php

/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

use BearFramework\App;

$app = App::get();

$app->bearCMS->addons
        ->announce('bearcms/forums-addon', function(\BearCMS\Addons\Addon $addon) use ($app) {
            $addon->initialize = function() use ($app) {
                $context = $app->context->get(__FILE__);

                $context->assets->addDir('assets');

                $app->localization
                ->addDictionary('en', function() use ($context) {
                    return include $context->dir . '/locales/en.php';
                })
                ->addDictionary('bg', function() use ($context) {
                    return include $context->dir . '/locales/bg.php';
                })
                ->addDictionary('ru', function() use ($context) {
                    return include $context->dir . '/locales/ru.php';
                });

                $context->classes
                ->add('BearCMS\Internal\Data\Models\ForumCategories', 'classes/Internal/Data/Models/ForumCategories.php')
                ->add('BearCMS\Internal\Data\Models\ForumCategory', 'classes/Internal/Data/Models/ForumCategory.php')
                ->add('BearCMS\Internal\Data\Models\ForumPost', 'classes/Internal/Data/Models/ForumPost.php')
                ->add('BearCMS\Internal\Data\Models\ForumPosts', 'classes/Internal/Data/Models/ForumPosts.php')
                ->add('BearCMS\Internal\Data\Models\ForumPostReply', 'classes/Internal/Data/Models/ForumPostReply.php')
                ->add('BearCMS\Internal\Data\Models\ForumPostsReplies', 'classes/Internal/Data/Models/ForumPostsReplies.php')
                ->add('BearCMS\Internal\Data\Utilities\ForumPosts', 'classes/Internal/Data/Utilities/ForumPosts.php')
                ->add('BearCMS\Internal\Data\Utilities\ForumPostsReplies', 'classes/Internal/Data/Utilities/ForumPostsReplies.php');

                \BearCMS\Internal\ElementsTypes::add('forumPosts', [
                    'componentSrc' => 'bearcms-forum-posts-element',
                    'componentFilename' => $context->dir . '/components/bearcmsForumPostsElement.php',
                    'fields' => [
                        [
                            'id' => 'categoryID',
                            'type' => 'textbox'
                        ],
                        [
                            'id' => 'count',
                            'type' => 'number'
                        ]
                    ]
                ]);

                $app->routes
                ->add('/f/?/', [
                    [$app->bearCMS, 'disabledCheck'],
                    function() use ($app, $context) {
                        $forumCategoryID = $app->request->path->getSegment(1);
                        $forumCategories = new \BearCMS\Internal\Data\Models\ForumCategories();
                        $forumCategory = $forumCategories->get($forumCategoryID);
                        if ($forumCategory !== null) {
                            $content = '<html>';
                            $content .= '<head>';
                            $content .= '<title>' . sprintf(__('bearcms.New post in %s'), htmlspecialchars($forumCategory->name)) . '</title>';
                            $content .= '</head>';
                            $content .= '<body>';
                            $content .= '<div class="bearcms-forum-post-page-title-container"><h1 class="bearcms-forum-post-page-title">' . sprintf(__('bearcms.New post in %s'), htmlspecialchars($forumCategory->name)) . '</h1></div>';
                            $content .= '<div class="bearcms-forum-post-page-content">';
                            $content .= '<component src="form" filename="' . $context->dir . '/components/bearcmsForumPostsElement/forumPostNewForm.php" categoryID="' . htmlentities($forumCategoryID) . '" />';
                            $content .= '</div>';
                            $content .= '</body>';
                            $content .= '</html>';

                            $app->hooks->execute('bearCMSForumCategoryPageContentCreated', $content, $forumCategoryID);

                            $response = new App\Response\HTML($app->components->process($content));
                            $response->headers->set($response->headers->make('X-Robots-Tag', 'noindex'));
                            $app->bearCMS->apply($response);
                            return $response;
                        }
                    }
                ])
                ->add('/f/?/?/', [
                    [$app->bearCMS, 'disabledCheck'],
                    function() use ($app, $context) {
                        $forumPostSlug = $app->request->path->getSegment(1); // todo validate
                        $forumPostID = $app->request->path->getSegment(2);
                        $forumPosts = new \BearCMS\Internal\Data\Models\ForumPosts();
                        $forumPost = $forumPosts->get($forumPostID);
                        if ($forumPost !== null) {

                            $render = false;
                            if ($forumPost->status === 'approved') {
                                $render = true;
                            } elseif ($forumPost->status === 'pendingApproval') {
                                if ($app->currentUser->exists()) {
                                    $render = $app->currentUser->provider === $forumPost->author['provider'] && $app->currentUser->id === $forumPost->author['id'];
                                }
                            }
                            if (!$render) {
                                return;
                            }

                            $content = '<html>';
                            $content .= '<head>';
                            $content .= '<title>' . htmlspecialchars($forumPost->title) . '</title>';
                            $content .= '</head>';
                            $content .= '<body>';
                            $content .= '<div class="bearcms-forum-post-page-title-container"><h1 class="bearcms-forum-post-page-title">' . htmlspecialchars($forumPost->title) . '</h1></div>';
                            //$content .= '<div class="bearcms-forum-post-page-date-container"><div class="bearcms-forum-post-page-date">' . Internal\Localization::getDate($forumPost->createdTime) . '</div></div>';
                            $content .= '<div class="bearcms-forum-post-page-content">';
                            $content .= '<component src="file:' . $context->dir . '/components/bearcmsForumPostsElement/forumPostRepliesList.php" includePost="true" forumPostID="' . htmlentities($forumPost->id) . '" />';
                            $content .= '</div>';
                            $content .= '<component src="form" filename="' . $context->dir . '/components/bearcmsForumPostsElement/forumPostReplyForm.php" forumPostID="' . htmlentities($forumPost->id) . '" />';
                            $content .= '</body>';
                            $content .= '</html>';

                            $forumPostID = $forumPost->id;
                            $app->hooks->execute('bearCMSForumPostPageContentCreated', $content, $forumPostID);

                            $response = new App\Response\HTML($app->components->process($content));
                            $app->bearCMS->apply($response);
                            return $response;
                        }
                    }
                ]);

                $app->serverRequests
                ->add('bearcms-forumposts-load-more', function($data) use ($app) {
                    if (isset($data['serverData'], $data['serverData'])) {
                        $serverData = Internal\TempClientData::get($data['serverData']);
                        if (is_array($serverData) && isset($serverData['componentHTML'])) {
                            $content = $app->components->process($serverData['componentHTML']);
                            return json_encode([
                                'content' => $content
                            ]);
                        }
                    }
                });

                \BearCMS\Internal\Themes::$elementsOptions['forumPosts'] = function($context, $idPrefix, $parentSelector) {
                    $groupForumPosts = $context->addGroup(__("bearcms.themes.options.Forum posts"));

                    $groupForumPostsPost = $groupForumPosts->addGroup(__("bearcms.themes.options.forumPosts.Post"));
                    $groupForumPostsPost->addOption($idPrefix . "ForumPostsPostCSS", "css", '', [
                        "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["selector", $parentSelector . " .bearcms-forum-posts-post"]
                        ]
                    ]);

                    $groupForumPostsPostTitle = $groupForumPostsPost->addGroup(__("bearcms.themes.options.forumPosts.Title"));
                    $groupForumPostsPostTitle->addOption($idPrefix . "ForumPostsTitleCSS", "css", '', [
                        "cssOutput" => [
                            ["selector", $parentSelector . " .bearcms-forum-posts-post-title"]
                        ]
                    ]);

                    $groupForumPostsPostRepliesCount = $groupForumPostsPost->addGroup(__("bearcms.themes.options.forumPosts.Replies count"));
                    $groupForumPostsPostRepliesCount->addOption($idPrefix . "ForumPostsRepliesCountCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", $parentSelector . " .bearcms-forum-posts-post-replies-count", "display:inline-block;float:right;"],
                            ["selector", $parentSelector . " .bearcms-forum-posts-post-replies-count"]
                        ]
                    ]);

                    $groupForumPostsShowMoreButton = $groupForumPosts->addGroup(__("bearcms.themes.options.forumPosts.Show more button"));
                    $groupForumPostsShowMoreButton->addOption($idPrefix . "ForumPostsShowMoreButtonCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", $parentSelector . " .bearcms-forum-posts-show-more-button", "display:inline-block;"],
                            ["selector", $parentSelector . " .bearcms-forum-posts-show-more-button"]
                        ]
                    ]);

                    $groupForumPostsShowMoreButtonContainer = $groupForumPostsShowMoreButton->addGroup(__("bearcms.themes.options.forumPosts.Container"));
                    $groupForumPostsShowMoreButtonContainer->addOption($idPrefix . "ForumPostsShowMoreButtonContainerCSS", "css", '', [
                        "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["selector", $parentSelector . " .bearcms-forum-posts-show-more-button-container"]
                        ]
                    ]);

                    $groupForumPostsNewPostButton = $groupForumPosts->addGroup(__("bearcms.themes.options.forumPosts.New post button"));
                    $groupForumPostsNewPostButton->addOption($idPrefix . "ForumPostsNewPostButtonCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", $parentSelector . " .bearcms-forum-posts-new-post-button", "display:inline-block;"],
                            ["selector", $parentSelector . " .bearcms-forum-posts-new-post-button"]
                        ]
                    ]);

                    $groupForumPostsNewPostButtonContainer = $groupForumPostsNewPostButton->addGroup(__("bearcms.themes.options.forumPosts.Container"));
                    $groupForumPostsNewPostButtonContainer->addOption($idPrefix . "ForumPostsShowMoreButtonContainerCSS", "css", '', [
                        "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["selector", $parentSelector . " .bearcms-forum-posts-new-post-button-container"]
                        ]
                    ]);
                };

                \BearCMS\Internal\Themes::$pagesOptions['forums'] = function($context) {
                    $groupNewForumPostPage = $context->addGroup(__("bearcms.themes.options.New forum post page"));

                    $groupNewForumPostPageTitleLabel = $groupNewForumPostPage->addGroup(__("bearcms.themes.options.newForumPostPage.Title label"));
                    $groupNewForumPostPageTitleLabel->addOption("newForumPostPageTitleLabelCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-new-forum-post-page-title-label", "display:block;"],
                            ["selector", ".bearcms-new-forum-post-page-title-label"]
                        ]
                    ]);

                    $groupNewForumPostPageTitleInput = $groupNewForumPostPage->addGroup(__("bearcms.themes.options.newForumPostPage.Title input"));
                    $groupNewForumPostPageTitleInput->addOption("newForumPostPageTitleInputCSS", "css", '', [
                        "cssTypes" => ["cssText", "cssTextShadow", "cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["rule", ".bearcms-new-forum-post-page-title", "box-sizing:border-box;border:0;"],
                            ["selector", ".bearcms-new-forum-post-page-title"]
                        ]
                    ]);

                    $groupNewForumPostPageTextLabel = $groupNewForumPostPage->addGroup(__("bearcms.themes.options.newForumPostPage.Text label"));
                    $groupNewForumPostPageTextLabel->addOption("newForumPostPageTextLabelCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-new-forum-post-page-text-label", "display:block;"],
                            ["selector", ".bearcms-new-forum-post-page-text-label"]
                        ]
                    ]);

                    $groupNewForumPostPageTextInput = $groupNewForumPostPage->addGroup(__("bearcms.themes.options.newForumPostPage.Text input"));
                    $groupNewForumPostPageTextInput->addOption("newForumPostPageTextInputCSS", "css", '', [
                        "cssTypes" => ["cssText", "cssTextShadow", "cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["rule", ".bearcms-new-forum-post-page-text", "box-sizing:border-box;border:0;"],
                            ["selector", ".bearcms-new-forum-post-page-text"]
                        ]
                    ]);

                    $groupNewForumPostPageSendButton = $groupNewForumPostPage->addGroup(__("bearcms.themes.options.newForumPostPage.Send button"));
                    $groupNewForumPostPageSendButton->addOption("newForumPostPageSendButtonCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-new-forum-post-page-send-button", "display:inline-block;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;"],
                            ["selector", ".bearcms-new-forum-post-page-send-button"]
                        ]
                    ]);

                    $groupNewForumPostPageSendButtonWaiting = $groupNewForumPostPageSendButton->addGroup(__("bearcms.themes.options.newForumPostPage.Send button waiting"));
                    $groupNewForumPostPageSendButtonWaiting->addOption("newForumPostPageSendButtonWaitingCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-new-forum-post-page-send-button-waiting", "display:inline-block;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;"],
                            ["selector", ".bearcms-new-forum-post-page-send-button-waiting"]
                        ]
                    ]);

                    $groupForumPostPage = $context->addGroup(__("bearcms.themes.options.Forum post page"));

                    $groupForumPostPageTitle = $groupForumPostPage->addGroup(__("bearcms.themes.options.forumPostPage.Title"));
                    $groupForumPostPageTitle->addOption("forumPostPageTitleCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-forum-post-page-title", "font-weight:normal;"],
                            ["selector", ".bearcms-forum-post-page-title"]
                        ]
                    ]);

                    $groupForumPostPageTitleContainer = $groupForumPostPageTitle->addGroup(__("bearcms.themes.options.forumPostPage.Container"));
                    $groupForumPostPageTitleContainer->addOption("forumPostPageTitleContainerCSS", "css", '', [
                        "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["selector", ".bearcms-forum-post-page-title-container"]
                        ]
                    ]);

                    $groupForumPostPageDate = $groupForumPostPage->addGroup(__("bearcms.themes.options.forumPostPage.Date"));
                    $groupForumPostPageDate->addOption("forumPostPageDateCSS", "css", '', [
                        "cssOutput" => [
                            ["selector", ".bearcms-forum-post-page-date"]
                        ]
                    ]);

                    $groupForumPostPageDateContainer = $groupForumPostPageDate->addGroup(__("bearcms.themes.options.forumPostPage.Container"));
                    $groupForumPostPageDateContainer->addOption("forumPostPageDateContainerCSS", "css", '', [
                        "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["selector", ".bearcms-forum-post-page-date-container"]
                        ]
                    ]);

                    $groupForumPostPageContent = $groupForumPostPage->addGroup(__("bearcms.themes.options.forumPostPage.Content"));
                    $groupForumPostPageContent->addOption("forumPostPageContentCSS", "css", '', [
                        "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["selector", ".bearcms-forum-post-page-content"]
                        ]
                    ]);

                    $groupForumPostPageReply = $groupForumPostPage->addGroup(__("bearcms.themes.options.forumPostPage.Reply"));
                    $groupForumPostPageReply->addOption("forumPostPageReplyCSS", "css", '', [
                        "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["rule", ".bearcms-forum-post-page-reply", "overflow:hidden;"],
                            ["selector", ".bearcms-forum-post-page-reply"]
                        ]
                    ]);

                    $groupForumPostPageReplyAuthorName = $groupForumPostPageReply->addGroup(__("bearcms.themes.options.forumPostPage.Author name"));
                    $groupForumPostPageReplyAuthorName->addOption("forumPostPageReplyAuthorNameCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-forum-post-page-reply-author-name", "display:inline-block;"],
                            ["selector", ".bearcms-forum-post-page-reply-author-name"]
                        ]
                    ]);

                    $groupForumPostPageReplyAuthorImage = $groupForumPostPageReply->addGroup(__("bearcms.themes.options.forumPostPage.Author image"));
                    $groupForumPostPageReplyAuthorImage->addOption("forumPostPageReplyAuthorImageCSS", "css", '', [
                        "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["rule", ".bearcms-forum-post-page-reply-author-image", "display:inline-block;float:left;"],
                            ["selector", ".bearcms-forum-post-page-reply-author-image"]
                        ]
                    ]);

                    $groupForumPostPageReplyDate = $groupForumPostPageReply->addGroup(__("bearcms.themes.options.forumPostPage.Date"));
                    $groupForumPostPageReplyDate->addOption("forumPostPageReplyDateCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-forum-post-page-reply-date", "display:inline-block;float:right;"],
                            ["selector", ".bearcms-forum-post-page-reply-date"]
                        ]
                    ]);

                    $groupForumPostPageReplyText = $groupForumPostPageReply->addGroup(__("bearcms.themes.options.forumPostPage.Text"));
                    $groupForumPostPageReplyText->addOption("forumPostPageReplyTextCSS", "css", '', [
                        "cssOutput" => [
                            ["selector", ".bearcms-forum-post-page-reply-text"]
                        ]
                    ]);

                    $groupForumPostPageReplyTextLinks = $groupForumPostPageReply->addGroup(__("bearcms.themes.options.forumPostPage.Text links"));
                    $groupForumPostPageReplyTextLinks->addOption("forumPostPageReplyTextLinksCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-forum-post-page-reply-text a", "display:inline-block;"],
                            ["selector", ".bearcms-forum-post-page-reply-text a"]
                        ]
                    ]);

                    $groupForumPostPageTextInput = $groupForumPostPage->addGroup(__("bearcms.themes.options.forumPostPage.Text input"));
                    $groupForumPostPageTextInput->addOption("forumPostPageTextInputCSS", "css", '', [
                        "cssTypes" => ["cssText", "cssTextShadow", "cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["rule", ".bearcms-forum-post-page-text", "box-sizing:border-box;border:0;"],
                            ["selector", ".bearcms-forum-post-page-text"]
                        ]
                    ]);

                    $groupForumPostPageSendButton = $groupForumPostPage->addGroup(__("bearcms.themes.options.forumPostPage.Send button"));
                    $groupForumPostPageSendButton->addOption("forumPostPageSendButtonCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-forum-post-page-send-button", "display:inline-block;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;"],
                            ["selector", ".bearcms-forum-post-page-send-button"]
                        ]
                    ]);

                    $groupForumPostPageSendButtonWaiting = $groupForumPostPageSendButton->addGroup(__("bearcms.themes.options.forumPostPage.Send button waiting"));
                    $groupForumPostPageSendButtonWaiting->addOption("forumPostPageSendButtonWaitingCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-forum-post-page-send-button-waiting", "display:inline-block;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;"],
                            ["selector", ".bearcms-forum-post-page-send-button-waiting"]
                        ]
                    ]);
                };

                \BearCMS\Internal\ServerCommands::add('forumCategories', function() {
                    $list = \BearCMS\Internal\Data::getList('bearcms/forums/categories/category/');
                    $structure = \BearCMS\Internal\Data::getValue('bearcms/forums/categories/structure.json');
                    $temp = [];
                    $temp['structure'] = $structure !== null ? json_decode($structure, true) : [];
                    $temp['categories'] = [];
                    foreach ($list as $value) {
                        $temp['categories'][] = json_decode($value, true);
                    }
                    return $temp;
                });

                \BearCMS\Internal\ServerCommands::add('forumPostGet', function(array $data) {
                    $forumPosts = new \BearCMS\Internal\Data\Models\ForumPosts();
                    $result = $forumPosts->get($data['forumPostID']);
                    $result->author = \BearCMS\Internal\PublicProfile::getFromAuthor($result->author)->toArray();
                    $result->replies = new \IvoPetkov\DataList();
                    return $result->toArray();
                });

                \BearCMS\Internal\ServerCommands::add('forumPostReplyDelete', function(array $data) {
                    \BearCMS\Internal\Data\Utilities\ForumPostsReplies::deleteReplyForever($data['forumPostID'], $data['replyID']);
                });

                \BearCMS\Internal\ServerCommands::add('forumPostReplySetStatus', function(array $data) {
                    \BearCMS\Internal\Data\Utilities\ForumPostsReplies::setStatus($data['forumPostID'], $data['replyID'], $data['status']);
                });

                \BearCMS\Internal\ServerCommands::add('forumPostSetStatus', function(array $data) {
                    \BearCMS\Internal\Data\Utilities\ForumPosts::setStatus($data['forumPostID'], $data['status']);
                });

                \BearCMS\Internal\ServerCommands::add('forumPostsCount', function(array $data) {
                    $forumPosts = new \BearCMS\Internal\Data\Models\ForumPosts();
                    $result = $forumPosts->getList();
                    if ($data['type'] !== 'all') {
                        $result->filterBy('status', $data['type']);
                    }
                    return $result->length;
                });

                \BearCMS\Internal\ServerCommands::add('forumPostsList', function(array $data) {
                    $forumPosts = new \BearCMS\Internal\Data\Models\ForumPosts();
                    $result = $forumPosts->getList();
                    $result->sortBy('createdTime', 'desc');
                    if ($data['type'] !== 'all') {
                        $result->filterBy('status', $data['type']);
                    }
                    $result = $result->slice($data['limit'] * ($data['page'] - 1), $data['limit']);
                    foreach ($result as $i => $item) {
                        $result[$i]->location = '';
                        $result[$i]->author = \BearCMS\Internal\PublicProfile::getFromAuthor($item->author)->toArray();
                    }
                    return $result->toArray();
                });

                \BearCMS\Internal\ServerCommands::add('forumPostsRepliesCount', function(array $data) {
                    $forumPostsReplies = new \BearCMS\Internal\Data\Models\ForumPostsReplies();
                    $result = $forumPostsReplies->getList();
                    if (isset($data['forumPostID']) && strlen($data['forumPostID']) > 0) {
                        $result->filterBy('forumPostID', $data['forumPostID']);
                    }
                    if ($data['type'] !== 'all') {
                        $result->filterBy('status', $data['type']);
                    }
                    return $result->length;
                });

                \BearCMS\Internal\ServerCommands::add('forumPostsRepliesList', function(array $data) {
                    $forumPostsReplies = new \BearCMS\Internal\Data\Models\ForumPostsReplies();
                    $result = $forumPostsReplies->getList();
                    $result->sortBy('createdTime', 'desc');
                    if (isset($data['forumPostID']) && strlen($data['forumPostID']) > 0) {
                        $result->filterBy('forumPostID', $data['forumPostID']);
                    }
                    if ($data['type'] !== 'all') {
                        $result->filterBy('status', $data['type']);
                    }
                    $result = $result->slice($data['limit'] * ($data['page'] - 1), $data['limit']);
                    foreach ($result as $i => $item) {
                        $result[$i]->location = '';
                        $result[$i]->author = \BearCMS\Internal\PublicProfile::getFromAuthor($item->author)->toArray();
                    }
                    return $result->toArray();
                });

                if (\BearCMS\Internal\Config::hasFeature('NOTIFICATIONS')) {
                    $app->tasks
                    ->define('bearcms-send-new-forum-post-notification', function($data) {
                        $forumPostID = $data['forumPostID'];
                        $forumPosts = new \BearCMS\Internal\Data\Models\ForumPosts();
                        $forumPost = $forumPosts->get($forumPostID);
                        if ($forumPost !== null) {
                            $list = $forumPosts->getList()
                                    ->filterBy('status', 'pendingApproval');
                            $pendingApprovalCount = $list->length;
                            $profile = \BearCMS\Internal\PublicProfile::getFromAuthor($forumPost->author);
                            \BearCMS\Internal\Data::sendNotification('forum-posts', $forumPost->status, $profile->name, $forumPost->title, $pendingApprovalCount);
                        }
                    })
                    ->define('bearcms-send-new-forum-post-reply-notification', function($data) {
                        $forumPostID = $data['forumPostID'];
                        $forumPostReplyID = $data['forumPostReplyID'];
                        $forumPostsReplies = new \BearCMS\Internal\Data\Models\ForumPostsReplies();
                        $list = $forumPostsReplies->getList()
                                ->filterBy('forumPostID', $forumPostID)
                                ->filterBy('id', $forumPostReplyID);
                        if (isset($list[0])) {
                            $forumPostsReply = $list[0];
                            $list = $forumPostsReplies->getList()
                                    ->filterBy('status', 'pendingApproval');
                            $pendingApprovalCount = $list->length;
                            $profile = \BearCMS\Internal\PublicProfile::getFromAuthor($forumPostsReply->author);
                            \BearCMS\Internal\Data::sendNotification('forum-posts-replies', $forumPostsReply->status, $profile->name, $forumPostsReply->text, $pendingApprovalCount);
                        }
                    });
                }
            };
        });
