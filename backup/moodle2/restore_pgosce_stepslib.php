<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Restore structure for PG OSCE.
 *
 * @package    mod_pgosce
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restores one PG OSCE activity.
 */
class restore_pgosce_activity_structure_step extends restore_activity_structure_step {
    /** @var int New activity instance id. */
    protected $pgosceid = 0;

    /**
     * Define restore paths.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [
            new restore_path_element('pgosce', '/activity/pgosce'),
            new restore_path_element('pgosce_section', '/activity/pgosce/sections/section'),
            new restore_path_element('pgosce_question', '/activity/pgosce/sections/section/questions/question'),
            new restore_path_element('pgosce_criterion', '/activity/pgosce/sections/section/questions/question/criteria/criterion'),
        ];

        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('pgosce_attempt', '/activity/pgosce/attempts/attempt');
            $paths[] = new restore_path_element('pgosce_score', '/activity/pgosce/attempts/attempt/scores/score');
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore the activity instance.
     *
     * @param array $data
     */
    protected function process_pgosce($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        if (!isset($data->showstudentinstructions)) {
            $data->showstudentinstructions = 1;
        }
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $this->pgosceid = $DB->insert_record('pgosce', $data);
        $this->apply_activity_instance($this->pgosceid);
    }

    /**
     * Restore a rubric section.
     *
     * @param array $data
     */
    protected function process_pgosce_section($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->pgosceid = $this->get_new_parentid('pgosce');

        $newid = $DB->insert_record('pgosce_section', $data);
        $this->set_mapping('pgosce_section', $oldid, $newid, true);
    }

    /**
     * Restore a rubric question.
     *
     * @param array $data
     */
    protected function process_pgosce_question($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->sectionid = $this->get_new_parentid('pgosce_section');

        $newid = $DB->insert_record('pgosce_question', $data);
        $this->set_mapping('pgosce_question', $oldid, $newid, true);
    }

    /**
     * Restore an answer-key criterion.
     *
     * @param array $data
     */
    protected function process_pgosce_criterion($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->questionid = $this->get_new_parentid('pgosce_question');

        $newid = $DB->insert_record('pgosce_criterion', $data);
        $this->set_mapping('pgosce_criterion', $oldid, $newid, true);
    }

    /**
     * Restore a student assessment attempt when user data is included.
     *
     * @param array $data
     */
    protected function process_pgosce_attempt($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->pgosceid = $this->get_new_parentid('pgosce');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->assessorid = $this->get_mappingid('user', $data->assessorid);

        if (empty($data->userid) || empty($data->assessorid)) {
            return;
        }

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newid = $DB->insert_record('pgosce_attempt', $data);
        $this->set_mapping('pgosce_attempt', $oldid, $newid);
    }

    /**
     * Restore a criterion score when user data is included.
     *
     * @param array $data
     */
    protected function process_pgosce_score($data) {
        global $DB;

        $data = (object)$data;
        $data->attemptid = $this->get_new_parentid('pgosce_attempt');
        $data->criterionid = $this->get_mappingid('pgosce_criterion', $data->criterionid);

        if (empty($data->attemptid) || empty($data->criterionid)) {
            return;
        }

        $DB->insert_record('pgosce_score', $data);
    }

    /**
     * Restore related files.
     */
    protected function after_execute() {
        $this->add_related_files('mod_pgosce', 'intro', null);
        $this->add_related_files('mod_pgosce', 'section', 'pgosce_section');
        $this->add_related_files('mod_pgosce', 'question', 'pgosce_question');
        $this->add_related_files('mod_pgosce', 'criterion', 'pgosce_criterion');
    }
}
