<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage local_course_metadata
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/local/course_metadata/indexlib.php');
require_once($CFG->dirroot.'/local/course_metadata/fieldlib.php');
require_once($CFG->dirroot.'/local/course_metadata/definelib.php');
require_once($CFG->dirroot.'/local/course_metadata/hierarchy/lib.php');

require_login();

// $prefix         = required_param('prefix', PARAM_ALPHA);        // hierarchy name or mod name
// Modified for Moodle. Only handling 'course' fields.
$prefix = 'course';
$typeid = 0;
$action         = optional_param('action', '', PARAM_ALPHA);    // param for some action
$id             = optional_param('id', 0, PARAM_INT); // id of a custom field

$sitecontext = context_system::instance();

// use $prefix to determine where to get custom field data from
$tableprefix = $shortprefix = $prefix;
$adminpagename = 'coursecustomfields';

$can_add = has_capability('local/course_metadata:createcoursecustomfield', $sitecontext);
$can_edit = has_capability('local/course_metadata:updatecoursecustomfield', $sitecontext);
$can_delete = has_capability('local/course_metadata:deletecoursecustomfield', $sitecontext);

$PAGE->set_url('/local/course_metadata/index.php');
$PAGE->set_context($sitecontext);
$PAGE->set_pagelayout('admin');

$redirectoptions = array('prefix' => $prefix);
if ($id) {
    $redirectoptions['id'] = $id;
}

$redirect = new moodle_url('/local/course_metadata/index.php', $redirectoptions);

$pagetitle = format_string(get_string('coursecustomfields', 'local_course_metadata'));
$PAGE->navbar->add(get_string('coursecustomfields', 'local_course_metadata'));

$navlinks = $PAGE->navbar->has_items();

admin_externalpage_setup($adminpagename, '', array('prefix' => $prefix));

// check if any actions need to be performed
switch ($action) {
   case 'movefield':
        require_capability('local/course_metadata:update'.$prefix.'customfield', $sitecontext);
        $id  = required_param('id', PARAM_INT);
        $dir = required_param('dir', PARAM_ALPHA);

        if (confirm_sesskey()) {
            customfield_move_field($id, $dir, $tableprefix, $prefix);
        }
        redirect($redirect);
        break;
    case 'deletefield':
        require_capability('local/course_metadata:delete'.$prefix.'customfield', $sitecontext);
        $id      = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', 0, PARAM_BOOL);

        if (data_submitted() and $confirm and confirm_sesskey()) {
            customfield_delete_field($id, $tableprefix);
            redirect($redirect);
        }

        //ask for confirmation
        $datacount = $DB->count_records('local_'.$tableprefix.'_metadata', array('fieldid' => $id));
        switch ($datacount) {
        case 0:
            $deletestr = get_string('confirmfielddeletionnodata', 'local_course_metadata');
            break;
        case 1:
            $deletestr = get_string('confirmfielddeletionsingle', 'local_course_metadata');
            break;
        default:
            $deletestr = get_string('confirmfielddeletionplural', 'local_course_metadata', $datacount);
        }
        $optionsyes = array ('id'=>$id, 'confirm'=>1, 'action'=>'deletefield', 'sesskey'=>sesskey(), 'typeid'=>$typeid);
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('deletefield', 'local_course_metadata'));
        $formcontinue = new single_button(new moodle_url($redirect, $optionsyes), get_string('yes'), 'post');
        $formcancel = new single_button(new moodle_url($redirect, $redirectoptions), get_string('no'), 'get');
        echo $OUTPUT->confirm($deletestr, $formcontinue, $formcancel);
        echo $OUTPUT->footer();
        die;
        break;
    case 'editfield':
        $id       = optional_param('id', 0, PARAM_INT);
        $datatype = optional_param('datatype', '', PARAM_ALPHA);

        if ($id == 0) {
            require_capability('local/course_metadata:create'.$prefix.'customfield', $sitecontext);
        } else {
            require_capability('local/course_metadata:update'.$prefix.'customfield', $sitecontext);
        }

        customfield_edit_field($id, $datatype, $typeid, $redirect, $tableprefix, $prefix, $navlinks);
        die;
        break;
    default:
}

// Display page header.
echo $OUTPUT->header();

// Show tab.
$currenttab = $prefix;
include_once('tabs.php');

$heading = get_string('coursecustomfields', 'local_course_metadata');
echo $OUTPUT->heading($heading);

// show custom fields for the given type
$table = new html_table();
$table->head  = array(get_string('customfield', 'local_course_metadata'));
if ($can_edit || $can_delete) {
    $table->head[] = get_string('edit');
}
$table->id = 'customfields_course';
$table->data = array();

$where = ($typeid) ? array('typeid' => $typeid) : array();
$fields = customfield_get_defined_fields($tableprefix, $where);

$fieldcount = count($fields);

foreach ($fields as $field) {
    $row = array(format_string($field->fullname), get_string('customfieldtype'.$field->datatype, 'local_course_metadata'));
    if ($can_edit || $can_delete) {
        $row[] = customfield_edit_icons($field, $fieldcount, $typeid, $prefix, $can_edit, $can_delete);
    }
    $table->data[] = $row;
}
if (count($table->data)) {
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('nocustomfieldsdefined', 'local_course_metadata'));
}
echo html_writer::empty_tag('br');
// Create a new custom field dropdown menu
$options = customfield_list_datatypes();

if ($can_add) {
    $select = new single_select(new moodle_url('/local/course_metadata/index.php', array('prefix' => $prefix, 'id' => 0, 'action' => 'editfield', 'datatype' => '')), 'datatype', $options, '', array(''=>'choosedots'), 'newfieldform');
    $select->set_label(get_string('createnewcustomfield', 'local_course_metadata'));
    echo $OUTPUT->render($select);
}

echo $OUTPUT->footer();
