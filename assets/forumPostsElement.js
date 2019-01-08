/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

var bearCMS = bearCMS || {};

bearCMS.forumPostsElement = (function () {

    var updateList = function (content, listElement) {
        html5DOMDocument.insert(content, [listElement, 'outerHTML']);
    }

    var loadMore = function (event, data) {
        var listElement = event.target.parentNode.parentNode;
        var requestData = [];
        requestData['serverData'] = data['serverData'];
        ivoPetkov.bearFrameworkAddons.serverRequests.send('bearcms-forumposts-load-more', requestData, function (response) {
            var result = JSON.parse(response);
            updateList(result.content, listElement);
        });
    };

    return {
        'loadMore': loadMore
    };

}());