<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Upgrade steps for PG OSCE.
 *
 * @package    mod_pgosce
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade PG OSCE.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_pgosce_upgrade($oldversion) {
    global $CFG, $DB;

    if ($oldversion < 2026042900) {
        upgrade_mod_savepoint(true, 2026042900, 'pgosce');
    }

    if ($oldversion < 2026042901) {
        upgrade_mod_savepoint(true, 2026042901, 'pgosce');
    }

    if ($oldversion < 2026042902) {
        require_once($CFG->dirroot . '/mod/pgosce/locallib.php');

        $sampleids = $DB->get_fieldset_sql(
            "SELECT DISTINCT s.pgosceid
               FROM {pgosce_section} s
               JOIN {pgosce_question} q ON q.sectionid = s.id
               JOIN {pgosce_criterion} c ON c.questionid = q.id
              WHERE q.title = ?
                 OR c.description = ?",
            ['BRCA1 cancer risks', 'States lifetime breast cancer risk around 60-70%']
        );
        foreach ($sampleids as $pgosceid) {
            if (!pgosce_has_attempts($pgosceid)) {
                pgosce_delete_rubric($pgosceid);
            }
        }

        upgrade_mod_savepoint(true, 2026042902, 'pgosce');
    }

    if ($oldversion < 2026042903) {
        upgrade_mod_savepoint(true, 2026042903, 'pgosce');
    }

    if ($oldversion < 2026042904) {
        upgrade_mod_savepoint(true, 2026042904, 'pgosce');
    }

    if ($oldversion < 2026042905) {
        upgrade_mod_savepoint(true, 2026042905, 'pgosce');
    }

    if ($oldversion < 2026042906) {
        unset_config('activitypurposepgosce', 'theme_boost_union');
        upgrade_mod_savepoint(true, 2026042906, 'pgosce');
    }

    if ($oldversion < 2026042907) {
        upgrade_mod_savepoint(true, 2026042907, 'pgosce');
    }

    if ($oldversion < 2026042908) {
        upgrade_mod_savepoint(true, 2026042908, 'pgosce');
    }

    if ($oldversion < 2026042909) {
        upgrade_mod_savepoint(true, 2026042909, 'pgosce');
    }

    if ($oldversion < 2026042910) {
        upgrade_mod_savepoint(true, 2026042910, 'pgosce');
    }

    if ($oldversion < 2026042911) {
        require_once($CFG->dirroot . '/mod/pgosce/lib.php');

        $instances = $DB->get_records('pgosce', [], '', 'id, course');
        foreach ($instances as $pgosce) {
            pgosce_prune_empty_duplicate_grade_items($pgosce->course, $pgosce->id);
        }

        upgrade_mod_savepoint(true, 2026042911, 'pgosce');
    }

    if ($oldversion < 2026042912) {
        upgrade_mod_savepoint(true, 2026042912, 'pgosce');
    }

    if ($oldversion < 2026042913) {
        $table = new xmldb_table('pgosce');
        $field = new xmldb_field('showstudentinstructions', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1',
            'displaystudentreports');

        $dbman = $DB->get_manager();
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026042913, 'pgosce');
    }

    if ($oldversion < 2026042914) {
        upgrade_mod_savepoint(true, 2026042914, 'pgosce');
    }

    return true;
}
