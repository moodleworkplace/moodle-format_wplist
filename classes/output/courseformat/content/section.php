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

namespace format_wplist\output\courseformat\content;

use context_course;
use format_wplist;
use format_wplist_renderer;
use section_info;
use stdClass;

/**
 * Base class to render a course section.
 *
 * @package   format_wplist
 * @copyright 2022 Moodle Pty Ltd <support@moodle.com>
 * @author    2022 Marina Glancy
 * @license   Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */
class section extends \core_courseformat\output\local\content\section {

    /** @var format_wplist the course format */
    protected $format;

    /**
     * Get the name of the template to use for this templatable.
     *
     * @return string
     */
    public function get_template_name(): string {
        return 'format_wplist/local/content/section';
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        return $this->export_section($output) ?? new stdClass();
    }

    /**
     * Export section
     *
     * @param format_wplist_renderer $renderer
     * @return stdClass|null
     */
    protected function export_section(\format_wplist_renderer $renderer): ?stdClass {
        /** @var \section_info $thissection */
        $thissection = $this->thissection;
        $course = $this->format->get_course();
        $context = context_course::instance($course->id);
        $opensections = [];
        $preferences = get_user_preferences('format_wplist_opensections_' . $context->id);
        if ($preferences) {
            $opensections = json_decode($preferences, true);
        }

        $options = $this->format->get_format_options();
        $expandallsections = (bool)$options['sectionstate'];

        $section = $thissection->section;
        $editing = $renderer->page->user_is_editing();

        $sectiontemp = new stdClass();
        foreach ($thissection as $key => $value) {
            $sectiontemp->$key = $value;
        }
        $sectiontemp->sectionnumber = $section;
        if ($section == 0) {
            $sectiontemp->expandbtn = true;
            $sectiontemp->hideexpandcollapse = true;
            $sectiontemp->hideheader = is_null($sectiontemp->name);
            $sectiontemp->expanded = true;
        }
        if ($editing) {
            if ($section > 0) {
                $sectiontemp->move = true;
                $sectiontemp->movetitle = get_string('movesection', 'moodle', $section);
            } else {
                $sectiontemp->moveplaceholder = true;
            }
            $sectiontemp->editsection = $this->edit_section($renderer, $thissection, $course, false);
        } else {
            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display,
            // OR it is hidden but the course has a setting to display hidden sections as unavilable.
            $showsection = $thissection->uservisible ||
                ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo)) ||
                (!$thissection->visible && !$course->hiddensections);
            if (!$showsection) {
                return null;
            }
        }
        $sectiontemp->availabilitymsg = $this->section_availability($renderer);
        $sectiontemp->completion = $renderer->course_section_completion($course, $completioninfo, $section);
        $sectiontemp->sectionname = $this->format->get_section_name($thissection);
        $sectiontemp->name = $renderer->section_title($thissection, $course);
        $sectiontemp->summary = $this->format_summary_text($thissection);
        if ($section != 0) {
            $sectiontemp->expanded = false;
        }
        $expandstate = !is_null($preferences) ? in_array($thissection->id, $opensections) : $expandallsections;
        if ($expandstate) {
            $sectiontemp->expanded = true;
        }
        if ($editing) {
            $sectiontemp->expanded = true;
        }
        if (!$thissection->uservisible) {
            $sectiontemp->expanded = false;
            $sectiontemp->disableexpanding = true;
        }
        if ($sectiontemp->expanded == true) {
            $sectiontemp->toggletitle = get_string('collapsesection', 'format_wplist', $sectiontemp->sectionname);
        } else {
            $sectiontemp->toggletitle = get_string('expandsection', 'format_wplist', $sectiontemp->sectionname);
        }
        $sectiontemp->cmlist = (new $this->cmlistclass($this->format, $thissection))
            ->export_for_template($renderer);

        return $sectiontemp;
    }

    /**
     * Generate the content to displayed on the right part of a section
     * before course modules are included
     *
     * @param format_wplist_renderer $renderer
     * @param stdClass|section_info $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return string HTML to output.
     */
    public function edit_section(format_wplist_renderer $renderer, $section, $course, $onsectionpage) {
        $template = new stdClass();

        $template->addcm = $renderer->course_section_add_cm_control($course, $section->section, 0);

        $controls = $this->section_edit_control_items($renderer, $course, $section, $onsectionpage);
        $template->sectionmenu = $renderer->section_edit_control_menu($controls, $course, $section);

        return $renderer->render_from_template('format_wplist/editsection', $template);
    }

    /**
     * Generate the edit control items of a section
     *
     * This element is now a core_courseformat\output\content\section output component and it is displayed using
     * mustache templates instead of a renderer method.
     *
     * @param format_wplist_renderer $renderer
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of edit control items
     */
    protected function section_edit_control_items(format_wplist_renderer $renderer, $course, $section, $onsectionpage = false) {

        $format = $this->format;
        $modinfo = $format->get_modinfo();

        if ($onsectionpage) {
            $format->set_section_number($section->section);
        }

        // We need a section_info object, not a record.
        $section = $modinfo->get_section_info($section->section);

        $widgetclass = $format->get_output_classname('content\\section\\controlmenu');
        $widget = new $widgetclass($format, $section);
        return $widget->section_control_items();
    }

    /**
     * Generate html for a section summary text
     *
     * @param section_info $section The course_section entry from DB
     * @return string HTML to output.
     */
    protected function format_summary_text(section_info $section) {
        $format = $this->format;
        $summaryclass = $format->get_output_classname('content\\section\\summary');
        $summary = new $summaryclass($format, $section);
        return $summary->format_summary_text();
    }

    /**
     * Displays availability information for the section (hidden, not available unles, etc.)
     *
     * @param format_wplist_renderer $renderer
     * @return string
     */
    public function section_availability(format_wplist_renderer $renderer) {
        global $CFG;
        $section = $this->thissection;
        $context = context_course::instance($section->course);
        $canviewhidden = has_capability('moodle/course:viewhiddensections', $context);
        $o = '';
        if (!$section->visible) {
            if ($canviewhidden) {
                $o .= $renderer->availability_info(get_string('hiddenfromstudents'), 'ishidden');
            } else {
                // We are here because of the setting "Hidden sections are shown in collapsed form".
                // Student can not see the section contents but can see its name.
                $o .= $renderer->availability_info(get_string('notavailable'), 'ishidden');
            }
        } else if (!$section->uservisible) {
            if ($section->availableinfo) {
                // Note: We only get to this function if availableinfo is non-empty,
                // so there is definitely something to print.
                $formattedinfo = \core_availability\info::format_info(
                    $section->availableinfo, $section->course);
                $o .= $renderer->availability_info($formattedinfo, 'isrestricted');
            }
        } else if ($canviewhidden && !empty($CFG->enableavailability)) {
            // Check if there is an availability restriction.
            $ci = new \core_availability\info_section($section);
            $fullinfo = $ci->get_full_information();
            if ($fullinfo) {
                $formattedinfo = \core_availability\info::format_info(
                    $fullinfo, $section->course);
                $o .= $renderer->availability_info($formattedinfo, 'isrestricted isfullinfo');
            }
        }
        return $o;
    }
}
