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
// Moodle Workplace Code is dual-licensed under the terms of both the
// single GNU General Public Licence version 3.0, dated 29 June 2007
// and the terms of the proprietary Moodle Workplace Licence strictly
// controlled by Moodle Pty Ltd and its certified premium partners.
// Wherever conflicting terms exist, the terms of the MWL are binding
// and shall prevail.

/**
 * External services.
 *
 * @package    format_wplist
 * @copyright  2019 Moodle Pty Ltd <support@moodle.com>
 * @author     2019 <bas@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license    Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(

    'format_wplist_move_section' => array(
        'classpath' => 'course/format/wplist/classes/external.php',
        'classname'   => 'format_wplist_external',
        'methodname'  => 'move_section',
        'description' => 'Move Sections.',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'format_wplist_move_module' => array(
        'classpath' => 'course/format/wplist/classes/external.php',
        'classname'   => 'format_wplist_external',
        'methodname'  => 'move_module',
        'description' => 'Move Modules.',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'format_wplist_module_completion' => array(
        'classpath' => 'course/format/wplist/classes/external.php',
        'classname'   => 'format_wplist_external',
        'methodname'  => 'module_completion',
        'description' => 'Change module completion.',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    )
);

