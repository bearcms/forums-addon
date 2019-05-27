/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

/* global clientPackages */

var bearCMS = bearCMS || {};
bearCMS.forumPostsElement = bearCMS.forumPostsElement || (function () {

    var updateList = function (content, listElement) {
        clientPackages.get('-bearcms-forums-html5domdocument').then(function (html5DOMDocument) {
            html5DOMDocument.insert(content, [listElement, 'outerHTML']);
        });
    };

    var loadMore = function (button, data) {
        button.innerHTML += " ...";
        var listElement = button.parentNode.parentNode;
        clientPackages.get('serverRequests').then(function (serverRequests) {
            var requestData = [];
            requestData['serverData'] = data['serverData'];
            serverRequests.send('-bearcms-forumposts-load-more', requestData).then(function (responseText) {
                var result = JSON.parse(responseText);
                updateList(result.html, listElement);
            });
        });
    };

    var _openNewPost = function (newPostServerData, lightboxContext) {
        clientPackages.get('serverRequests').then(function (serverRequests) {
            serverRequests.send('-bearcms-forums-new-post-form', newPostServerData).then(function (responseText) {
                var result = JSON.parse(responseText);
                if (typeof result.html !== 'undefined') {
                    lightboxContext.open(result.html);
                }
            });
        });
    };

    var openNewPost = function (newPostServerData) {
        clientPackages.get('lightbox').then(function (lightbox) {
            var context = lightbox.make();
            clientPackages.get('users').then(function (users) {
                users.currentUser.exists().then(function (exists) {
                    if (exists) {
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