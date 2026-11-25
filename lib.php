<?php
defined('MOODLE_INTERNAL') || die();

class enrol_tan_plugin extends enrol_plugin {

    /**
     * Rollenauswahl im Kursformular schützen.
     */
    public function roles_protected() {
        return true;
    }

    /**
     * Standard-Bearbeitungsformular (edit.php / edit_form.php) verwenden.
     */
    public function use_standard_editing_ui() {
        return true;
    }

    /**
     * Prüft, ob im Kurs eine TAN-Einschreibemethode angelegt werden darf.
     */
    public function can_add_instance($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);
        if (!has_capability('moodle/course:enrolconfig', $context)) {
            return false;
        }
        return true;
    }

    /**
     * Erlaubt Aktivieren/Deaktivieren der Methode im Kurs.
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('moodle/course:enrolconfig', $context);
    }

    /**
     * Erlaubt Löschen der Methode im Kurs.
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('moodle/course:enrolconfig', $context);
    }

    /**
     * Zeigt Bearbeiten-, Aktivieren/Deaktivieren- und Löschen-Symbole an.
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;

        if ($instance->enrol !== 'tan') {
            throw new coding_exception('invalid enrol instance!');
        }

        $context = context_course::instance($instance->courseid);
        $icons = [];

        if (has_capability('enrol/tan:config', $context) || has_capability('moodle/course:enrolconfig', $context)) {
            $editlink = new moodle_url('/enrol/tan/edit.php', ['courseid' => $instance->courseid, 'id' => $instance->id]);
            $icons[] = $OUTPUT->action_icon(
                $editlink,
                new pix_icon('t/edit', get_string('edit'), 'core', ['class' => 'iconsmall'])
            );
        }

        return array_merge($icons, parent::get_action_icons($instance));
    }

    /**
     * Schreibt einen Nutzer in den Kurs ein, sofern eine aktive enrol_tan-Instanz existiert.
     *
     * @param int $courseid
     * @param int $userid
     * @return bool True bei erfolgreicher (oder bereits bestehender) Einschreibung, sonst false.
     */
    public function enrol_tan_user(int $courseid, int $userid): bool {
        global $DB;

        if ($courseid <= 0 || $userid <= 0) {
            return false;
        }

        $course = $DB->get_record('course', ['id' => $courseid], '*', IGNORE_MISSING);
        if (!$course || $course->id == SITEID) {
            return false;
        }

        // Aktive Instanz von enrol_tan im Kurs suchen.
        $instances = $DB->get_records('enrol', [
            'courseid' => $courseid,
            'enrol'    => 'tan',
            'status'   => ENROL_INSTANCE_ENABLED,
        ], 'id ASC');

        if (empty($instances)) {
            return false;
        }

        $instance = reset($instances);

        // Bereits eingeschrieben? Dann nichts doppelt anlegen.
        $context = context_course::instance($courseid);
        if (is_enrolled($context, $userid, '', true)) {
            return true;
        }

        $roleid = !empty($instance->roleid) ? (int)$instance->roleid : 5; // 5 = standardmäßig 'student'.
        $timestart = time();
        $timeend = !empty($instance->enrolperiod) ? ($timestart + (int)$instance->enrolperiod) : 0;

        $this->enrol_user($instance, $userid, $roleid, $timestart, $timeend, ENROL_USER_ACTIVE);
        return true;
    }
}