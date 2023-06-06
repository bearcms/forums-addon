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
        clientPackages.get('html5DOMDocument').then(function (html5DOMDocument) {
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

    var openNewPost = function (newPostServerData) {
        clientPackages.get('modalWindows').then(function (modalWindows) {
            modalWindows.showLoading();
            clientPackages.get('users').then(function (users) {
                users.currentUser.exists().then(function (exists) {
                    modalWindows.hideLoading();
                    if (exists) {
                        modalWindows.open('-bearcms-forums-new-post-form', newPostServerData);
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