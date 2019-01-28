(function () {
    'use strict';

    // for IE8
    if (!document.getElementsByClassName) {
        document.getElementsByClassName = function (className) {
            return this.querySelectorAll("." + className);
        };
        Element.prototype.getElementsByClassName = document.getElementsByClassName;
    }

    var
        elActiveClassName = 'active',
        websiteUrl = document.getElementById('website_url').value,
        toasterPanel = document.getElementsByClassName('seotoaster-panel')[0],
        sectionList = document.getElementsByClassName('section-list')[0],
        showHideButton = document.getElementsByClassName('show-hide')[0];

    /**
     * Find and create all path to the event element
     * @param element
     * @returns {Array}
     * @private
     */
    function _getPathToEvent(element) {
        var elementPath = [];

        while (element) {
            elementPath.push(element);
            element = element.parentNode;
        }
        return elementPath;
    }

    /**
     *
     * @param element
     * @private
     */
    function _removeActiveClass(element) {
        if (element != null) {
            element.classList.remove(elActiveClassName);
            localStorage.removeItem('panel-section-active');
        }
    }

    /**
     *
     * @param element
     * @returns {number}
     * @private
     */
    function _getItemIndex(element) {
        var list = _getItems();
        for (var i = 0; i < list.length; i++) {
            if (list[i] == element) {
                return i;
            }
        }
    }

    /**
     *
     * @returns {Array}
     * @private
     */
    function _getItems() {
        var
            list = document.getElementsByClassName('section-list')[0].childNodes,
            result = [];
        for (var i = 0; i < list.length; i++) {
            if (list[i].nodeType == 1) {
                result.push(list[i]);
            }
        }
        return result;
    }

    if (localStorage.getItem('panel-section-active') != null) {
        var list = _getItems();
        list[localStorage.getItem('panel-section-active')].classList.add('active');
    }

    /**
     * Get CMS version
     * @private
     */
    function _getCMSVersion(){
        var xmlHttp = new XMLHttpRequest();
        xmlHttp.open("GET", websiteUrl + '/backend/backend_update/version/', true);
        xmlHttp.onreadystatechange = function () {
            if (xmlHttp.readyState == 4 && xmlHttp.status == 200) {
                var response = JSON.parse(xmlHttp.responseText);
                if (response.status == 1) {
                    document.getElementsByClassName('_i-notification')[0].classList.add('new-notices');
                    document.getElementsByClassName('_new-version')[0].classList.add('show');
                }
            }
        };
        xmlHttp.send(null);
    }


    window.addEventListener('load', function(){
        _getCMSVersion();
    });


    /**
     * Event on the panel item
     */
    sectionList.addEventListener('click', function (event) {
        if(event.target.classList.contains('section-btn')){
            event.preventDefault();
        }

        var item = event.target.parentNode,
            itemWithClass = sectionList.querySelector('.section.active');


        if (event.target.className.indexOf('section-btn') == -1 || event.target.className.indexOf('no-open-section') != -1) {
            // event.preventDefault();
            return false;
        }
        // if haven't class
        if (!item.classList.contains(elActiveClassName)) {
            _removeActiveClass(itemWithClass);
            localStorage.setItem('panel-section-active', _getItemIndex(item));
            item.classList.add(elActiveClassName);
        } else {
            _removeActiveClass(item);
        }

    });

    /**
     * Close the section if we click outside the panel
     */
    document.addEventListener('click', function (event) {
        var path = _getPathToEvent(event.target);

        // We use '-2' because 'window' and 'document' cannot have class
        for (var i = 0; i < path.length - 2; i++) {
            if (path[i].classList.contains('seotoaster-panel')) {
                return false;
            }
        }
        _removeActiveClass(sectionList.querySelector('.section.active'));
    });

    document.getElementsByClassName('show-hide')[0].addEventListener('click', function (event) {
        event.target.classList.toggle('_i-panel-hide');
        event.target.classList.toggle('_i-panel-show');
        toasterPanel.classList.toggle('p-show');
        toasterPanel.classList.toggle('p-hide');
    });

    /**
     * Delete current page
     */
    var elDel = document.getElementById('del-this-page');
    if (elDel)
        elDel.addEventListener('click', function (event) {
            var pId = document.getElementById('del-page-id').value, cId = event.target.getAttribute('data-cid');

            if (cId == 0) {
                var xmlHttp = new XMLHttpRequest();
                xmlHttp.open("GET", websiteUrl + 'backend/backend_page/checkforsubpages/pid/' + pId, false);
                xmlHttp.send(null);
                if (xmlHttp.status == 200) {
                    if (xmlHttp.responseText.subpages) {
                        smoke.alert(xmlHttp.responseText.message, function (e) {
                        }, {'classname': 'warning'});
                        return false;
                    } else {
                        showDelConfirm();
                    }
                }
            } else {
                showDelConfirm();
            }
        });

    /**
     * Check 404 page
     */
    document.getElementById('edit404').addEventListener('click', function () {
        var xmlHttp = new XMLHttpRequest();
        xmlHttp.open("GET", websiteUrl + '/backend/backend_page/edit404page', false);
        xmlHttp.send(null);
        if (xmlHttp.status == 200) {
            var response = JSON.parse(xmlHttp.responseText);
            if (response.notFoundUrl) {
                window.location.href = response.notFoundUrl;
            }
            else {
                smoke.alert(document.getElementsByClassName('edit404-msg')[0].innerHTML, {'classname': 'errors'});
            }
        }
    });

    document.getElementById('cleancache').addEventListener('click', function(e){
        // showMessage('Clearing cache...', false);
        e.target.classList.add('run');

        var xmlHttp = new XMLHttpRequest();
        xmlHttp.open("GET", websiteUrl + '/backend/backend_content/cleancache/', false);
        xmlHttp.send(null);
        var response = JSON.parse(xmlHttp.responseText);
        if (xmlHttp.status == 200) {
            setTimeout(function(){
                e.target.classList.remove('run');
                showMessage(response.responseText, false, 2500);
            }, 2000);

        }else {
            setTimeout(function(){
                e.target.classList.remove('run');
                showMessage(response.responseText, true);
            }, 2000);
        }

    });
})();



$(document).on('click', '#widgets-shortcodes', function(e) {
    window.open($(e.target).data('externalUrl') + 'cheat-sheet.html', '_blank');
});

/**
 *
 */
function showDelConfirm() {
    var pageId = $('#del-page-id').val();
    var websiteUrl = $('#website_url').val();
    smoke.confirm('Are you sure you want to delete this page?', function (e) {
        if (e) {
            $.ajax({
                url       : websiteUrl + 'backend/backend_page/delete/' + 'id/' + pageId,
                type      : 'DELETE',
                dataType  : 'json',
                beforeSend: function () {
                    smoke.signal('Removing page...', 30000);
                },
                success   : function (response) {
                    hideSpinner();
                    if (!response.error) {
                        top.location.href = websiteUrl;
                    }
                    else {
                        smoke.alert(response.responseText.body, {'classname': 'error'});
                    }

                }
            })
        }
    }, {'classname': 'error', 'ok': 'Yes', 'cancel': 'No'});
}