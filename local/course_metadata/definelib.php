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
 * @subpackage totara_customfield
 */

class customfield_define_base {

    /**
     * Prints out the form snippet for creating or editing a custom field
     * @param   object   instance of the moodleform class
     */
    function define_form(&$form, $typeid=0, $tableprefix) {
        $form->addElement('header', '_commonsettings', get_string('commonsettings', 'local_course_metadata'));
        $this->define_form_common($form, $typeid, $tableprefix);

        $form->addElement('header', '_specificsettings', get_string('specificsettings', 'local_course_metadata'));
        $this->define_form_specific($form);
    }

    /**
     * Prints out the form snippet for the part of creating or
     * editing a custom field common to all data types
     * @param   object   instance of the moodleform class
     */
    function define_form_common(&$form, $typeid=0, $tableprefix) {
        global $TEXTAREA_OPTIONS;
        $strrequired = get_string('customfieldrequired', 'local_course_metadata');

        $form->addElement('text', 'fullname', get_string('fullname'), 'size="50"');
        $form->addRule('fullname', $strrequired, 'required', null, 'client');
        $form->setType('fullname', PARAM_MULTILANG);
        $form->addHelpButton('fullname', 'customfieldfullname', 'local_course_metadata');

        $form->addElement('text', 'shortname', get_string('shortname', 'local_course_metadata'), 'maxlength="100" size="25"');
        $form->addRule('shortname', $strrequired, 'required', null, 'client');
        $form->setType('shortname', PARAM_ALPHANUM);
        $form->addHelpButton('shortname', 'customfieldshortname', 'local_course_metadata');

        $form->addElement('editor', 'description_editor', get_string('description', 'local_course_metadata'), null, $TEXTAREA_OPTIONS);
        $form->setType('description_editor', PARAM_CLEANHTML);
        $form->addHelpButton('description_editor', 'description', 'local_course_metadata');

        $form->addElement('selectyesno', 'required', get_string('customfieldrequired', 'local_course_metadata'));
        $form->addHelpButton('required', 'customfieldrequired', 'local_course_metadata');
        $form->setDefault('required', 0);

        $form->addElement('selectyesno', 'locked', get_string('locked', 'local_course_metadata'));
        $form->addHelpButton('locked', 'customfieldlocked', 'local_course_metadata');
        $form->setDefault('locked', 0);

        // Unique disabled for filepicker custom fields.
        if ($form->getElementValue('datatype') != 'file') {
            $form->addElement('selectyesno', 'forceunique', get_string('forceunique', 'local_course_metadata'));
            $form->addHelpButton('forceunique', 'customfieldforceunique', 'local_course_metadata');
        } else {
            $form->addElement('hidden', 'forceunique', '0');
            $form->setType('forceunique', PARAM_INT);
        }

        $form->addElement('selectyesno', 'hidden', get_string('visible', 'local_course_metadata'));
        $form->addHelpButton('hidden', 'customfieldhidden', 'local_course_metadata');

    }

    /**
     * Prints out the form snippet for the part of creating or
     * editing a custom field specific to the current data type
     * @param   object   instance of the moodleform class
     */
    function define_form_specific(&$form) {
        /// do nothing - override if necessary
    }

    /**
     * Validate the data from the add/edit custom field form.
     * Generally this method should not be overwritten by child
     * classes.
     * @param   object   data from the add/edit custom field form
     * @return  array    associative array of error messages
     */
    function define_validate($data, $files, $typeid, $tableprefix) {

        $data = (object)$data;
        $err = array();

        $err += $this->define_validate_common($data, $files, $typeid, $tableprefix);
        $err += $this->define_validate_specific($data, $files, $tableprefix);

        return $err;
    }

    /**
     * Validate the data from the add/edit custom field form
     * that is common to all data types. Generally this method
     * should not be overwritten by child classes.
     * @param   object   data from the add/edit custom field form
     * @return  array    associative array of error messages
     */
    function define_validate_common($data, $files, $typeid, $tableprefix) {
        global $DB;

        $err = array();

        /// Check the shortname was not truncated by cleaning
        if (empty($data->shortname)) {
            $err['shortname'] = get_string('customfieldrequired', 'local_course_metadata');

        } else {
            /// Fetch field-record from DB
            $params = array('shortname' => $data->shortname);
            if ($typeid) {
                $params['typeid'] = $typeid;
            }

            $field = $DB->get_record('local_'.$tableprefix.'_metadata_field', $params);
        /// Check the shortname is unique
            if ($field and $field->id <> $data->id) {
                $err['shortname'] = get_string('shortnamenotunique', 'local_course_metadata');
            }
        }

        // Prevent custom fields being both required and locked.
        if (!empty($data->required) && !empty($data->locked) ) {
            $err['required'] = get_string('requiredandlockednotallowed', 'local_course_metadata');
        }

        /// No further checks necessary as the form class will take care of it
        return $err;
    }

    /**
     * Validate the data from the add/edit custom field form
     * that is specific to the current data type
     * @param   object   data from the add/edit custom field form
     * @return  array    associative array of error messages
     */
    function define_validate_specific($data, $files, $tableprefix) {
        /// do nothing - override if necessary
        return array();
    }

    /**
     * Alter form based on submitted or existing data
     * @param   object   form
     */
    function define_after_data(&$form) {
        /// do nothing - override if necessary
        // Prevent custom fields being both required and locked.
        $locked = $form->getElementValue('locked');
        $required = $form->getElementValue('required');
        if ($required[0] != "1" || $locked[0] != "1") {
            $form->disabledIf('required', 'locked', 'eq', 1);
            $form->disabledIf('locked', 'required', 'eq', 1);
        }
    }

    /**
     * Add a new custom field or save changes to current field
     * @param   object   data from the add/edit custom field form
     * @return  boolean  status of the insert/update record
     */
    function define_save($data, $tableprefix) {
        global $DB, $TEXTAREA_OPTIONS;
        $old = null;

        if (!empty($data->id)) {
            $old = $DB->get_record('local_'.$tableprefix.'_metadata_field', array('id' => $data->id));
        }
        $data->tableprefix = $tableprefix;
        $data = $this->define_save_preprocess($data, $old); // Hook for child classes.
        if (!$old) {
            $data->sortorder = $DB->count_records_select('local_'.$tableprefix.'_metadata_field', '') + 1;
            } else {
            $data->sortorder = $old->sortorder;
        }

        if (empty($data->id)) {
            unset($data->id);
            $data->id = $DB->insert_record('local_'.$tableprefix.'_metadata_field', $data);
        } else {
            $DB->update_record('local_'.$tableprefix.'_metadata_field', $data);
        }
        $data = file_postupdate_standard_editor($data, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'], 'local_course_metadata', 'textarea', $data->id);
        $DB->set_field('local_'.$tableprefix.'_metadata_field', 'description', $data->description, array('id' => $data->id));
        if ($data->datatype == 'textarea') {
            $data = file_postupdate_standard_editor($data, 'defaultdata', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'], 'local_course_metadata', 'textarea', $data->id);
            $DB->set_field('local_'.$tableprefix.'_metadata_field', 'defaultdata', $data->defaultdata, array('id' => $data->id));
        }
    }

    /**
     * Preprocess data from the add/edit custom field form
     * before it is saved. This method is a hook for the child
     * classes to override.
     * @param   object   data from the add/edit custom field form
     * @param   object   previous data record
     * @return  object   processed data object
     */
    function define_save_preprocess($data, $old = null) {
        /// do nothing - override if necessary
        return $data;
    }

    /**
     * Preprocess data from the add/edit custom field form
     * before it is loaded. This method is a hook for the child
     * classes to override.
     * @param   object   data from the add/edit custom field table
     * @return  object   processed data object
     */
    public function define_load_preprocess($data) {
        // Do nothing - override if necessary.
        return $data;
    }

}
