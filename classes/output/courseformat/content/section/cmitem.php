<?php
// This file is part of Moodle Workplace https://moodle.com/workplace based on Moodle
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
//
// Moodle Workplace™ Code is the collection of software scripts
// (plugins and modifications, and any derivations thereof) that are
// exclusively owned and licensed by Moodle under the terms of this
// proprietary Moodle Workplace License ("MWL") alongside Moodle's open
// software package offering which itself is freely downloadable at
// "download.moodle.org" and which is provided by Moodle under a single
// GNU General Public License version 3.0, dated 29 June 2007 ("GPL").
// MWL is strictly controlled by Moodle Pty Ltd and its certified
// premium partners. Wherever conflicting terms exist, the terms of the
// MWL are binding and shall prevail.

namespace format_wplist\output\courseformat\content\section;

use cm_info;
use format_wplist_renderer;
use renderer_base;
use stdClass;

/**
 * Format wplist cmitem
 *
 * @package   format_wplist
 * @copyright 2022 Moodle Pty Ltd <support@moodle.com>
 * @author    2022 Marina Glancy
 * @license   Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */
class cmitem extends \core_courseformat\output\local\content\section\cmitem {

    /**
     * Get the name of the template to use for this templatable.
     *
     * @param \renderer_base $renderer The renderer requesting the template name
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_wplist/local/content/section/cmitem';
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        return $this->export_cmitem($output);
    }

    /**
     * Export cmitem
     *
     * @param format_wplist_renderer $renderer
     * @param int|null $sectionreturn
     * @return stdClass
     */
    protected function export_cmitem(\format_wplist_renderer $renderer, ?int $sectionreturn = null): stdClass {
        $mod = $this->mod;
        $course = $this->format->get_course();
        $displayoptions = $this->displayoptions;

        $template = new stdClass();
        $template->mod = $mod;
        $template->text = $mod->get_formatted_content(array('overflowdiv' => false, 'noclean' => true));
        $template->completion = $renderer->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions);
        $template->activityinfo = $renderer->course_section_cm_activity_info($course, $mod);
        $template->cmname = $this->course_section_cm_name($renderer, $mod, $displayoptions);
        $template->editing = $renderer->page->user_is_editing();
        $template->availability = $this->course_section_cm_availability($renderer);

        if ($template->editing) {
            $editactions = course_get_cm_edit_actions($mod, $mod->indent, $sectionreturn);
            $template->editoptions = $renderer->course_section_cm_edit_actions($editactions, $mod, $displayoptions);
            $template->editoptions .= $mod->afterediticons;
            $template->moveicons = $renderer->course_get_cm_move($mod, $sectionreturn);
        }

        return $template;
    }

    /**
     * Renders html to display a name with the link to the course module on a course page
     *
     * If module is unavailable for user but still needs to be displayed
     * in the list, just the name is returned without a link
     *
     * Note, that for course modules that never have separate pages (i.e. labels)
     * this function return an empty string
     *
     * @param format_wplist_renderer $renderer
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_name(\format_wplist_renderer $renderer, cm_info $mod, $displayoptions = array()) {

        if (!$mod->is_visible_on_course_page() || !$mod->url) {
            // Nothing to be displayed to the user.
            return '';
        }

        list($linkclasses, $textclasses) = $this->course_section_cm_classes($renderer, $mod);
        $groupinglabel = $mod->get_grouping_label($textclasses);

        if (!isset($displayoptions['linkclasses']) || !isset($displayoptions['textclasses'])) {
            $displayoptions['linkclasses'] = $linkclasses;
            $displayoptions['textclasses'] = $textclasses;
        }

        // Render element that allows to edit activity name inline.
        $format = course_get_format($mod->course);
        $cmnameclass = $format->get_output_classname('content\\cm\\cmname');
        // Mod inplace name editable.
        $cmname = new $cmnameclass(
            $format,
            $mod->get_section_info(),
            $mod,
            $renderer->page->user_is_editing(),
            $displayoptions
        );

        $data = $cmname->export_for_template($renderer);

        return $renderer->render_from_template('core/inplace_editable', $data) .
            $groupinglabel;
    }

    /**
     * Checks if course module has any conditions that may make it unavailable for
     * all or some of the students
     *
     * This function is internal and is only used to create CSS classes for the module name/text
     *
     * @param cm_info $mod
     * @return bool
     */
    protected function is_cm_conditionally_hidden(cm_info $mod) {
        global $CFG;
        $conditionalhidden = false;
        if (!empty($CFG->enableavailability)) {
            $info = new \core_availability\info_module($mod);
            $conditionalhidden = !$info->is_available_for_all();
        }
        return $conditionalhidden;
    }

    /**
     * Returns the CSS classes for the activity name/content
     *
     * For items which are hidden, unavailable or stealth but should be displayed
     * to current user ($mod->is_visible_on_course_page()), we show those as dimmed.
     * Students will also see as dimmed activities names that are not yet available
     * but should still be displayed (without link) with availability info.
     *
     * @param format_wplist_renderer $renderer
     * @param cm_info $mod
     * @return array array of two elements ($linkclasses, $textclasses)
     */
    protected function course_section_cm_classes(format_wplist_renderer $renderer, cm_info $mod) {
        $linkclasses = '';
        $textclasses = '';
        if ($mod->uservisible) {
            $conditionalhidden = $this->is_cm_conditionally_hidden($mod);
            $accessiblebutdim = (!$mod->visible || $conditionalhidden) &&
                has_capability('moodle/course:viewhiddenactivities', $mod->context);
            if ($accessiblebutdim) {
                $linkclasses .= ' dimmed';
                $textclasses .= ' dimmed_text';
                if ($conditionalhidden) {
                    $linkclasses .= ' conditionalhidden';
                    $textclasses .= ' conditionalhidden';
                }
            }
            if ($mod->is_stealth()) {
                // Stealth activity is the one that is not visible on course page.
                // It still may be displayed to the users who can manage it.
                $linkclasses .= ' stealth';
                $textclasses .= ' stealth';
            }
        } else {
            $linkclasses .= ' dimmed';
            $textclasses .= ' dimmed dimmed_text';
        }
        return array($linkclasses, $textclasses);
    }

    /**
     * Renders HTML to show course module availability information (for someone who isn't allowed
     * to see the activity itself, or for staff)
     *
     * @param format_wplist_renderer $renderer
     * @return string
     */
    public function course_section_cm_availability(format_wplist_renderer $renderer) {
        global $CFG;
        $mod = $this->mod;
        $output = '';
        if (!$mod->is_visible_on_course_page()) {
            return $output;
        }
        if (!$mod->uservisible) {
            // This is a student who is not allowed to see the module but might be allowed
            // to see availability info (i.e. "Available from ...").
            if (!empty($mod->availableinfo)) {
                $formattedinfo = \core_availability\info::format_info(
                    $mod->availableinfo, $mod->get_course());
                $output = $renderer->availability_info($formattedinfo, 'isrestricted');
            }
            return $output;
        }
        // This is a teacher who is allowed to see module but still should see the
        // information that module is not available to all/some students.
        $modcontext = \context_module::instance($mod->id);
        $canviewhidden = has_capability('moodle/course:viewhiddenactivities', $modcontext);
        if ($canviewhidden && !$mod->visible) {
            // This module is hidden but current user has capability to see it.
            // Do not display the availability info if the whole section is hidden.
            if ($mod->get_section_info()->visible) {
                $output .= $renderer->availability_info(get_string('hiddenfromstudents'), 'ishidden');
            }
        } else if ($mod->is_stealth()) {
            // This module is available but is normally not displayed on the course page
            // (this user can see it because they can manage it).
            $output .= $renderer->availability_info(get_string('hiddenoncoursepage'), 'isstealth');
        }
        if ($canviewhidden && !empty($CFG->enableavailability)) {
            // Display information about conditional availability.
            // Don't add availability information if user is not editing and activity is hidden.
            if ($mod->visible || $renderer->page->user_is_editing()) {
                $hidinfoclass = 'isrestricted isfullinfo';
                if (!$mod->visible) {
                    $hidinfoclass .= ' hide';
                }
                $ci = new \core_availability\info_module($mod);
                $fullinfo = $ci->get_full_information();
                if ($fullinfo) {
                    $formattedinfo = \core_availability\info::format_info(
                        $fullinfo, $mod->get_course());
                    $output .= $renderer->availability_info($formattedinfo, $hidinfoclass);
                }
            }
        }
        return $output;
    }
}
