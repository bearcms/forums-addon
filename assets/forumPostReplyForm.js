/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

var bearCMS = bearCMS || {};

bearCMS.forumPostReplyForm = (function () {

    var showUserLoginIfNeeded = function (event) {
        if (typeof ivoPetkov.bearFrameworkAddons !== 'undefined' && typeof ivoPetkov.bearFrameworkAddons.users !== 'undefined') {
            var users = ivoPetkov.bearFrameworkAddons.users;
            if (!users.currentUser.exists()) {
                users.showLogin();
                event.preventDefault();
                return true;
            }
        }
        return false;
    };

    var onBeforeSubmitForm = function (event) {
        if (showUserLoginIfNeeded(event)) {
            return;
        }
        var listElementID = event.target.previousSibling.id;
        event.target.querySelector('input[type="hidden"]').value = JSON.stringify({
            'listElementID': listElementID
        });
    };

    var updateRepliesList = function (result) {
        var listElement = document.getElementById(result.listElementID);
        html5DOMDocument.insert(result.listContent);
        //temp
        listElement.innerHTML = document.body.lastChild.innerHTML;
        document.body.lastChild.parentNode.removeChild(document.body.lastChild);
    }

    var onSubmitFormDone = function (event) {
        var form = event.target;
        var result = event.result;
        if (typeof result.success !== 'undefined') {
            form.reset();
        }
        updateRepliesList(result);
    };


    var onFormRequestSent = function (event) {
        var form = event.target;
        form.querySelector('.bearcms-forum-post-page-send-button').style.display = 'none';
        form.querySelector('.bearcms-forum-post-page-send-button-waiting').style.removeProperty('display');
        form.querySelector('.bearcms-forum-post-page-text-input').setAttribute('readonly', 'readonly');
    };

    var onFormResponseReceived = function (event) {
        var form = event.target;
        form.querySelector('.bearcms-forum-post-page-send-button').style.removeProperty('display');
        form.querySelector('.bearcms-forum-post-page-send-button-waiting').style.display = 'none';
        form.querySelector('.bearcms-forum-post-page-text-input').removeAttribute('readonly');
    };

    var onFocusTextarea = function (event) {
        if(showUserLoginIfNeeded(event)){
            event.target.blur();
        }
        var form = event.target.parentNode;
        if (form.querySelector('.bearcms-forum-post-page-send-button-waiting').style.display === 'none') {
            form.querySelector('.bearcms-forum-post-page-send-button').style.removeProperty('display');
        }
    };

    return {
        'onBeforeSubmitForm': onBeforeSubmitForm,
        'onSubmitFormDone': onSubmitFormDone,
        'onFormRequestSent': onFormRequestSent,
        'onFormResponseReceived': onFormResponseReceived,
        'onFocusTextarea': onFocusTextarea
    }
    ;
}());