// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Javascript for course format workplace list.
 *
 * @package    format_wplist
 * @copyright  2010 <bas@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
[
    'jquery',
    'core/sortable_list',
    'core/notification',
    'core/custom_interaction_events',
    'core/ajax',
    'core/log',
    'core/str'
],
function(
    $,
    Sortablewplist,
    Notification,
    CustomEvents,
    Ajax,
    Log,
    Str
) {

    var SELECTORS = {
        SECTION_CONTAINER: '[data-region="section-container"]',
        SECTION: '[data-region="section"]',
        MODULES_CONTAINER: '[data-region="course-modules"]',
        MODULE: '[data-region="module"]',
        COMPLETIONCHECKS: '[data-region="completioncheck"]',
        COMPLETION_ON: '[data-region="checkon"]',
        COMPLETION_OFF: '[data-region="checkoff"]',
        EXPAND_SECTIONS: '[data-action="expand"]',
        EXPAND_SECTIONS_OPEN: '[data-region="collapsed-open"]',
        EXPAND_SECTIONS_CLOSED: '[data-region="collapsed-closed"]',
        COMPLETION_CONTAINER: '[data-actions="availability"]',
        COMPLETION_INFO: '[data-region="availabilityinfo"]'

    };

    /**
     * Move a course section.
     *
     * @param {Object} args Arguments to pass to webservice
     *
     * Valid args are:t
     * int sectionid     id of section to move
     * int sectiontarget id of section to position after
     * int courseid.     id of course this module belongs to
     *
     * @return {promise} Resolved with void or array of warnings
     */
    var moveSection = function(args) {
        var request = {
            methodname: 'format_wplist_move_section',
            args: args
        };
        var promise = Ajax.call([request])[0];
        promise.fail(Notification.exception);
        return promise;
    };

    /**
     * Move a course module.
     *
     * @param {Object} args Arguments to pass to webservice
     *
     * Valid args are:t
     * int moduleid      id of module to move
     * int moduletarget  id of module to position after
     * int sectionid     id of section to move module to
     * int courseid.     id of course this section belongs to
     *
     * @return {promise} Resolved with void or array of warnings
     */
    var moveModule = function(args) {
        var request = {
            methodname: 'format_wplist_move_module',
            args: args
        };
        var promise = Ajax.call([request])[0];
        promise.fail(Notification.exception);
        return promise;
    };

    /**
     * Check the completion checkbox for self-completion.
     *
     * @param {Object} args Arguments to pass to webservice
     *
     * Valid args are:t
     * int moduleid      id of module to complet
     * int targetstate   set completion to on (1) or off (0)
     *
     * @return {promise} Resolved with void or array of warnings
     */
    var checkCompletion = function(args) {
        var request = {
            methodname: 'format_wplist_module_completion',
            args: args
        };
        var promise = Ajax.call([request])[0];
        promise.fail(Notification.exception);
        return promise;
    };

    /**
     * Toggle the completion icon for self completion.
     *
     * @param  {Object} checkbox Container checkbox dom element.
     * @param  {Number} targetstate Value of data-checked
     */
    var checkCompletionIcon = function(checkbox, checked) {
        if (checked == 0) {
            checkbox.attr('data-checked', 1);
            checkbox.attr('data-targetstate', 0);
            checkbox.find(SELECTORS.COMPLETION_ON).removeClass('hidden');
            checkbox.find(SELECTORS.COMPLETION_OFF).addClass('hidden');
        } else {
            checkbox.attr('data-checked', 0);
            checkbox.attr('data-targetstate', 1);
            checkbox.find(SELECTORS.COMPLETION_ON).addClass('hidden');
            checkbox.find(SELECTORS.COMPLETION_OFF).removeClass('hidden');
        }
    };

    /**
     * Update the section completion progress bar.
     *
     * @param {Object} root The course format root container element.
     * @param {Number} sectionid Section ID.
     * @param {Number} targetstate New state.
     */
    var updateSectionCompletion = function(root, sectionid, targetstate) {
        var sectionprogress = root.find('#sectionprogress-' + sectionid);
        var completedmodules = sectionprogress.attr('data-completed-modules');
        var completionmodules = sectionprogress.attr('data-completion-modules');
        if (targetstate == 1) {
            completedmodules++;
        } else {
            completedmodules--;
        }
        sectionprogress.attr('data-completed-modules', completedmodules);
        var newCompletionPercentage = 100 * (completedmodules / completionmodules);
        Log.debug('hello', completedmodules);
        sectionprogress.css('width', newCompletionPercentage + '%');
    };

    /**
     * Listen to, and handle events for the workplace list format.
     *
     * @param {Object} root The course format root container element.
     */
    var registerEventListeners = function(root) {
        CustomEvents.define(root, [
            CustomEvents.events.activate
        ]);

        root.on(CustomEvents.events.activate, SELECTORS.EXPAND_SECTIONS, function(e) {
            var expand = $(e.target).closest(SELECTORS.EXPAND_SECTIONS);
            var openmsg = expand.find(SELECTORS.EXPAND_SECTIONS_OPEN);
            var closedmsg = expand.find(SELECTORS.EXPAND_SECTIONS_CLOSED);
            var open = expand.attr('data-expended');
            if (open == 0) {
                $('[data-region="sectioncollapse"]').each(function() {
                    $(this).addClass('show');
                });
                openmsg.removeClass('hidden');
                closedmsg.addClass('hidden');
                expand.attr('data-expended', 1);
            } else {
                $('[data-region="sectioncollapse"]').each(function() {
                    $(this).removeClass('show');
                });
                openmsg.addClass('hidden');
                closedmsg.removeClass('hidden');
                expand.attr('data-expended', 0);
            }
        });

        // Listen for changes on completion.
        root.on(CustomEvents.events.activate, SELECTORS.COMPLETIONCHECKS, function(e) {
            var cc = $(e.target).closest(SELECTORS.COMPLETIONCHECKS);
            var moduleid = cc.attr('data-module');
            var targetstate = cc.attr('data-targetstate');
            var courseid = cc.attr('data-courseid');
            var checked = cc.attr('data-checked');
            var sectionid = cc.attr('data-sectionid');

            var args = {
                moduleid: moduleid,
                targetstate: targetstate,
                courseid: courseid
            };

            checkCompletion(args).then(function() {
                checkCompletionIcon(cc, checked);
                updateSectionCompletion(root, sectionid, targetstate);
            });
        });

        // Variables for moving sections.
        var sectionsContainer = root.find(SELECTORS.SECTION_CONTAINER);

        var sections = root.find(SELECTORS.SECTION);

        var courseid = sectionsContainer.attr('data-courseid');

        var getSectionName = function(element) {
            return element.find('h3.sectionname .inplaceeditable').text();
        };
        var getModuleName = function(element) {
            return element.find('.cmname .inplaceeditable').text();
        };
        var findClosestSection = function(element) {
            return element.closest('[data-region="section"][data-section]');
        };

        var sectionsSortable = new Sortablewplist(sectionsContainer, {moveHandlerSelector: '.movesection > [data-drag-type=move]'});
        sectionsSortable.getElementName = function(element) {
            return $.Deferred().resolve(getSectionName(element));
        };

        // Variables for moving modules.
        var modulesContainers = root.find(SELECTORS.MODULES_CONTAINER);

        var modulesSortable = new Sortablewplist(modulesContainers, {moveHandlerSelector: '.movemodule > [data-drag-type=move]'});
        modulesSortable.getElementName = function(element) {
            return $.Deferred().resolve(getModuleName(element));
        };
        modulesSortable.getDestinationName = function(parentElement, afterElement) {
            if (!afterElement.length) {
                return Str.get_string('totopofsection', 'moodle',
                        getSectionName(findClosestSection(parentElement)));
            } else {
                return Str.get_string('movecontentafter', 'moodle', getModuleName(afterElement));
            }
        };

        sections.on(Sortablewplist.EVENTS.DROP, function(e, info) {
            e.stopPropagation();
            if (info.positionChanged) {
                if (info.element.attr('data-section')) {
                    if (info.targetNextElement && info.targetNextElement.attr('data-section') === "0") {
                        // Can not move before general section.
                        sectionsSortable.moveElement(info.sourceList, info.sourceNextElement);
                        return;
                    }
                    var sectionid = info.element.attr('data-section');
                    var sectiontarget = info.targetNextElement.attr('data-section');
                    var args = {
                        sectionid: sectionid,
                        sectiontarget: sectiontarget,
                        courseid: courseid
                    };
                    moveSection(args).then(function(result) {
                        info.element.attr('data-section', sectiontarget);
                        info.targetNextElement.attr('data-section', sectionid);
                        Log.debug(result);
                    }).catch(Notification.exception);
                }
                if (info.element.attr('data-module')) {
                    var moduleid = info.element.attr('data-module');
                    var moduletarget = info.targetNextElement.attr('data-module');
                    var sectionid = findClosestSection(info.targetList).attr('data-section');
                    var args = {
                        moduleid: moduleid,
                        moduletarget: moduletarget,
                        sectionid: sectionid,
                        courseid: courseid
                    };
                    moveModule(args).then(function(result) {
                        info.element.attr('data-module', moduletarget);
                        info.targetNextElement.attr('data-module', moduleid);
                        Log.debug(result);
                    }).catch(Notification.exception);
                }
            }
        });
    };

    /**
     * Initialise all of the modules for the workplace list course format.
     *
     * @param {object} root The root element for the workplace list course format.
     */
    var init = function(root) {
        root = $(root);
        registerEventListeners(root);
    };

    return {
        init: init
    };
});