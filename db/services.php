<?php
// This file is part of the format_wplist plugin for Moodle - http://moodle.org/
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
 * External services.
 *
 * @package    format_wplist
 * @copyright  2019 Moodle Pty Ltd <support@moodle.com>
 * @author     2019 <bas@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    'format_wplist_move_section' => [
        'classpath' => 'course/format/wplist/classes/external.php',
        'classname'   => 'format_wplist_external',
        'methodname'  => 'move_section',
        'description' => 'Move Sections.',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'format_wplist_move_module' => [
        'classpath' => 'course/format/wplist/classes/external.php',
        'classname'   => 'format_wplist_external',
        'methodname'  => 'move_module',
        'description' => 'Move Modules.',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'format_wplist_module_completion' => [
        'classpath' => 'course/format/wplist/classes/external.php',
        'classname'   => 'format_wplist_external',
        'methodname'  => 'module_completion',
        'description' => 'Change module completion.',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];

