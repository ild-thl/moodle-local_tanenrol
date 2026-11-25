<?php
require('../../config.php');
require_once('edit_form.php');

$courseid   = required_param('courseid', PARAM_INT);
$instanceid = optional_param('id', 0, PARAM_INT);

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
require_capability('moodle/course:enrolconfig', $context);

$PAGE->set_url('/enrol/tan/edit.php', ['courseid' => $course->id, 'id' => $instanceid]);
$PAGE->set_title(get_string('pluginname', 'enrol_tan'));
$PAGE->set_heading($course->fullname);

$plugin = enrol_get_plugin('tan');

if ($instanceid) {
    $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'tan', 'id' => $instanceid], '*', MUST_EXIST);
} else {
    $instance = (object)[
        'id'           => 0,
        'courseid'     => $course->id,
        'enrol'        => 'tan',
        'status'       => ENROL_INSTANCE_ENABLED,
        'roleid'       => $plugin->get_config('roleid') ?: 5,
        'enrolperiod'  => 0,
    ];
}

$mform = new enrol_tan_edit_form(null, [$instance, $plugin, $context]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/enrol/instances.php', ['id' => $course->id]));
} else if ($data = $mform->get_data()) {
    if ($instance->id) {
        $instance->status      = $data->status;
        $instance->roleid      = $data->roleid;
        $instance->enrolperiod = $data->enrolperiod ?? 0;
        $instance->timemodified = time();
        $DB->update_record('enrol', $instance);
    } else {
        $plugin->add_instance($course, [
            'status'      => $data->status,
            'roleid'      => $data->roleid,
            'enrolperiod' => $data->enrolperiod ?? 0,
        ]);
    }
    redirect(new moodle_url('/enrol/instances.php', ['id' => $course->id]));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'enrol_tan'));
$mform->display();
echo $OUTPUT->footer();