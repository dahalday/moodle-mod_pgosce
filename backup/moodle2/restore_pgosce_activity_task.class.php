<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Restore task for PG OSCE.
 *
 * @package    mod_pgosce
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/pgosce/backup/moodle2/restore_pgosce_stepslib.php');

/**
 * Defines the PG OSCE activity restore task.
 */
class restore_pgosce_activity_task extends restore_activity_task {
    /**
     * No custom restore settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Add the structure step.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_pgosce_activity_structure_step('pgosce_structure', 'pgosce.xml'));
    }

    /**
     * Define rich-text fields that need link decoding.
     *
     * @return array
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('pgosce', ['intro'], 'pgosce'),
            new restore_decode_content('pgosce_section', ['description'], 'pgosce_section'),
            new restore_decode_content('pgosce_question', ['prompt'], 'pgosce_question'),
            new restore_decode_content('pgosce_criterion', ['description'], 'pgosce_criterion'),
        ];
    }

    /**
     * Define link decoding rules.
     *
     * @return array
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('PGOSCEVIEWBYID', '/mod/pgosce/view.php?id=$1', 'course_module'),
            new restore_decode_rule('PGOSCEINDEX', '/mod/pgosce/index.php?id=$1', 'course'),
        ];
    }

    /**
     * Restore old log links.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [
            new restore_log_rule('pgosce', 'add', 'view.php?id={course_module}', '{pgosce}'),
            new restore_log_rule('pgosce', 'update', 'view.php?id={course_module}', '{pgosce}'),
            new restore_log_rule('pgosce', 'view', 'view.php?id={course_module}', '{pgosce}'),
            new restore_log_rule('pgosce', 'report', 'report.php?id={course_module}', '{pgosce}'),
        ];
    }

    /**
     * Restore course-level log links.
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        return [
            new restore_log_rule('pgosce', 'view all', 'index.php?id={course}', null),
        ];
    }
}
