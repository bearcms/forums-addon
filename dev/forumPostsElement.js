/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

/* global clientShortcuts */

var bearCMS = bearCMS || {};
bearCMS.forumPostsElement = bearCMS.forumPostsElement || (function () {

    var updateList = function (content, listElement) {
        clientShortcuts.get('-bearcms-forums-html5domdocument').then(function (html5DOMDocument) {
            html5DOMDocument.insert(content, [listElement, 'outerHTML']);
        });
    };

    var loadMore = function (button, data) {
        button.innerHTML += " ...";
        var listElement = button.parentNode.parentNode;
        clientShortcuts.get('serverRequests').then(function (serverRequests) {
            var requestData = [];
            requestData['serverData'] = data['serverData'];
            serverRequests.send('-bearcms-forumposts-load-more', requestData).then(function (responseText) {
                var result = JSON.parse(responseText);
                updateList(result.html, listElement);
            });
        });
    };

    var _openNewPost = function (newPostServerData, lightboxContext) {
        clientShortcuts.get('serverRequests').then(function (serverRequests) {
            serverRequests.send('-bearcms-forums-new-post-form', newPostServerData).then(function (responseText) {
                var result = JSON.parse(responseText);
                if (typeof result.html !== 'undefined') {
                    lightboxContext.open(result.html);
                }
            });
        });
    };

    var openNewPost = function (newPostServerData) {
        clientShortcuts.get('lightbox').then(function (lightbox) {
            lightbox.wait(function (context) {
                clientShortcuts.get('users').then(function (users) {
                    if (users.currentUser.exists()) {
                        _openNewPost(newPostServerData, context);
                    } else {
                        users.openLogin();
                    }
                });

            });
        });
    };

    return {
        'loadMore': loadMore,
        'openNewPost': openNewPost
    };

}());