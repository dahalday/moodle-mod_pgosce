<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Backup structure for PG OSCE.
 *
 * @package    mod_pgosce
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the PG OSCE backup structure.
 */
class backup_pgosce_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define activity structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $pgosce = new backup_nested_element('pgosce', ['id'], [
            'course',
            'name',
            'intro',
            'introformat',
            'grade',
            'displaystudentreports',
            'showstudentinstructions',
            'markinputtype',
            'markbuttonstep',
            'timelimit',
            'timerwarnfirst',
            'timerwarnsecond',
            'timecreated',
            'timemodified',
        ]);

        $sections = new backup_nested_element('sections');
        $section = new backup_nested_element('section', ['id'], [
            'name',
            'description',
            'sortorder',
        ]);

        $questions = new backup_nested_element('questions');
        $question = new backup_nested_element('question', ['id'], [
            'title',
            'prompt',
            'sortorder',
        ]);

        $criteria = new backup_nested_element('criteria');
        $criterion = new backup_nested_element('criterion', ['id'], [
            'description',
            'maxmark',
            'markinputtype',
            'markbuttonstep',
            'sortorder',
        ]);

        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', ['id'], [
            'userid',
            'assessorid',
            'status',
            'generalcomment',
            'timecreated',
            'timemodified',
        ]);

        $scores = new backup_nested_element('scores');
        $score = new backup_nested_element('score', ['id'], [
            'criterionid',
            'mark',
            'comment',
        ]);

        $pgosce->add_child($sections);
        $sections->add_child($section);
        $section->add_child($questions);
        $questions->add_child($question);
        $question->add_child($criteria);
        $criteria->add_child($criterion);
        $pgosce->add_child($attempts);
        $attempts->add_child($attempt);
        $attempt->add_child($scores);
        $scores->add_child($score);

        $pgosce->set_source_table('pgosce', ['id' => backup::VAR_ACTIVITYID]);
        $section->set_source_table('pgosce_section', ['pgosceid' => backup::VAR_PARENTID], 'sortorder ASC, id ASC');
        $question->set_source_table('pgosce_question', ['sectionid' => backup::VAR_PARENTID], 'sortorder ASC, id ASC');
        $criterion->set_source_table('pgosce_criterion', ['questionid' => backup::VAR_PARENTID], 'sortorder ASC, id ASC');

        if ($userinfo) {
            $attempt->set_source_table('pgosce_attempt', ['pgosceid' => '../../id'], 'id ASC');
            $score->set_source_table('pgosce_score', ['attemptid' => backup::VAR_PARENTID], 'id ASC');
        }

        $attempt->annotate_ids('user', 'userid');
        $attempt->annotate_ids('user', 'assessorid');

        $pgosce->annotate_files('mod_pgosce', 'intro', null);
        $section->annotate_files('mod_pgosce', 'section', 'id');
        $question->annotate_files('mod_pgosce', 'question', 'id');
        $criterion->annotate_files('mod_pgosce', 'criterion', 'id');

        return $this->prepare_activity_structure($pgosce);
    }
}
