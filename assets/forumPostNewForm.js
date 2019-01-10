/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

var bearCMS = bearCMS || {};

bearCMS.forumPostNewForm = (function () {

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
    };

    var onSubmitFormDone = function (event) {
        var form = event.target;
        var result = event.result;
        if (typeof result.success !== 'undefined') {
            form.reset();
        }
        if (typeof result.redirectUrl !== 'undefined') {
            window.location = result.redirectUrl;
        }
    };

    var onFormRequestSent = function (event) {
        var form = event.target;
        form.querySelector('.bearcms-new-forum-post-page-send-button').style.display = 'none';
        form.querySelector('.bearcms-new-forum-post-page-send-button-waiting').style.removeProperty('display');
        form.querySelector('.bearcms-new-forum-post-page-text-input').setAttribute('readonly', 'readonly');
    };

    var onFormResponseReceived = function (event) {
        var form = event.target;
        form.querySelector('.bearcms-new-forum-post-page-send-button').style.removeProperty('display');
        form.querySelector('.bearcms-new-forum-post-page-send-button-waiting').style.display = 'none';
        form.querySelector('.bearcms-new-forum-post-page-text-input').removeAttribute('readonly');
    };

    var onFocusTextarea = function (event) {
        if(showUserLoginIfNeeded(event)){
            event.target.blur();
        }
    };

    var onFocusTitle = function (event) {
        if(showUserLoginIfNeeded(event)){
            event.target.blur();
        }
    };

    return {
        'onBeforeSubmitForm': onBeforeSubmitForm,
        'onSubmitFormDone': onSubmitFormDone,
        'onFormRequestSent': onFormRequestSent,
        'onFormResponseReceived': onFormResponseReceived,
        'onFocusTextarea': onFocusTextarea,
        'onFocusTitle': onFocusTitle
    };

}());