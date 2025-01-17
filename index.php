<?php

/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

use BearFramework\App;
use BearCMS\Internal;

$app = App::get();

$app->bearCMS->addons
    ->register('bearcms/forums-addon', function (\BearCMS\Addons\Addon $addon) use ($app): void {
        $addon->initialize = function (array $options) use ($app): void {
            $forumPagesPathPrefix = isset($options['forumPagesPathPrefix']) ? $options['forumPagesPathPrefix'] : '/f/';

            $context = $app->contexts->get(__DIR__);
            $context->assets->addDir('assets');

            $app->localization
                ->addDictionary('en', function () use ($context) {
                    return include $context->dir . '/locales/en.php';
                })
                ->addDictionary('bg', function () use ($context) {
                    return include $context->dir . '/locales/bg.php';
                })
                ->addDictionary('ru', function () use ($context) {
                    return include $context->dir . '/locales/ru.php';
                });

            $context->classes
                ->add('BearCMS\*', 'classes/*.php');

            Internal\ForumsData::$forumPagesPathPrefix = $forumPagesPathPrefix;

            $type = new \BearCMS\Internal\ElementType('forumPosts', 'bearcms-forum-posts-element', $context->dir . '/components/bearcmsForumPostsElement.php');
            $type->properties = [
                [
                    'id' => 'categoryID',
                    'type' => 'string'
                ],
                [
                    'id' => 'count',
                    'type' => 'int'
                ],
                [
                    'id' => 'showRepliesCount',
                    'type' => 'int'
                ]
            ];
            $type->canStyle = true;
            $type->canImportExport = true;
            \BearCMS\Internal\ElementsTypes::add($type);

            $app->routes
                ->add([$forumPagesPathPrefix . '?', $forumPagesPathPrefix . '?/'], [
                    [$app->bearCMS, 'disabledCheck'],
                    function (App\Request $request) use ($app, $context, $forumPagesPathPrefix) {
                        $forumPostSlug = $app->request->path->getSegment(1);
                        $forumPostID = Internal\ForumsUtilities::getIDFromSlug($forumPostSlug);
                        $forumPosts = new Internal\Data\Models\ForumPosts();
                        $forumPost = $forumPosts->get($forumPostID);
                        if ($forumPost !== null) {
                            $realSlug = Internal\ForumsUtilities::getSlug($forumPost->id, $forumPost->title);
                            if ($realSlug !== $forumPostSlug) {
                                $newUrl = $app->urls->get($forumPagesPathPrefix . $realSlug . '/');
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

                            $path = $request->path->get();
                            $hasSlash = substr($path, -1) === '/';
                            if (!$hasSlash) {
                                $tempRequest = clone ($request);
                                $tempRequest->path->set($path . '/');
                                return new App\Response\PermanentRedirect($tempRequest->getURL());
                            }

                            $strlen = function (string $string) {
                                return function_exists('mb_strlen') ? mb_strlen($string) : strlen($string);
                            };

                            $substr = function (string $string, int $start, ?int $length = null) {
                                return function_exists('mb_substr') ? mb_substr($string, $start, $length) : substr($string, $start, $length);
                            };

                            $content = '<html data-bearcms-page-type="forum-post">';
                            $content .= '<head>';
                            $content .= '<title>' . htmlspecialchars(\BearCMS\Internal\Data\Settings::applyPageTitleFormat($forumPost->title)) . '</title>';
                            $descriptionContent = $forumPost->text;
                            $content .= '<meta name="description" content="' . htmlentities($substr($descriptionContent, 0, 200) . ($strlen($descriptionContent) > 200 ? ' ...' : '')) . '">';
                            $content .= '<style>'
                                . '.bearcms-forum-post-page-title-container{word-break:break-word;}'
                                . '.bearcms-forum-post-page-content{word-break:break-word;}'
                                . '.bearcms-forum-post-page-content [data-form-element-type="submit-button"]{font-size:0;}'
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
                                $eventDetails = new Internal\MakeForumPostPageResponseEventDetails($response, $forumPostID);
                                $app->bearCMS->dispatchEvent('internalMakeForumPostPageResponse', $eventDetails);
                            }
                            $app->bearCMS->apply($response);
                            return $response;
                        }
                    }
                ]);

            Internal\Sitemap::addSource(function (Internal\Sitemap\Sitemap $sitemap) use ($app): void {
                $forumPosts = new Internal\Data\Models\ForumPosts();
                $posts = $forumPosts->getList()
                    ->filter(function ($forumPost) {
                        if ($forumPost->status === 'approved') {
                            return true;
                        }
                        return false;
                    });
                foreach ($posts as $post) {
                    $forumPostID = $post->id;
                    $path = $post->getURLPath();
                    $sitemap->addItem($path, function () use ($forumPostID) {
                        $forumPosts = new Internal\Data\Models\ForumPosts();
                        $forumPost = $forumPosts->get($forumPostID);
                        if ($forumPost !== null) {
                            return strlen((string)$forumPost->lastChangeTime) > 0 ? (int)$forumPost->lastChangeTime : null;
                        }
                        return null;
                    });
                }
            });

            $app->serverRequests
                ->add('-bearcms-forumposts-load-more', function ($data) use ($app) {
                    if (isset($data['serverData'], $data['serverData'])) {
                        $serverData = Internal\TempClientData::get($data['serverData']);
                        if (is_array($serverData) && isset($serverData['componentHTML'])) {
                            $content = $app->components->process($serverData['componentHTML']);
                            return json_encode([
                                'html' => $content
                            ]);
                        }
                    }
                });

            $app->modalWindows
                ->add('-bearcms-forums-new-post-form', function ($data) use ($app, $context) {
                    if (isset($data['serverData'])) {
                        $serverData = Internal\TempClientData::get($data['serverData']);
                        if (is_array($serverData) && isset($serverData['categoryID'])) {
                            $forumCategoryID = (string) $serverData['categoryID'];
                            $content = '<component src="form" filename="' . $context->dir . '/components/bearcmsForumPostsElement/forumPostNewForm.php" categoryID="' . htmlentities($forumCategoryID) . '" />';
                            $content = $app->components->process($content);
                            $content = $app->clientPackages->process($content);
                            return [
                                'title' => __('bearcms.forumPosts.New post'),
                                'content' => $content,
                                'width' => '700px'
                            ];
                        }
                    }
                });

            Internal\Themes::$elementsOptions['forumPosts'] = ['v2', function ($options, $idPrefix, $parentSelector, $context, $details): void {
                $isElementContext = $context === Internal\Themes::OPTIONS_CONTEXT_ELEMENT;
                if ($isElementContext) {
                    $optionsGroup = $options;
                    $defaultStyleSelector = '';
                } else {
                    $optionsGroup = $options->addGroup(__("bearcms.themes.options.Forum posts"));
                    $defaultStyleSelector = ' .bearcms-element:not([class*="bearcms-element-style-"])';
                    $optionsGroup->details['internalElementSelector'] = [$idPrefix, $parentSelector . " .bearcms-forum-posts-element"];
                }

                $optionsGroup->addOption($idPrefix . "ForumPostsCSS", "css", '', [
                    "cssTypes" => ["cssPadding", "cssBorder", "cssRadius", "cssShadow", "cssBackground"],
                    "cssOptions" => array_diff(isset($details['cssOptions']) ? $details['cssOptions'] : [], ["*/focusState"]), // all but focus state
                    "cssOutput" => [
                        ["selector", $parentSelector . $defaultStyleSelector . "> .bearcms-forum-posts-element"]
                    ]
                ]);

                $groupForumPostsPost = $optionsGroup->addGroup(__("bearcms.themes.options.forumPosts.Post"));
                $groupForumPostsPost->addOption($idPrefix . "ForumPostsPostCSS", "css", '', [
                    "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                    "cssOptions" => array_diff(isset($details['cssOptions']) ? $details['cssOptions'] : [], ["*/focusState"]),
                    "cssOutput" => [
                        ["rule", $parentSelector . " .bearcms-forum-posts-post", "box-sizing:border-box;"],
                        ["selector", $parentSelector . $defaultStyleSelector . "> .bearcms-forum-posts-element .bearcms-forum-posts-post"]
                    ]
                ]);

                $groupForumPostsPostTitle = $groupForumPostsPost->addGroup(__("bearcms.themes.options.forumPosts.Title"));
                $groupForumPostsPostTitle->addOption($idPrefix . "ForumPostsTitleCSS", "css", '', [
                    "cssTypes" => ["cssText", "cssTextShadow"],
                    "cssOptions" => isset($details['cssOptions']) ? $details['cssOptions'] : [],
                    "cssOutput" => [
                        ["rule", $parentSelector . " .bearcms-forum-posts-post-title", "text-decoration:none;"],
                        ["selector", $parentSelector . $defaultStyleSelector . "> .bearcms-forum-posts-element .bearcms-forum-posts-post-title"]
                    ]
                ]);

                $groupForumPostsPostRepliesCount = $groupForumPostsPost->addGroup(__("bearcms.themes.options.forumPosts.Replies count"));
                $groupForumPostsPostRepliesCount->addOption($idPrefix . "ForumPostsRepliesCountCSS", "css", '', [
                    "cssTypes" => ["cssText", "cssTextShadow"],
                    "cssOptions" => array_diff(isset($details['cssOptions']) ? $details['cssOptions'] : [], ["*/focusState"]),
                    "cssOutput" => [
                        ["selector", $parentSelector . $defaultStyleSelector . "> .bearcms-forum-posts-element .bearcms-forum-posts-post-replies-count"]
                    ]
                ]);

                $groupForumPostsShowMoreButton = $optionsGroup->addGroup(__("bearcms.themes.options.forumPosts.Show more button"));
                $groupForumPostsShowMoreButton->addOption($idPrefix . "ForumPostsShowMoreButtonCSS", "css", '', [
                    "cssOptions" => isset($details['cssOptions']) ? $details['cssOptions'] : [],
                    "cssOutput" => [
                        ["rule", $parentSelector . " .bearcms-forum-posts-show-more-button", "box-sizing:border-box;display:inline-block;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;"],
                        ["selector", $parentSelector . $defaultStyleSelector . "> .bearcms-forum-posts-element .bearcms-forum-posts-show-more-button"]
                    ]
                ]);

                $groupForumPostsShowMoreButtonContainer = $groupForumPostsShowMoreButton->addGroup(__("bearcms.themes.options.forumPosts.Container"));
                $groupForumPostsShowMoreButtonContainer->addOption($idPrefix . "ForumPostsShowMoreButtonContainerCSS", "css", '', [
                    "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                    "cssOptions" => array_diff(isset($details['cssOptions']) ? $details['cssOptions'] : [], ["*/focusState"]),
                    "cssOutput" => [
                        ["rule", $parentSelector . " .bearcms-forum-posts-show-more-button-container", "box-sizing:border-box;"],
                        ["selector", $parentSelector . $defaultStyleSelector . "> .bearcms-forum-posts-element .bearcms-forum-posts-show-more-button-container"]
                    ]
                ]);

                $groupForumPostsNewPostButton = $optionsGroup->addGroup(__("bearcms.themes.options.forumPosts.New post button"));
                $groupForumPostsNewPostButton->addOption($idPrefix . "ForumPostsNewPostButtonCSS", "css", '', [
                    "cssOptions" => isset($details['cssOptions']) ? $details['cssOptions'] : [],
                    "cssOutput" => [
                        ["rule", $parentSelector . " .bearcms-forum-posts-new-post-button", "box-sizing:border-box;display:inline-block;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;"],
                        ["selector", $parentSelector . $defaultStyleSelector . "> .bearcms-forum-posts-element .bearcms-forum-posts-new-post-button"]
                    ]
                ]);

                $groupForumPostsNewPostButtonContainer = $groupForumPostsNewPostButton->addGroup(__("bearcms.themes.options.forumPosts.Container"));
                $groupForumPostsNewPostButtonContainer->addOption($idPrefix . "ForumPostsNewPostButtonContainerCSS", "css", '', [
                    "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                    "cssOptions" => array_diff(isset($details['cssOptions']) ? $details['cssOptions'] : [], ["*/focusState"]),
                    "cssOutput" => [
                        ["rule", $parentSelector . " .bearcms-forum-posts-new-post-button-container", "box-sizing:border-box;display:flex;"],
                        ["selector", $parentSelector . $defaultStyleSelector . "> .bearcms-forum-posts-element .bearcms-forum-posts-new-post-button-container"]
                    ]
                ]);

                $containerSelector = $defaultStyleSelector . ":has(> .bearcms-forum-posts-element)";
                $groupContainer = $optionsGroup->addGroup(__("bearcms.themes.options.Container"));
                $groupContainer->addOption($idPrefix . "ForumPostsContainerCSS", "css", '', [
                    "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssTextAlign", "cssSize", "cssTransform"],
                    "cssOptions" => ["*/hoverState", "*/activeState", "*/visibilityState", "*/sizeState", "*/screenSizeState", "*/pageTypeState", "*/tagsState"],
                    "cssOutput" => [
                        ["rule", $parentSelector . $containerSelector, "box-sizing:border-box;"],
                        ["selector", $parentSelector . $containerSelector]
                    ]
                ]);

                if ($isElementContext) {
                    $groupContainer->addVisibility($idPrefix . "ForumPostsContainerVisibility", $parentSelector . $containerSelector);
                }
            }];

            Internal\Themes::$pagesOptions['forums'] = function ($options, array $details = []): void {

                $groupForumPostPage = $options->addGroup(__("bearcms.themes.options.Forum post page"));

                $groupForumPostPageTitle = $groupForumPostPage->addGroup(__("bearcms.themes.options.forumPostPage.Title"));
                $groupForumPostPageTitle->addOption("forumPostPageTitleCSS", "css", '', [
                    "cssOptions" => array_diff(isset($details['cssOptions']) ? $details['cssOptions'] : [], ["*/focusState"]),
                    "cssOutput" => [
                        ["rule", ".bearcms-forum-post-page-title", "box-sizing:border-box;font-weight:normal;padding:0;margin:0;"],
                        ["selector", ".bearcms-forum-post-page-title"]
                    ]
                ]);

                $groupForumPostPageTitleContainer = $groupForumPostPageTitle->addGroup(__("bearcms.themes.options.forumPostPage.Container"));
                $groupForumPostPageTitleContainer->addOption("forumPostPageTitleContainerCSS", "css", '', [
                    "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                    "cssOptions" => array_diff(isset($details['cssOptions']) ? $details['cssOptions'] : [], ["*/focusState"]),
                    "cssOutput" => [
                        ["rule", ".bearcms-forum-post-page-title-container", "box-sizing:border-box;"],
                        ["selector", ".bearcms-forum-post-page-title-container"]
                    ]
                ]);

                $groupForumPostPageContent = $groupForumPostPage->addGroup(__("bearcms.themes.options.forumPostPage.Content"));
                $groupForumPostPageContent->addOption("forumPostPageContentCSS", "css", '', [
                    "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                    "cssOptions" => array_diff(isset($details['cssOptions']) ? $details['cssOptions'] : [], ["*/focusState"]),
                    "cssOutput" => [
                        ["rule", ".bearcms-forum-post-page-content", "box-sizing:border-box;"],
                        ["selector", ".bearcms-forum-post-page-content"]
                    ]
                ]);

                $groupForumPostPageReply = $groupForumPostPageContent->addGroup(__("bearcms.themes.options.forumPostPage.Reply"));
                $groupForumPostPageReply->addOption("forumPostPageReplyCSS", "css", '', [
                    "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                    "cssOptions" => array_diff(isset($details['cssOptions']) ? $details['cssOptions'] : [], ["*/focusState"]),
                    "cssOutput" => [
                        ["rule", ".bearcms-forum-post-page-reply", "box-sizing:border-box;"],
                        ["selector", ".bearcms-forum-post-page-reply"]
                    ]
                ]);

                $groupForumPostPageReplyAuthorName = $groupForumPostPageReply->addGroup(__("bearcms.themes.options.forumPostPage.Author name"));
                $groupForumPostPageReplyAuthorName->addOption("forumPostPageReplyAuthorNameCSS", "css", '', [
                    "cssTypes" => ["cssText", "cssTextShadow"],
                    "cssOptions" => isset($details['cssOptions']) ? $details['cssOptions'] : [],
                    "cssOutput" => [
                        ["selector", ".bearcms-forum-post-page-reply-author-name"]
                    ]
                ]);

                $groupForumPostPageReplyAuthorImage = $groupForumPostPageReply->addGroup(__("bearcms.themes.options.forumPostPage.Author image"));
                $groupForumPostPageReplyAuthorImage->addOption("forumPostPageReplyAuthorImageCSS", "css", '', [
                    "cssTypes" => ["cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                    "cssOptions" => isset($details['cssOptions']) ? $details['cssOptions'] : [],
                    "cssOutput" => [
                        ["rule", ".bearcms-forum-post-page-reply-author-image", "box-sizing:border-box;"],
                        ["selector", ".bearcms-forum-post-page-reply-author-image"]
                    ]
                ]);

                $groupForumPostPageReplyDate = $groupForumPostPageReply->addGroup(__("bearcms.themes.options.forumPostPage.Date"));
                $groupForumPostPageReplyDate->addOption("forumPostPageReplyDateCSS", "css", '', [
                    "cssTypes" => ["cssText", "cssTextShadow"],
                    "cssOptions" => array_diff(isset($details['cssOptions']) ? $details['cssOptions'] : [], ["*/focusState"]),
                    "cssOutput" => [
                        ["selector", ".bearcms-forum-post-page-reply-date"]
                    ]
                ]);

                $groupForumPostPageReplyText = $groupForumPostPageReply->addGroup(__("bearcms.themes.options.forumPostPage.Text"));
                $groupForumPostPageReplyText->addOption("forumPostPageReplyTextCSS", "css", '', [
                    "cssTypes" => ["cssText", "cssTextShadow"],
                    "cssOptions" => array_diff(isset($details['cssOptions']) ? $details['cssOptions'] : [], ["*/focusState"]),
                    "cssOutput" => [
                        ["selector", ".bearcms-forum-post-page-reply-text"]
                    ]
                ]);

                $groupForumPostPageReplyTextLinks = $groupForumPostPageReply->addGroup(__("bearcms.themes.options.forumPostPage.Text links"));
                $groupForumPostPageReplyTextLinks->addOption("forumPostPageReplyTextLinksCSS", "css", '', [
                    "cssTypes" => ["cssText", "cssTextShadow"],
                    "cssOptions" => isset($details['cssOptions']) ? $details['cssOptions'] : [],
                    "cssOutput" => [
                        ["selector", ".bearcms-forum-post-page-reply-text a"]
                    ]
                ]);

                $groupForumPostPageTextInput = $groupForumPostPageContent->addGroup(__("bearcms.themes.options.forumPostPage.Text input"));
                $groupForumPostPageTextInput->addOption("forumPostPageTextInputCSS", "css", '', [
                    "cssTypes" => ["cssText", "cssTextShadow", "cssPadding", "cssMargin", "cssBorder", "cssRadius", "cssShadow", "cssBackground", "cssSize"],
                    "cssOptions" => isset($details['cssOptions']) ? $details['cssOptions'] : [],
                    "cssOutput" => [
                        ["rule", ".bearcms-forum-post-page-text-input", "box-sizing:border-box;border:0;margin:0;padding:0;"],
                        ["selector", ".bearcms-forum-post-page-text-input"]
                    ]
                ]);

                $groupForumPostPageSendButton = $groupForumPostPageContent->addGroup(__("bearcms.themes.options.forumPostPage.Send button"));
                $groupForumPostPageSendButton->addOption("forumPostPageSendButtonCSS", "css", '', [
                    "cssOptions" => isset($details['cssOptions']) ? $details['cssOptions'] : [],
                    "cssOutput" => [
                        ["rule", ".bearcms-forum-post-page-send-button", "box-sizing:border-box;display:inline-block;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;"],
                        ["selector", ".bearcms-forum-post-page-send-button"]
                    ]
                ]);

                $groupForumPostPageSendButtonWaiting = $groupForumPostPageSendButton->addGroup(__("bearcms.themes.options.forumPostPage.Send button waiting"));
                $groupForumPostPageSendButtonWaiting->addOption("forumPostPageSendButtonWaitingCSS", "css", '', [
                    "cssOptions" => array_diff(isset($details['cssOptions']) ? $details['cssOptions'] : [], ["*/focusState"]),
                    "cssOutput" => [
                        ["rule", ".bearcms-forum-post-page-send-button-waiting", "box-sizing:border-box;display:inline-block;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;"],
                        ["selector", ".bearcms-forum-post-page-send-button-waiting"]
                    ]
                ]);
            };

            // Posts

            Internal\ServerCommands::add('forumPostsList', function (array $data) {
                $result = \BearCMS\Internal\Data\Utilities\ForumPosts::getList();
                $result->sortBy('createdTime', 'desc');
                if ($data['type'] !== 'all') {
                    $result->filterBy('status', $data['type']);
                }
                $result = $result->slice($data['limit'] * ($data['page'] - 1), $data['limit']);
                foreach ($result as $i => $item) {
                    $result[$i]->location = '';
                    $result[$i]->author = Internal\PublicProfile::getFromAuthor($item->author)->toArray();
                }
                return $result->toArray();
            });

            Internal\ServerCommands::add('forumPostsCount', function (array $data) {
                $result = \BearCMS\Internal\Data\Utilities\ForumPosts::getList();
                if ($data['type'] !== 'all') {
                    $result->filterBy('status', $data['type']);
                }
                return $result->count();
            });

            Internal\ServerCommands::add('forumPostGet', function (array $data) {
                $forumPosts = new Internal\Data\Models\ForumPosts();
                $result = $forumPosts->get($data['forumPostID']);
                if ($result !== null) {
                    $result->author = Internal\PublicProfile::getFromAuthor($result->author)->toArray();
                    $result->replies = new \IvoPetkov\DataList();
                    return $result->toArray();
                }
            });

            Internal\ServerCommands::add('forumPostSetStatus', function (array $data): void {
                Internal\Data\Utilities\ForumPosts::setStatus($data['forumPostID'], $data['status']);
            });

            // Replies

            Internal\ServerCommands::add('forumPostsRepliesList', function (array $data) {
                $result = \BearCMS\Internal\Data\Utilities\ForumPostsReplies::getList();
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
                    $result[$i]->author = Internal\PublicProfile::getFromAuthor($item->author)->toArray();
                }
                return $result->toArray();
            });

            Internal\ServerCommands::add('forumPostsRepliesCount', function (array $data) {
                $result = \BearCMS\Internal\Data\Utilities\ForumPostsReplies::getList();
                if (isset($data['forumPostID']) && strlen($data['forumPostID']) > 0) {
                    $result->filterBy('forumPostID', $data['forumPostID']);
                }
                if ($data['type'] !== 'all') {
                    $result->filterBy('status', $data['type']);
                }
                return $result->count();
            });

            Internal\ServerCommands::add('forumPostsRepliesGet', function (array $data) {
                $forumPostsReplies = new Internal\Data\Models\ForumPostsReplies();
                $result = $forumPostsReplies->get($data['forumPostID'], $data['replyID']);
                if ($result !== null) {
                    $result->author = Internal\PublicProfile::getFromAuthor($result->author)->toArray();
                    return $result->toArray();
                }
                return null;
            });

            Internal\ServerCommands::add('forumPostReplySetStatus', function (array $data) use (&$forumPostsRepliesListCache): void {
                Internal\Data\Utilities\ForumPostsReplies::setStatus($data['forumPostID'], $data['replyID'], $data['status']);
                $forumPostsRepliesListCache = null;
            });

            Internal\ServerCommands::add('forumPostReplyDelete', function (array $data) use (&$forumPostsRepliesListCache): void {
                Internal\Data\Utilities\ForumPostsReplies::deleteReplyForever($data['forumPostID'], $data['replyID']);
                $forumPostsRepliesListCache = null;
            });

            // Categories

            Internal\ServerCommands::add('forumCategories', function () {
                $list = Internal\Data::getList('bearcms/forums/categories/category/');
                $structure = Internal\Data::getValue('bearcms/forums/categories/structure.json');
                $temp = [];
                $temp['structure'] = $structure !== null ? json_decode($structure, true) : [];
                $temp['categories'] = [];
                foreach ($list as $value) {
                    $temp['categories'][] = json_decode($value, true);
                }
                return $temp;
            });

            if (Internal\Config::hasFeature('NOTIFICATIONS')) {
                $app->tasks
                    ->define('bearcms-send-new-forum-post-notification', function ($data): void {
                        $forumPostID = $data['forumPostID'];
                        $forumPosts = new Internal\Data\Models\ForumPosts();
                        $forumPost = $forumPosts->get($forumPostID);
                        if ($forumPost !== null) {
                            $list = $forumPosts->getList()
                                ->filterBy('status', 'pendingApproval');
                            $pendingApprovalCount = $list->count();
                            $profile = Internal\PublicProfile::getFromAuthor($forumPost->author);
                            Internal\Data::sendNotification('forum-posts', $forumPost->status, $profile->name, $forumPost->title, $pendingApprovalCount);
                        }
                    })
                    ->define('bearcms-send-new-forum-post-reply-notification', function ($data): void {
                        $forumPostID = $data['forumPostID'];
                        $forumPostReplyID = $data['forumPostReplyID'];
                        $forumPostsReplies = new Internal\Data\Models\ForumPostsReplies();
                        $list = $forumPostsReplies->getList()
                            ->filterBy('forumPostID', $forumPostID)
                            ->filterBy('id', $forumPostReplyID);
                        if (isset($list[0])) {
                            $forumPostsReply = $list[0];
                            $list = $forumPostsReplies->getList()
                                ->filterBy('status', 'pendingApproval');
                            $pendingApprovalCount = $list->count();
                            $profile = Internal\PublicProfile::getFromAuthor($forumPostsReply->author);
                            Internal\Data::sendNotification('forum-posts-replies', $forumPostsReply->status, $profile->name, $forumPostsReply->text, $pendingApprovalCount);
                        }
                    });
            }

            $app->clientPackages
                ->add('-bearcms-forums-element', function (IvoPetkov\BearFrameworkAddons\ClientPackage $package): void {
                    //$js = file_get_contents(__DIR__ . '/dev/forumPostsElement.js');
                    $js = include __DIR__ . '/assets/forumPostsElement.min.js.php';
                    $package->addJSCode($js);
                    $package->embedPackage('modalWindows');
                })
                ->add('-bearcms-forums-element-reply', function (IvoPetkov\BearFrameworkAddons\ClientPackage $package): void {
                    //$js = file_get_contents(__DIR__ . '/dev/forumPostReply.js');
                    $js = include __DIR__ . '/assets/forumPostReply.min.js.php';
                    $package->addJSCode($js);
                });
        };
    });
