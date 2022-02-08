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

/**
 * Main renderer
 *
 * @package    format_wplist
 * @copyright  2019 Moodle Pty Ltd <support@moodle.com>
 * @author     2019 <bas@moodle.com>
 * @license    Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */


use core_course\output\activity_information;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/renderer.php');

/**
 * Basic renderer for wplist format.
 *
 * @property-read moodle_page $page
 * @property-read \core_course_renderer $courserenderer
 * @package    format_wplist
 * @copyright  2019 Moodle Pty Ltd <support@moodle.com>
 * @author     2019 <bas@moodle.com>
 * @license    Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */
class format_wplist_renderer extends core_courseformat\output\section_renderer {

    /**
     * Constructor method, calls the parent constructor
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Magic getter
     *
     * @param string $name
     * @return core_course_renderer|moodle_page|null
     */
    public function __get($name) {
        if ($name === 'page') {
            return $this->page;
        }
        if ($name === 'courserenderer') {
            return $this->courserenderer;
        }
        return null;
    }

    /**
     * Generate the starting container html for a wplist of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return '';
    }

    /**
     * Generate the closing container html for a wplist of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return '';
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title() {
        return get_string('topicoutline');
    }

    /**
     * Generate the content to displayed on the right part of a section
     * before course modules are included
     *
     * @param stdClass|section_info $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return string HTML to output.
     */
    public function edit_section($section, $course, $onsectionpage) {
        $template = new stdClass();

        $template->addcm = $this->course_section_add_cm_control($course, $section->section, 0);

        $controls = $this->section_edit_control_items($course, $section, $onsectionpage);
        $template->sectionmenu = $this->section_edit_control_menu($controls, $course, $section);

        return $this->render_from_template('format_wplist/editsection', $template);
    }

    /**
     * Generate the edit control action menu
     *
     * @param array $controls The edit control items from section_edit_control_items
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @return string HTML to output.
     */
    public function section_edit_control_menu($controls, $course, $section) {
        $o = "";
        if (!empty($controls)) {
            $menu = new action_menu();
            $menu->set_menu_trigger($this->output->pix_icon('i/settings', '', 'core'));
            $menu->attributes['class'] .= ' section-actions';
            foreach ($controls as $value) {
                $url = empty($value['url']) ? '' : $value['url'];
                $icon = empty($value['icon']) ? '' : $value['icon'];
                $name = empty($value['name']) ? '' : $value['name'];
                $attr = empty($value['attr']) ? array() : $value['attr'];
                $class = empty($value['pixattr']['class']) ? '' : $value['pixattr']['class'];
                $alt = empty($value['pixattr']['alt']) ? '' : $value['pixattr']['alt'];
                $al = new action_menu_link_secondary(
                    new moodle_url($url),
                    new pix_icon($icon, $alt, null, array('class' => "smallicon " . $class)),
                    $name,
                    $attr
                );
                $menu->add($al);
            }

            $o .= html_writer::div($this->render($menu), 'section_action_menu',
                array('data-sectionid' => $section->id, 'title' => get_string('edit', 'moodle')));
        }

        return $o;
    }

    /**
     * Renders HTML for the menus to add activities and resources to the current course
     *
     * @param stdClass $course
     * @param int $section relative section number (field course_sections.section)
     * @param int $sectionreturn The section to link back to
     * @param array $displayoptions additional display options, for example blocks add
     *     option 'inblock' => true, suggesting to display controls vertically
     * @return string
     */
    public function course_section_add_cm_control($course, $section, $sectionreturn = null, $displayoptions = array()) {
        if ($course->id == $this->page->course->id) {
            $straddeither = get_string('addresourceoractivity');
            $ajaxcontrol = html_writer::start_tag('div', ['class' => 'mdl-right']);
            $ajaxcontrol .= html_writer::start_tag('div', ['class' => 'section-modchooser']);
            $icon = $this->output->pix_icon('plus-circle', $straddeither, 'tool_wp');
            $ajaxcontrol .= html_writer::tag('button', $icon, [
                    'class' => 'section-modchooser-link btn btn-link pt-0',
                    'data-action' => 'open-chooser',
                    'data-sectionid' => $section,
                ]
            );
            $ajaxcontrol .= html_writer::end_tag('div');
            $ajaxcontrol .= html_writer::end_tag('div');

            $this->course_activitychooser($course->id);
        }
        return $ajaxcontrol ?? '';
    }

    /**
     * Displays availability information for the section (hidden, not available unles, etc.)
     *
     * @param section_info $section
     * @return string
     */
    public function section_availability($section) {
        $context = context_course::instance($section->course);
        $canviewhidden = has_capability('moodle/course:viewhiddensections', $context);
        $message = $this->section_availability_message($section, $canviewhidden);
        return $message ? html_writer::div($message) : null;
    }

    /**
     * Generate the section title, wraps it in a link to the section page if page is to be displayed on a separate page
     *
     * @param stdClass|section_info $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title($section, $course) {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section, false));
    }

    /**
     * Render the course module move icon.
     *
     * @param  cm_info $mod
     * @param  int $sr Section return ID
     * @return String HTML to be returned
     */
    public function course_get_cm_move(cm_info $mod, $sr = null) {
        $template = new stdClass();

        $modcontext = context_module::instance($mod->id);
        $hasmanageactivities = has_capability('moodle/course:manageactivities', $modcontext);

        $template->movetitle = get_string('movecoursemodule', 'moodle');

        if ($hasmanageactivities) {
            return $this->render_from_template('format_wplist/movecoursemodule', $template);
        }
        return '';
    }

    /**
     * calculates and renders the section completion progressbar
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param int $section Section number
     */
    public function course_section_completion($course, &$completioninfo, $section) {
        $template = new stdClass();

        $template->sectionnumber = $section;

        $completionok = array(COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS);
        $completionfail = array(COMPLETION_COMPLETE_FAIL, COMPLETION_INCOMPLETE);
        $output = '';
        $modinfo = get_fast_modinfo($course);
        if ($completioninfo === null) {
            $completioninfo = new completion_info($course);
        }

        $completionmodcount = 0;
        $completedmodscount = 0;
        if (!isset($modinfo->sections[$section])) {
            return '';
        }
        foreach ($modinfo->sections[$section] as $modnumber) {
            $mod = $modinfo->cms[$modnumber];
            $completion = $completioninfo->is_enabled($mod);
            if ($completion == COMPLETION_TRACKING_NONE) {
                continue;
            }
            $completionmodcount++;
            $completiondata = $completioninfo->get_data($mod, true);
            if (in_array($completiondata->completionstate, $completionok)) {
                $completedmodscount++;
            }
        }

        if ($completionmodcount > 0) {
            $template->hascompletion = true;
            $template->percentagecompleted = round(100 * ($completedmodscount / $completionmodcount));
            $template->completionmodcount = $completionmodcount;
            $template->completedmodscount = $completedmodscount;
        }
        return $this->render_from_template('format_wplist/sectioncompletion', $template);
    }

    /**
     * Renders html for completion box on course page
     *
     * If completion is disabled, returns empty string
     * If completion is automatic, returns an icon of the current completion state
     * If completion is manual, returns a form (with an icon inside) that allows user to
     * toggle completion
     *
     * @param stdClass $course course object
     * @param completion_info $completioninfo completion info for the course, it is recommended
     *     to fetch once for all modules in course/section for performance
     * @param cm_info $mod module to show completion for
     * @param array $displayoptions display options, not used in core
     * @return string
     */
    public function course_section_cm_completion($course, &$completioninfo, cm_info $mod, $displayoptions = array()) {
        global $CFG, $USER;

        if (!empty($displayoptions['hidecompletion']) || !isloggedin() || isguestuser() || !$mod->uservisible) {
            return "";
        }
        if ($completioninfo === null) {
            $completioninfo = new completion_info($course);
        }
        $completion = $completioninfo->is_enabled($mod);

        if ($completion == COMPLETION_TRACKING_NONE) {
            return "";
        }

        $isediting = $this->page->user_is_editing();
        $istrackeduser = $completioninfo->is_tracked_user($USER->id);

        $completionicon = '';

        $completiondata = $completioninfo->get_data($mod, true);
        if ($isediting) {
            switch ($completion) {
                case COMPLETION_TRACKING_MANUAL :
                    $completionicon = 'manual-enabled';
                    break;
                case COMPLETION_TRACKING_AUTOMATIC :
                    $completionicon = 'auto-enabled';
                    break;
            }
        } else {
            if ($completion == COMPLETION_TRACKING_MANUAL) {
                switch($completiondata->completionstate) {
                    case COMPLETION_INCOMPLETE:
                        $completionicon = 'manual-n' . ($completiondata->overrideby ? '-override' : '');
                        break;
                    case COMPLETION_COMPLETE:
                        $completionicon = 'manual-y' . ($completiondata->overrideby ? '-override' : '');
                        break;
                }
            } else {
                switch($completiondata->completionstate) {
                    case COMPLETION_INCOMPLETE:
                        $completionicon = 'auto-n' . ($completiondata->overrideby ? '-override' : '');
                        break;
                    case COMPLETION_COMPLETE:
                        $completionicon = 'auto-y' . ($completiondata->overrideby ? '-override' : '');
                        break;
                    case COMPLETION_COMPLETE_PASS:
                        $completionicon = 'auto-pass';
                        break;
                    case COMPLETION_COMPLETE_FAIL:
                        $completionicon = 'auto-fail';
                        break;
                }
            }
        }
        $template = new stdClass();
        $template->sectionnumber = $mod->get_section_info()->section;
        $template->mod = $mod;
        $template->completionicon = $completionicon;
        $template->courseid = $course->id;

        if ($completionicon) {
            $formattedname = $mod->get_formatted_name(['escape' => false]);
            $template->hascompletion = true;

            if ($isediting) {
                $template->editing = true;
            }

            if (\core_availability\info::completion_value_used($course, $mod->id)) {
                $template->reloadonchange = true;
            }

            if (!$isediting && $istrackeduser && $completiondata->overrideby) {
                $args = new stdClass();
                $args->modname = $formattedname;
                $overridebyuser = \core_user::get_user($completiondata->overrideby, '*', MUST_EXIST);
                $args->overrideuser = fullname($overridebyuser);
                $imgalt = get_string('completion-alt-' . $completionicon, 'completion', $args);
            } else {
                $imgalt = get_string('completion-alt-' . $completionicon, 'completion', $formattedname);
            }
            $template->imgalt = $imgalt;

            if ($completion == COMPLETION_TRACKING_MANUAL) {
                $template->self = true;
                $template->newstate =
                    $completiondata->completionstate == COMPLETION_COMPLETE
                    ? COMPLETION_INCOMPLETE
                    : COMPLETION_COMPLETE;

                if ($completiondata->completionstate == COMPLETION_COMPLETE) {
                    $template->checked = true;
                }
            } else {
                $template->auto = true;
                if ($completionicon == 'auto-y' || $completionicon == 'auto-pass') {
                    $template->checked = true;
                }
            }
        }
        return $this->render_from_template('format_wplist/completionicon', $template);
    }

    /**
     * Renders HTML for displaying the sequence of course module editing buttons
     *
     * @see course_get_cm_edit_actions()
     *
     * @param action_link[] $actions Array of action_link objects
     * @param cm_info $mod The module we are displaying actions for.
     * @param array $displayoptions additional display options:
     *     ownerselector => A JS/CSS selector that can be used to find an cm node.
     *         If specified the owning node will be given the class 'action-menu-shown' when the action
     *         menu is being displayed.
     *     constraintselector => A JS/CSS selector that can be used to find the parent node for which to constrain
     *         the action menu to when it is being displayed.
     *     donotenhance => If set to true the action menu that gets displayed won't be enhanced by JS.
     * @return string
     */
    public function course_section_cm_edit_actions($actions, cm_info $mod = null, $displayoptions = array()) {
        global $CFG;

        if (empty($actions)) {
            return '';
        }

        $template = new stdClass();
        $template->controls = [];

        foreach ($actions as $action) {
            if ($action instanceof action_menu_link) {
                $action->add_class('cm-edit-action');
            }
        }

        foreach ($actions as $key => $action) {
            if ($key === 'moveright' || $key === 'moveleft' ) {
                continue;
            }
            if (empty($action->url)) {
                continue;
            }

            $control = new stdClass();
            $control->icon = $action->icon;
            $control->attributes = '';
            if (is_array($action->attributes)) {
                foreach ($action->attributes as $name => $value) {
                    $control->attributes .= s($name) . '="' . s($value) . '"';
                }
            }
            $control->url = $action->url->out(false);
            $control->string = $action->text;
            $template->controls[] = $control;
        }

        return $this->render_from_template('format_wplist/editactivity', $template);
    }

    /**
     * Renders HTML for displaying the activity information
     *
     * @param stdClass $course
     * @param cm_info $mod
     * @return string
     */
    public function course_section_cm_activity_info(stdClass $course, cm_info $mod): string {
        global $USER;

        // Fetch completion details.
        $showcompletionconditions = $course->showcompletionconditions == COMPLETION_SHOW_CONDITIONS;
        $completiondetails = \core_completion\cm_completion_details::get_instance($mod, $USER->id, $showcompletionconditions);

        // Fetch activity dates.
        $activitydates = [];
        if ($course->showactivitydates) {
            $activitydates = \core\activity_dates::get_dates_for_module($mod, $USER->id);
        }

        if ($showcompletionconditions || $activitydates) {
            $activityinfo = new activity_information($mod, $completiondetails, $activitydates);
            /** @var core_course_renderer $renderer */
            $renderer = $this->page->get_renderer('core', 'course');
            $context = $activityinfo->export_for_template($renderer);
            // Override "showmanualcompletion" to false. Never show "Mark as complete" button.
            $context->showmanualcompletion = false;
            return $renderer->render_from_template('core_course/activity_info', $context);
        } else {
            return '';
        }
    }

    /**
     * Returns controls in the bottom of the page to increase/decrease number of sections
     *
     * @param stdClass $course
     * @param int|null $sectionreturn
     * @return string
     */
    public function wplist_change_number_sections($course, $sectionreturn = null) {
        $coursecontext = context_course::instance($course->id);
        if (!has_capability('moodle/course:update', $coursecontext)) {
            return '';
        }

        $format = course_get_format($course);
        $maxsections = $format->get_max_sections();
        $lastsection = $format->get_last_section_number();

        if ($lastsection >= $maxsections) {
            return '';
        }

        $template = new stdClass();
        $url = new moodle_url('/course/changenumsections.php',
            ['courseid' => $course->id, 'insertsection' => 0, 'sesskey' => sesskey()]);

        if ($sectionreturn !== null) {
            $url->param('sectionreturn', $sectionreturn);
        }
        $template->url = $url->out(false);
        $template->attributes = [['name' => 'new-sections', 'value' => $maxsections - $lastsection]];

        return $this->render_from_template('format_wplist/change_number_sections', $template);
    }

    /**
     * Displays availability info for a course section or course module
     *
     * @param string $text
     * @param string $additionalclasses
     * @return string
     */
    public function availability_info($text, $additionalclasses = '') {

        $data = ['text' => $text, 'classes' => $additionalclasses];
        $additionalclasses = array_filter(explode(' ', $additionalclasses));

        if (in_array('ishidden', $additionalclasses)) {
            $data['ishidden'] = 1;

        } else if (in_array('isstealth', $additionalclasses)) {
            $data['isstealth'] = 1;

        } else if (in_array('isrestricted', $additionalclasses)) {
            $data['isrestricted'] = 1;

            if (in_array('isfullinfo', $additionalclasses)) {
                $data['isfullinfo'] = 1;
            }
        }

        return $this->render_from_template('core/availability_info', $data);
    }
}
