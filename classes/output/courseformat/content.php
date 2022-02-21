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

namespace format_wplist\output\courseformat;

use context_course;
use format_wplist;
use format_wplist_renderer;
use moodle_url;
use renderer_base;
use stdClass;

/**
 * Format wplist content
 *
 * @package   format_wplist
 * @copyright 2022 Moodle Pty Ltd <support@moodle.com>
 * @author    2022 Marina Glancy
 * @license   Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */
class content extends \core_courseformat\output\local\content {

    /**
     * @var bool Topic format has add section after each topic.
     *
     * The responsible for the buttons is core_courseformat\output\local\content\section.
     */
    protected $hasaddsection = false;

    /**
     * Get the name of the template to use for this templatable.
     *
     * @param \renderer_base $renderer The renderer requesting the template name
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_wplist/local/content';
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        $format = $this->format;
        $course = $format->get_course();

        $data = $this->print_multiple_section_page($output, $course);

        return $data;
    }

    /**
     * Output the html for a multiple section page
     *
     * @param format_wplist_renderer $renderer
     * @param stdClass $course The course entry from DB
     * @return stdClass
     */
    public function print_multiple_section_page(\format_wplist_renderer $renderer, $course) {
        $template = new stdClass();
        $template->courseid = $course->id;
        $template->editing = $renderer->page->user_is_editing();
        $template->editsettingsurl = new moodle_url('/course/edit.php', ['id' => $course->id]);
        $template->enrolusersurl = new moodle_url('/user/index.php', ['id' => $course->id]);
        $template->incourse = true;

        /** @var format_wplist $courseformat */
        $courseformat = course_get_format($course);
        $course = $courseformat->get_course();
        $options = $courseformat->get_format_options();

        $template->sections = [];

        $modinfo = get_fast_modinfo($course);

        $template->accordion = $options['accordioneffect'];
        $template->expandallsections = $options['sectionstate'];
        $context = context_course::instance($course->id);

        $template->contextid = $context->id;

        $template->pagetitle = $renderer->page_title();
        $template->hasclosedsections = false;

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            /** @var format_wplist\output\courseformat\content\section $s */
            $s = new $this->sectionclass($this->format, $thissection);
            if (($data = $s->export_for_template($renderer)) && count((array)$data)) {
                $template->sections[] = $data;
                if (!$data->expanded == true) {
                    $template->hasclosedsections = true;
                }
            }
        }

        if ($template->editing and has_capability('moodle/course:update', $context)) {
            $template->addsection = $renderer->wplist_change_number_sections($course, 0);
        }

        return $template;
    }

}
