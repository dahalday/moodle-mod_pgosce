<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Activity settings form for PG OSCE.
 *
 * @package    mod_pgosce
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * PG OSCE module form.
 */
class mod_pgosce_mod_form extends moodleform_mod {
    /**
     * Define form fields.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $mform->addElement('advcheckbox', 'displaystudentreports', get_string('displaystudentreports', 'pgosce'));
        $mform->addHelpButton('displaystudentreports', 'displaystudentreports', 'pgosce');
        $mform->setDefault('displaystudentreports', 0);

        $mform->addElement('advcheckbox', 'showstudentinstructions', get_string('showstudentinstructions', 'pgosce'));
        $mform->addHelpButton('showstudentinstructions', 'showstudentinstructions', 'pgosce');
        $mform->setDefault('showstudentinstructions', 1);

        $this->standard_grading_coursemodule_elements();
        $mform->setDefault('grade', 100);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
