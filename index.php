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
        ->register('bearcms/forums-addon', function(\BearCMS\Addons\Addon $addon) use ($app) {
            $addon->initialize = function() use ($app) {
                $context = $app->contexts->get(__FILE__);

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
                ->add('BearCMS\*', 'classes/*.php');

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
                        $forumPostSlug = $app->request->path->getSegment(1);
                        $forumPostID = BearCMS\Internal\Utilities::getIDFromSlug($forumPostSlug);
                        $forumPosts = new \BearCMS\Internal\Data\Models\ForumPosts();
                        $forumPost = $forumPosts->get($forumPostID);
                        if ($forumPost !== null) {
                            $realSlug = \BearCMS\Internal\Utilities::getSlug($forumPost->id, $forumPost->title);
                            if ($realSlug !== $forumPostSlug) {
                                $newUrl = $app->urls->get('/f/' . $realSlug . '/');
                                $response = new App\Response\PermanentRedirect($newUrl);
                                return $response;
                            }
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
                            $content .= '<style>'
                                    . '.bearcms-forum-post-page-title-container{word-break:break-word;}'
                                    . '.bearcms-forum-post-page-content{word-break:break-word;}'
                                    . '</style>';
                            $content .= '</head>';
                            $content .= '<body>';
                            $content .= '<div class="bearcms-forum-post-page-title-container"><h1 class="bearcms-forum-post-page-title">' . htmlspecialchars($forumPost->title) . '</h1></div>';
                            $content .= '<div class="bearcms-forum-post-page-content">';
                            $content .= '<component src="file:' . $context->dir . '/components/bearcmsForumPostsElement/forumPostRepliesList.php" includePost="true" forumPostID="' . htmlentities($forumPost->id) . '" />';
                            $content .= '<component src="form" filename="' . $context->dir . '/components/bearcmsForumPostsElement/forumPostReplyForm.php" forumPostID="' . htmlentities($forumPost->id) . '" />';
                            $content .= '</div>';
                            $content .= '</body>';
                            $content .= '</html>';

                            $forumPostID = $forumPost->id;

                            $response = new App\Response\HTML($content);
                            if ($app->bearCMS->hasEventListeners('internalMakeForumPostPageResponse')) {
                                $eventDetails = new \BearCMS\Internal\MakeForumPostPageResponseEventDetails($response, $forumPostID);
                                $app->bearCMS->dispatchEvent('internalMakeForumPostPageResponse', $eventDetails);
                            }
                            $app->bearCMS->apply($response);
                            return $response;
                        }
                    }
                ]);

                $app->serverRequests
                ->add('-bearcms-forumposts-load-more', function($data) use ($app) {
                    if (isset($data['serverData'], $data['serverData'])) {
                        $serverData = \BearCMS\Internal\TempClientData::get($data['serverData']);
                        if (is_array($serverData) && isset($serverData['componentHTML'])) {
                            $content = $app->components->process($serverData['componentHTML']);
                            return json_encode([
                                'html' => $content
                            ]);
                        }
                    }
                })
                ->add('-bearcms-forums-new-post-form', function($data) use ($app, $context) {
                    if (isset($data['serverData'], $data['serverData'])) {
                        $serverData = \BearCMS\Internal\TempClientData::get($data['serverData']);
                        if (is_array($serverData) && isset($serverData['categoryID'])) {
                            $forumCategoryID = (string) $serverData['categoryID'];
                            $content = $app->components->process('<component src="form" filename="' . $context->dir . '/components/bearcmsForumPostsElement/forumPostNewForm.php" categoryID="' . htmlentities($forumCategoryID) . '" />');
                            return json_encode([
                                'html' => $content
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
                            ["rule", $parentSelector . " .bearcms-forum-posts-post", "box-sizing:border-box;"],
                            ["selector", $parentSelector . " .bearcms-forum-posts-post"]
                        ]
                    ]);

                    $groupForumPostsPostTitle = $groupForumPostsPost->addGroup(__("bearcms.themes.options.forumPosts.Title"));
                    $groupForumPostsPostTitle->addOption($idPrefix . "ForumPostsTitleCSS", "css", '', [
                        "cssTypes" => ["cssText", "cssTextShadow"],
                        "cssOutput" => [
                            ["rule", $parentSelector . " .bearcms-forum-posts-post-title", "text-decoration:none;"],
                            ["selector", $parentSelector . " .bearcms-forum-posts-post-title"]
                        ]
                    ]);

                    $groupForumPostsPostRepliesCount = $groupForumPostsPost->addGroup(__("bearcms.themes.options.forumPosts.Replies count"));
                    $groupForumPostsPostRepliesCount->addOption($idPrefix . "ForumPostsRepliesCountCSS", "css", '', [
                        "cssTypes" => ["cssText", "cssTextShadow"],
                        "cssOutput" => [
                            ["selector", $parentSelector . " .bearcms-forum-posts-post-replies-count"]
                        ]
                    ]);

                    $groupForumPostsShowMoreButton = $groupForumPosts->addGroup(__("bearcms.themes.options.forumPosts.Show more button"));
                    $groupForumPostsShowMoreButton->addOption($idPrefix . "ForumPostsShowMoreButtonCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", $parentSelector . " .bearcms-forum-posts-show-more-button", "box-sizing:border-box;display:inline-block;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;"],
                            ["selector", $parentSelector . " .bearcms-forum-posts-show-more-button"]
                        ]
                    ]);

                    $groupForumPostsShowMoreButtonContainer = $groupForumPostsShowMoreButton->addGroup(__("bearcms.themes.options.forumPosts.Container"));
                    $groupForumPostsShowMoreButtonContainer->addOption($idPrefix . "ForumPostsShowMoreButtonContainerCSS", "css", '', [
                        "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["rule", $parentSelector . " .bearcms-forum-posts-show-more-button-container", "box-sizing:border-box;"],
                            ["selector", $parentSelector . " .bearcms-forum-posts-show-more-button-container"]
                        ]
                    ]);

                    $groupForumPostsNewPostButton = $groupForumPosts->addGroup(__("bearcms.themes.options.forumPosts.New post button"));
                    $groupForumPostsNewPostButton->addOption($idPrefix . "ForumPostsNewPostButtonCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", $parentSelector . " .bearcms-forum-posts-new-post-button", "box-sizing:border-box;display:inline-block;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;"],
                            ["selector", $parentSelector . " .bearcms-forum-posts-new-post-button"]
                        ]
                    ]);

                    $groupForumPostsNewPostButtonContainer = $groupForumPostsNewPostButton->addGroup(__("bearcms.themes.options.forumPosts.Container"));
                    $groupForumPostsNewPostButtonContainer->addOption($idPrefix . "ForumPostsShowMoreButtonContainerCSS", "css", '', [
                        "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["rule", $parentSelector . " .bearcms-forum-posts-new-post-button-container", "box-sizing:border-box;"],
                            ["selector", $parentSelector . " .bearcms-forum-posts-new-post-button-container"]
                        ]
                    ]);
                };

                \BearCMS\Internal\Themes::$pagesOptions['forums'] = function($context) {
                    $groupNewForumPostPage = $context->addGroup(__("bearcms.themes.options.New forum post page"));

                    $groupNewForumPostPageTitle = $groupNewForumPostPage->addGroup(__("bearcms.themes.options.forumPostPage.Title"));
                    $groupNewForumPostPageTitle->addOption("newForumPostPageTitleCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-new-forum-post-page-title", "box-sizing:border-box;font-weight:normal;padding:0;margin:0;"],
                            ["selector", ".bearcms-new-forum-post-page-title"]
                        ]
                    ]);

                    $groupNewForumPostPageTitleContainer = $groupNewForumPostPageTitle->addGroup(__("bearcms.themes.options.forumPostPage.Container"));
                    $groupNewForumPostPageTitleContainer->addOption("newForumPostPageTitleContainerCSS", "css", '', [
                        "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["rule", ".bearcms-new-forum-post-page-title-container", "box-sizing:border-box;"],
                            ["selector", ".bearcms-new-forum-post-page-title-container"]
                        ]
                    ]);

                    $groupNewForumPostPageContent = $groupNewForumPostPage->addGroup(__("bearcms.themes.options.forumPostPage.Content"));
                    $groupNewForumPostPageContent->addOption("newForumPostPageContentCSS", "css", '', [
                        "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["rule", ".bearcms-new-forum-post-page-content", "box-sizing:border-box;"],
                            ["selector", ".bearcms-new-forum-post-page-content"]
                        ]
                    ]);

                    $groupNewForumPostPageTitleLabel = $groupNewForumPostPageContent->addGroup(__("bearcms.themes.options.newForumPostPage.Title label"));
                    $groupNewForumPostPageTitleLabel->addOption("newForumPostPageTitleLabelCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-new-forum-post-page-title-label", "box-sizing:border-box;display:block;"],
                            ["selector", ".bearcms-new-forum-post-page-title-label"]
                        ]
                    ]);

                    $groupNewForumPostPageTitleInput = $groupNewForumPostPageContent->addGroup(__("bearcms.themes.options.newForumPostPage.Title input"));
                    $groupNewForumPostPageTitleInput->addOption("newForumPostPageTitleInputCSS", "css", '', [
                        "cssTypes" => ["cssText", "cssTextShadow", "cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["rule", ".bearcms-new-forum-post-page-title-input", "box-sizing:border-box;border:0;margin:0;padding:0;"],
                            ["selector", ".bearcms-new-forum-post-page-title-input"]
                        ]
                    ]);

                    $groupNewForumPostPageTextLabel = $groupNewForumPostPageContent->addGroup(__("bearcms.themes.options.newForumPostPage.Text label"));
                    $groupNewForumPostPageTextLabel->addOption("newForumPostPageTextLabelCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-new-forum-post-page-text-label", "box-sizing:border-box;display:block;"],
                            ["selector", ".bearcms-new-forum-post-page-text-label"]
                        ]
                    ]);

                    $groupNewForumPostPageTextInput = $groupNewForumPostPageContent->addGroup(__("bearcms.themes.options.newForumPostPage.Text input"));
                    $groupNewForumPostPageTextInput->addOption("newForumPostPageTextInputCSS", "css", '', [
                        "cssTypes" => ["cssText", "cssTextShadow", "cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["rule", ".bearcms-new-forum-post-page-text-input", "box-sizing:border-box;border:0;margin:0;padding:0;"],
                            ["selector", ".bearcms-new-forum-post-page-text-input"]
                        ]
                    ]);

                    $groupNewForumPostPageSendButton = $groupNewForumPostPageContent->addGroup(__("bearcms.themes.options.newForumPostPage.Send button"));
                    $groupNewForumPostPageSendButton->addOption("newForumPostPageSendButtonCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-new-forum-post-page-send-button", "box-sizing:border-box;display:inline-block;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;"],
                            ["selector", ".bearcms-new-forum-post-page-send-button"]
                        ]
                    ]);

                    $groupNewForumPostPageSendButtonWaiting = $groupNewForumPostPageSendButton->addGroup(__("bearcms.themes.options.newForumPostPage.Send button waiting"));
                    $groupNewForumPostPageSendButtonWaiting->addOption("newForumPostPageSendButtonWaitingCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-new-forum-post-page-send-button-waiting", "box-sizing:border-box;display:inline-block;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;"],
                            ["selector", ".bearcms-new-forum-post-page-send-button-waiting"]
                        ]
                    ]);

                    $groupForumPostPage = $context->addGroup(__("bearcms.themes.options.Forum post page"));

                    $groupForumPostPageTitle = $groupForumPostPage->addGroup(__("bearcms.themes.options.forumPostPage.Title"));
                    $groupForumPostPageTitle->addOption("forumPostPageTitleCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-forum-post-page-title", "box-sizing:border-box;font-weight:normal;padding:0;margin:0;"],
                            ["selector", ".bearcms-forum-post-page-title"]
                        ]
                    ]);

                    $groupForumPostPageTitleContainer = $groupForumPostPageTitle->addGroup(__("bearcms.themes.options.forumPostPage.Container"));
                    $groupForumPostPageTitleContainer->addOption("forumPostPageTitleContainerCSS", "css", '', [
                        "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["rule", ".bearcms-forum-post-page-title-container", "box-sizing:border-box;"],
                            ["selector", ".bearcms-forum-post-page-title-container"]
                        ]
                    ]);

                    $groupForumPostPageContent = $groupForumPostPage->addGroup(__("bearcms.themes.options.forumPostPage.Content"));
                    $groupForumPostPageContent->addOption("forumPostPageContentCSS", "css", '', [
                        "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["rule", ".bearcms-forum-post-page-content", "box-sizing:border-box;"],
                            ["selector", ".bearcms-forum-post-page-content"]
                        ]
                    ]);

                    $groupForumPostPageReply = $groupForumPostPageContent->addGroup(__("bearcms.themes.options.forumPostPage.Reply"));
                    $groupForumPostPageReply->addOption("forumPostPageReplyCSS", "css", '', [
                        "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["rule", ".bearcms-forum-post-page-reply", "box-sizing:border-box;"],
                            ["selector", ".bearcms-forum-post-page-reply"]
                        ]
                    ]);

                    $groupForumPostPageReplyAuthorName = $groupForumPostPageReply->addGroup(__("bearcms.themes.options.forumPostPage.Author name"));
                    $groupForumPostPageReplyAuthorName->addOption("forumPostPageReplyAuthorNameCSS", "css", '', [
                        "cssTypes" => ["cssText", "cssTextShadow"],
                        "cssOutput" => [
                            ["selector", ".bearcms-forum-post-page-reply-author-name"]
                        ]
                    ]);

                    $groupForumPostPageReplyAuthorImage = $groupForumPostPageReply->addGroup(__("bearcms.themes.options.forumPostPage.Author image"));
                    $groupForumPostPageReplyAuthorImage->addOption("forumPostPageReplyAuthorImageCSS", "css", '', [
                        "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["rule", ".bearcms-forum-post-page-reply-author-image", "box-sizing:border-box;"],
                            ["selector", ".bearcms-forum-post-page-reply-author-image"]
                        ]
                    ]);

                    $groupForumPostPageReplyDate = $groupForumPostPageReply->addGroup(__("bearcms.themes.options.forumPostPage.Date"));
                    $groupForumPostPageReplyDate->addOption("forumPostPageReplyDateCSS", "css", '', [
                        "cssTypes" => ["cssText", "cssTextShadow"],
                        "cssOutput" => [
                            ["selector", ".bearcms-forum-post-page-reply-date"]
                        ]
                    ]);

                    $groupForumPostPageReplyText = $groupForumPostPageReply->addGroup(__("bearcms.themes.options.forumPostPage.Text"));
                    $groupForumPostPageReplyText->addOption("forumPostPageReplyTextCSS", "css", '', [
                        "cssTypes" => ["cssText", "cssTextShadow"],
                        "cssOutput" => [
                            ["selector", ".bearcms-forum-post-page-reply-text"]
                        ]
                    ]);

                    $groupForumPostPageReplyTextLinks = $groupForumPostPageReply->addGroup(__("bearcms.themes.options.forumPostPage.Text links"));
                    $groupForumPostPageReplyTextLinks->addOption("forumPostPageReplyTextLinksCSS", "css", '', [
                        "cssTypes" => ["cssText", "cssTextShadow"],
                        "cssOutput" => [
                            ["selector", ".bearcms-forum-post-page-reply-text a"]
                        ]
                    ]);

                    $groupForumPostPageTextInput = $groupForumPostPageContent->addGroup(__("bearcms.themes.options.forumPostPage.Text input"));
                    $groupForumPostPageTextInput->addOption("forumPostPageTextInputCSS", "css", '', [
                        "cssTypes" => ["cssText", "cssTextShadow", "cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                        "cssOutput" => [
                            ["rule", ".bearcms-forum-post-page-text-input", "box-sizing:border-box;border:0;margin:0;padding:0;"],
                            ["selector", ".bearcms-forum-post-page-text-input"]
                        ]
                    ]);

                    $groupForumPostPageSendButton = $groupForumPostPageContent->addGroup(__("bearcms.themes.options.forumPostPage.Send button"));
                    $groupForumPostPageSendButton->addOption("forumPostPageSendButtonCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-forum-post-page-send-button", "box-sizing:border-box;display:inline-block;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;"],
                            ["selector", ".bearcms-forum-post-page-send-button"]
                        ]
                    ]);

                    $groupForumPostPageSendButtonWaiting = $groupForumPostPageSendButton->addGroup(__("bearcms.themes.options.forumPostPage.Send button waiting"));
                    $groupForumPostPageSendButtonWaiting->addOption("forumPostPageSendButtonWaitingCSS", "css", '', [
                        "cssOutput" => [
                            ["rule", ".bearcms-forum-post-page-send-button-waiting", "box-sizing:border-box;display:inline-block;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;"],
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
                    return $result->count();
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
                    return $result->count();
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
                            $pendingApprovalCount = $list->count();
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
                            $pendingApprovalCount = $list->count();
                            $profile = \BearCMS\Internal\PublicProfile::getFromAuthor($forumPostsReply->author);
                            \BearCMS\Internal\Data::sendNotification('forum-posts-replies', $forumPostsReply->status, $profile->name, $forumPostsReply->text, $pendingApprovalCount);
                        }
                    });
                }

                $app->clientShortcuts
                ->add('-bearcms-forums-element', function(IvoPetkov\BearFrameworkAddons\ClientShortcut $shortcut) {
                    $shortcut->requirements[] = [// taken from dev/forumPostsElement.js // file_get_contents(__DIR__ . '/dev/forumPostsElement.js')
                        'type' => 'text',
                        'value' => 'var bearCMS=bearCMS||{};bearCMS.forumPostsElement=bearCMS.forumPostsElement||function(){var e=function(a,b){clientShortcuts.get("-bearcms-forums-html5domdocument").then(function(c){c.insert(a,[b,"outerHTML"])})},f=function(a,b){clientShortcuts.get("serverRequests").then(function(c){c.send("-bearcms-forums-new-post-form",a).then(function(a){a=JSON.parse(a);"undefined"!==typeof a.html&&b.open(a.html)})})};return{loadMore:function(a,b){a.innerHTML+=" ...";var c=a.parentNode.parentNode;clientShortcuts.get("serverRequests").then(function(a){var d=[];d.serverData=b.serverData;a.send("-bearcms-forumposts-load-more",d).then(function(a){a=JSON.parse(a);e(a.html,c)})})},openNewPost:function(a){clientShortcuts.get("lightbox").then(function(b){b.wait(function(b){clientShortcuts.get("users").then(function(c){c.currentUser.exists()?f(a,b):c.openLogin()})})})}}}();',
                        'mimeType' => 'text/javascript'
                    ];
                })
                ->add('-bearcms-forums-element-reply', function(IvoPetkov\BearFrameworkAddons\ClientShortcut $shortcut) {
                    $shortcut->requirements[] = [// taken from dev/forumPostReply.js // file_get_contents(__DIR__ . '/dev/forumPostReply.js')
                        'type' => 'text',
                        'value' => 'var bearCMS=bearCMS||{};bearCMS.forumPostReply=bearCMS.forumPostReply||function(){var e=[],g=function(a){var b="ur"+a;"undefined"===typeof e[b]&&(e[b]=1,b=document.getElementById(a),clientShortcuts.get("users").then(function(b){b.currentUser.addEventListener("change",function(){f(a,null)})}),b.addEventListener("beforesubmit",k),b.addEventListener("submitsuccess",l))},f=function(a,b){var c=function(b){var c=document.getElementById(a),d=c.querySelector("textarea");b?(d.removeAttribute("readonly"),d.style.cursor="auto",d.removeEventListener("click",h),c.querySelector(".bearcms-forum-post-page-send-button").style.removeProperty("display")):(d.setAttribute("readonly",!0),d.style.cursor="pointer",d.addEventListener("click",h),c.querySelector(".bearcms-forum-post-page-send-button").style.display="none")};null!==b?c(b):clientShortcuts.get("users").then(function(a){c(a.currentUser.exists())})},h=function(a){var b=a.target.parentNode.parentNode.id;clientShortcuts.get("users").then(function(a){g(b);a.openLogin()})},m=function(a){clientShortcuts.get("-bearcms-forums-html5domdocument").then(function(b){var c=document.getElementById(a.listElementID);b.insert(a.listContent,[c,"outerHTML"])})},k=function(a){a=a.target;var b=a.previousSibling;a.querySelector(\'[name="fprcontext"]\').value=JSON.stringify({listElementID:b.id})},l=function(a){var b=a.target;a=a.result;"undefined"!==typeof a.success&&(b.querySelector(\'[name="fprtext"]\').value="",m(a))};return{initializeForm:function(a,b){f(a,b);if(b)return g(a)}}}();',
                        'mimeType' => 'text/javascript'
                    ];
                })
                ->add('-bearcms-forums-html5domdocument', function($shortcut) use ($context) {
                    $shortcut->requirements[] = [
                        'type' => 'file',
                        'url' => $context->assets->getURL('assets/HTML5DOMDocument.min.js', ['cacheMaxAge' => 999999999, 'version' => 1]),
                        'mimeType' => 'text/javascript'
                    ];
                    $shortcut->get = 'return html5DOMDocument;';
                });
            };
        });
