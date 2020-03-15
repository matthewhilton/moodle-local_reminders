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
 * @package local_reminders
 * @copyright  2014 Joannes Burk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/local/reminders/locallib.php');

class local_reminders_coursesettings_edit_form extends moodleform {

    public function definition() {
        global $USER;

        $mform = $this->_form;
        list($coursesettings) = $this->_customdata;

        $mform->addElement('advcheckbox', 'status_course',
            get_string('courseheading', 'local_reminders'),
            get_string('enabled', 'local_reminders'));
        $mform->setDefault('status_course', 1);

        $mform->addElement('advcheckbox', 'status_activities',
            get_string('dueheading', 'local_reminders'),
            get_string('enabled', 'local_reminders'));
        $mform->setDefault('status_activities', 1);

        $mform->addElement('advcheckbox', 'status_group',
            get_string('groupheading', 'local_reminders'),
            get_string('enabled', 'local_reminders'));
        $mform->setDefault('status_group', 1);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $currtime = time() - (24 * 3600);
        $upcomingactivities = get_upcoming_events_for_course($coursesettings->courseid, $currtime);
        $noactivities = true;
        $mform->addElement('header', 'description',
                get_string('activityconfupcomingactivities', 'local_reminders'));

        if (!empty($upcomingactivities)) {
            // group activities by start time
            $allactivities = array();
            foreach ($upcomingactivities as $activity) {
                $starttime = $activity->timestart - ($activity->timestart % (24 * 3600));
                if (array_key_exists($starttime, $allactivities)) {
                    $allactivities[$starttime][] = $activity;
                } else {
                    $allactivities[$starttime] = array($activity);
                }
            }
            ksort($allactivities);
            $upcomingactivities = $allactivities;
            $mform->addElement('static', 'descriptionsub', '',
                get_string('activityconfupcomingactivitiesdesc', 'local_reminders'));

            $daytimeformat = get_string('strftimedaydate', 'langconfig');
            $tzone = core_date::get_user_timezone($USER);
            foreach ($upcomingactivities as $daytime => $dailyactivities) {
                $mform->addElement('static', 'header'.$daytime, '<h5>'.userdate($daytime, $daytimeformat, $tzone).'</h5>');
                foreach ($dailyactivities as $activity) {
                    $modinfo = fetch_module_instance($activity->modulename, $activity->instance, $coursesettings->courseid);
                    $timeduration = format_event_time_duration($USER, $activity, null, false);

                    $key = "activity_".$activity->id.'_enabled';
                    $mform->addElement('advcheckbox', $key,
                        get_string('pluginname', $activity->modulename).
                        ': '.(isset($modinfo->name) ? $modinfo->name : $activity->name),
                        get_string('enabled', 'local_reminders'));
                    $mform->setDefault($key, 1);

                    $keyoverdue = "activity_".$activity->id.'_enabledoverdue';
                    $mform->addElement('advcheckbox', $keyoverdue,
                        ' ', get_string('enabledoverdue', 'local_reminders'));
                    $mform->setDefault($keyoverdue, 1);

                    $noactivities = false;
                }
            }
        }

        if ($noactivities) {
            $mform->addElement('static', 'descriptionsubnoact', '',
                get_string('activityconfnoupcomingactivities', 'local_reminders'));
        }

        $this->add_action_buttons(true);

        $this->set_data($coursesettings);
    }
}