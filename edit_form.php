<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class enrol_tan_edit_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        [$instance, $plugin, $context] = $this->_customdata;

        $mform->addElement('header', 'header', get_string('pluginname', 'enrol_tan'));

        $options = [
            ENROL_INSTANCE_ENABLED  => get_string('yes'),
            ENROL_INSTANCE_DISABLED => get_string('no'),
        ];
        $mform->addElement('select', 'status', get_string('status', 'enrol_tan'), $options);
        $mform->setDefault('status', ENROL_INSTANCE_ENABLED);

        $roles = get_assignable_roles($context, ROLENAME_BOTH);
        $mform->addElement('select', 'roleid', get_string('assignrole', 'enrol_tan'), $roles);
        $mform->setDefault('roleid', $plugin->get_config('roleid') ?: 5);

        $mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_tan'), [
            'optional' => true,
            'defaultunit' => DAYSECS,
        ]);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $this->set_data($instance);
    }
}