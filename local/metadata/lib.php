<?php
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
 * @package local_metadata
 * @author Mike Churchward <mike.churchward@poetgroup.org>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2016 The POET Group
 */
/**
 * Loads user profile field data into the user object.
 * @param stdClass $user
 */
function local_metadata_load_data($instance, $contextlevel) {
    global $DB;

    if ($fields = $DB->get_records('local_metadata_field', ['contextlevel' => $contextlevel])) {
        foreach ($fields as $field) {
            $newfield = "\\local_metadata\\fieldtype\\{$field->datatype}\\fieldtype";
            $formfield = new $newfield($field->id, $instance->id);
            $formfield->edit_load_instance_data($instance);
        }
    }
}

/**
 * Print out the customisable categories and fields for a users profile
 *
 * @param moodleform $mform instance of the moodleform class
 * @param int $instanceid id of user whose profile is being edited.
 */
function local_metadata_definition($mform, $instanceid = 0, $contextlevel) {
    global $DB;

    // If user is "admin" fields are displayed regardless.
    $update = has_capability('moodle/user:update', context_system::instance());

    if ($categories = $DB->get_records('local_metadata_category', ['contextlevel' => $contextlevel], 'sortorder ASC')) {
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('local_metadata_field', ['categoryid' => $category->id], 'sortorder ASC')) {

                // Check first if *any* fields will be displayed.
                $display = false;
                foreach ($fields as $field) {
                    if ($field->visible != PROFILE_VISIBLE_NONE) {
                        $display = true;
                    }
                }

                // Display the header and the fields.
                if ($display or $update) {
                    $mform->addElement('header', 'category_'.$category->id, format_string($category->name));
                    foreach ($fields as $field) {
                        $newfield = "\\local_metadata\\fieldtype\\{$field->datatype}\\fieldtype";
                        $formfield = new $newfield($field->id, $instanceid);
                        $formfield->edit_field($mform);
                    }
                }
            }
        }
    }
}

/**
 * Adds profile fields to instance edit forms.
 * @param moodleform $mform
 * @param int $instanceid
 */
function local_metadata_definition_after_data($mform, $instanceid, $contextlevel) {
    global $DB;

    $instanceid = ($instanceid < 0) ? 0 : (int)$instanceid;

    if ($fields = $DB->get_records('local_metadata_field', ['contextlevel' => $contextlevel])) {
        foreach ($fields as $field) {
            $newfield = "\\local_metadata\\fieldtype\\{$field->datatype}\\fieldtype";
            $formfield = new $newfield($field->id, $instanceid);
            $formfield->edit_after_data($mform);
        }
    }
}

/**
 * Validates profile data.
 * @param stdClass $new
 * @param array $files
 * @return array
 */
function local_metadata_validation($new, $files, $contextlevel) {
    global $DB;

    if (is_array($new)) {
        $new = (object)$new;
    }

    $err = array();
    if ($fields = $DB->get_records('local_metadata_field', ['contextlevel' => $contextlevel])) {
        foreach ($fields as $field) {
            $newfield = "\\local_metadata\\fieldtype\\{$field->datatype}\\fieldtype";
            $formfield = new $newfield($field->id, $new->id);
            $err += $formfield->edit_validate_field($new, $files);
        }
    }
    return $err;
}

/**
 * Saves profile data for a instance.
 * @param stdClass $new
 */
function local_metadata_save_data($new, $contextlevel) {
    global $DB;

    if ($fields = $DB->get_records('local_metadata_field', ['contextlevel' => $contextlevel])) {
        foreach ($fields as $field) {
            $newfield = "\\local_metadata\\fieldtype\\{$field->datatype}\\fieldtype";
            $formfield = new $newfield($field->id, $new->id);
            $formfield->edit_save_data($new);
        }
    }
}

/**
 * Display profile fields.
 * @param int $instanceid
 */
function local_metadata_display_fields($instanceid, $contextlevel) {
    global $DB;

    if ($categories = $DB->get_records('local_metadata_category', ['contextlevel' => $contextlevel], 'sortorder ASC')) {
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('local_metadata_field', ['categoryid' => $category->id], 'sortorder ASC')) {
                foreach ($fields as $field) {
                    $newfield = "\\local_metadata\\fieldtype\\{$field->datatype}\\fieldtype";
                    $formfield = new $newfield($field->id, $instanceid);
                    if ($formfield->is_visible() and !$formfield->is_empty()) {
                        echo html_writer::tag('dt', format_string($formfield->field->name));
                        echo html_writer::tag('dd', $formfield->display_data());
                    }
                }
            }
        }
    }
}

/**
 * Retrieves a list of profile fields that must be displayed in the sign-up form.
 * Specific to user profiles.
 *
 * @return array list of profile fields info
 * @since Moodle 3.2
 */
function local_metadata_get_signup_fields() {
    global $DB;

    $profilefields = [];
    // Only retrieve required custom fields (with category information)
    // results are sort by categories, then by fields.
    $sql = "SELECT uf.id as fieldid, ic.id as categoryid, ic.name as categoryname, uf.datatype
                FROM {local_metadata_field} uf
                JOIN {local_metadata_category} ic
                ON uf.categoryid = ic.id AND uf.signup = 1 AND uf.visible<>0
                WHERE uf.contextlevel = ?
                ORDER BY ic.sortorder ASC, uf.sortorder ASC";

    if ($fields = $DB->get_records_sql($sql, [CONTEXT_USER])) {
        foreach ($fields as $field) {
            $newfield = "\\local_metadata\\fieldtype\\{$field->datatype}\\fieldtype";
            $fieldobject = new $newfield($field->fieldid);

            $profilefields[] = (object) array(
                'categoryid' => $field->categoryid,
                'categoryname' => $field->categoryname,
                'fieldid' => $field->fieldid,
                'datatype' => $field->datatype,
                'object' => $fieldobject
            );
        }
    }
    return $profilefields;
}

/**
 * Adds code snippet to a moodle form object for custom profile fields that
 * should appear on the signup page
 * Specific to user profiles.
 * @param moodleform $mform moodle form object
 */
function local_metadata_signup_fields($mform) {

    if ($fields = local_metadata_get_signup_fields()) {
        foreach ($fields as $field) {
            // Check if we change the categories.
            if (!isset($currentcat) || $currentcat != $field->categoryid) {
                 $currentcat = $field->categoryid;
                 $mform->addElement('header', 'category_'.$field->categoryid, format_string($field->categoryname));
            };
            $field->object->edit_field($mform);
        }
    }
}

/**
 * Returns an object with the custom profile fields set for the given user
 * Specific to user profiles.
 * @param integer $instanceid
 * @param bool $onlyinuserobject True if you only want the ones in $USER.
 * @return stdClass
 */
function local_metadata_user_record($instanceid, $onlyinuserobject = true) {
    global $DB;

    $usercustomfields = new \stdClass();

    if ($fields = $DB->get_records('local_metadata_field', ['contextlevel' => CONTEXT_USER])) {
        foreach ($fields as $field) {
            $newfield = "\\local_metadata\\fieldtype\\{$field->datatype}\\fieldtype";
            $formfield = new $newfield($field->id, $instanceid);
            if (!$onlyinuserobject || $formfield->is_instance_object_data()) {
                $usercustomfields->{$field->shortname} = $formfield->data;
            }
        }
    }

    return $usercustomfields;
}

/**
 * Obtains a list of all available custom profile fields, indexed by id.
 *
 * Some profile fields are not included in the user object data (see
 * local_metadata_user_record function above). Optionally, you can obtain only those
 * fields that are included in the user object.
 *
 * To be clear, this function returns the available fields, and does not
 * return the field values for a particular user.
 *
 * @param bool $onlyinuserobject True if you only want the ones in $USER
 * @return array Array of field objects from database (indexed by id)
 * @since Moodle 2.7.1
 */
function local_metadata_get_custom_fields($onlyinuserobject = false, $contextlevel) {
    global $DB;

    // Get all the fields.
    $fields = $DB->get_records('local_metadata_field', ['contextlevel' => $contextlevel], 'id ASC');

    // If only doing the user object ones, unset the rest.
    if ($onlyinuserobject) {
        foreach ($fields as $id => $field) {
            $newfield = "\\local_metadata\\fieldtype\\{$field->datatype}\\fieldtype";
            $formfield = new $newfield();
            if (!$formfield->is_instance_object_data()) {
                unset($fields[$id]);
            }
        }
    }

    return $fields;
}

/**
 * Does the user have all required custom fields set?
 *
 * Internal, to be exclusively used by {@link user_not_fully_set_up()} only.
 *
 * Note that if users have no way to fill a required field via editing their
 * profiles (e.g. the field is not visible or it is locked), we still return true.
 * So this is actually checking if we should redirect the user to edit their
 * profile, rather than whether there is a value in the database.
 *
 * @param int $instanceid
 * @return bool
 */
function local_metadata_has_required_custom_fields_set($instanceid, $contextlevel) {
    global $DB;

    $sql = "SELECT f.id
              FROM {local_metadata_field} f
         LEFT JOIN {local_metadata} d ON (d.fieldid = f.id AND d.instanceid = ?)
             WHERE f.contextlevel = ? AND f.required = 1 AND f.visible > 0 AND f.locked = 0 AND d.id IS NULL";

    if ($DB->record_exists_sql($sql, [$instanceid, $contextlevel])) {
        return false;
    }

    return true;
}
