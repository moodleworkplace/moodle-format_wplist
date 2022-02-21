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

use format_wplist_renderer;
use renderer_base;
use stdClass;

/**
 * Format wplist cmlist
 *
 * @package   format_wplist
 * @copyright 2022 Moodle Pty Ltd <support@moodle.com>
 * @author    2022 Marina Glancy
 * @license   Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */
class cmlist extends \core_courseformat\output\local\content\section\cmlist {

    /**
     * Get the name of the template to use for this templatable.
     *
     * @param \renderer_base $renderer The renderer requesting the template name
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_wplist/local/content/section/cmlist';
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        return $this->export_cmlist($output, $this->section);
    }

    /**
     * Export cmlist
     *
     * @param format_wplist_renderer $renderer
     * @param \section_info $section
     * @return stdClass
     */
    protected function export_cmlist(format_wplist_renderer $renderer,
                                     \section_info $section): stdClass {
        $format = $this->format;
        $course = $format->get_course();

        $template = new stdClass();

        $template->section = $section->section;

        $modinfo = get_fast_modinfo($course);

        // Check if we are currently in the process of moving a module with JavaScript disabled.
        $template->editing = $renderer->page->user_is_editing();
        $template->ismoving = $template->editing && ismoving($course->id);

        $template->modules = [];
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $modnumber) {
                $mod = $modinfo->cms[$modnumber];
                if (!$mod->is_visible_on_course_page()) {
                    continue;
                }
                $item = new $this->itemclass($format, $section, $mod, $this->displayoptions);
                $template->cms[] = (object)[
                    'cmitem' => $item->export_for_template($renderer),
                    'moveurl' => new \moodle_url('/course/mod.php', array('moveto' => $modnumber, 'sesskey' => sesskey())),
                ];
            }
        } else {
            $template->nomodules = true;
        }
        return $template;
    }
}
