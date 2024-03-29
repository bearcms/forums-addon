/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

/* global clientPackages */

var bearCMS = bearCMS || {};
bearCMS.forumPostReply = bearCMS.forumPostReply || (function () {

    var temp = [];

    var prepareForUserAction = function (formID) {
        var checkKey = 'ur' + formID;
        if (typeof temp[checkKey] !== 'undefined') {
            return;
        }
        temp[checkKey] = 1;
        var form = document.getElementById(formID);
        clientPackages.get('users').then(function (users) {
            users.currentUser.addEventListener('change', function () {
                updateState(formID, null);
            });
        });
        form.addEventListener('beforesubmit', onBeforeSubmit);
        form.addEventListener('submitsuccess', onSubmitSuccess);
    };

    var initializeForm = function (formID, hasUser) {
        updateState(formID, hasUser);
        if (hasUser) {
            return prepareForUserAction(formID); // return is neededed because of bug in closure compiler
        }
    };

    var updateState = function (formID, hasUser) {
        var update = function (hasCurrentUser) {
            var form = document.getElementById(formID);
            var textarea = form.querySelector('textarea');
            if (hasCurrentUser) {
                textarea.removeAttribute('readonly');
                textarea.style.cursor = "auto";
                textarea.removeEventListener('click', openLogin);
                form.querySelector('[data-form-element-type="submit-button"]').style.removeProperty('display');
            } else {
                textarea.setAttribute('readonly', true);
                textarea.style.cursor = "pointer";
                textarea.addEventListener('click', openLogin);
                form.querySelector('[data-form-element-type="submit-button"]').style.display = 'none';
            }
        };
        if (hasUser !== null) {
            update(hasUser);
        } else {
            clientPackages.get('users').then(function (users) {
                users.currentUser.exists().then(function (exists) {
                    update(exists);
                });
            });
        }
    };

    var openLogin = function (event) {
        var formID = event.target.closest('form').id;
        clientPackages.get('users').then(function (users) {
            prepareForUserAction(formID);
            users.openLogin();
        });
    };

    var updateList = function (result) {
        clientPackages.get('html5DOMDocument').then(function (html5DOMDocument) {
            var listElement = document.getElementById(result.listElementID);
            html5DOMDocument.insert(result.listContent, [listElement, 'outerHTML']);
        });
    };

    var onBeforeSubmit = function (event) {
        var form = event.target;
        var elementContainer = form.previousSibling;
        form.querySelector('[name="fprcontext"]').value = JSON.stringify({
            'listElementID': elementContainer.id
        });
    };

    var onSubmitSuccess = function (event) {
        var form = event.target;
        var result = event.result;
        if (typeof result.success !== 'undefined') {
            form.querySelector('[name="fprtext"]').value = '';
            updateList(result);
        }
    };

    return {
        'initializeForm': initializeForm
    };

}());