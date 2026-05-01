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

    if ($oldversion < 2026043011) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('pgosce_assessor');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('pgosceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('courseuser', XMLDB_INDEX_NOTUNIQUE, ['course', 'userid']);
            $table->add_index('coursepgosceuser', XMLDB_INDEX_UNIQUE, ['course', 'pgosceid', 'userid']);
            $dbman->create_table($table);
        }

        $teacherroles = get_archetype_roles('teacher');
        $restrictedcaps = [
            'mod/pgosce:manage',
            'mod/pgosce:export',
            'mod/pgosce:import',
            'mod/pgosce:assignassessors',
        ];
        foreach ($teacherroles as $role) {
            foreach ($restrictedcaps as $capability) {
                if ($DB->record_exists('capabilities', ['name' => $capability])) {
                    unassign_capability($capability, $role->id);
                }
            }
        }

        upgrade_mod_savepoint(true, 2026043011, 'pgosce');
    }

    if ($oldversion < 2026043014) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('pgosce');

        $field = new xmldb_field('markinputtype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'buttons',
            'showstudentinstructions');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('markbuttonstep', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '0.5',
            'markinputtype');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026043014, 'pgosce');
    }

    if ($oldversion < 2026043015) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('pgosce');

        $field = new xmldb_field('timelimit', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0',
            'markbuttonstep');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timerwarnfirst', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '2',
            'timelimit');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timerwarnsecond', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1',
            'timerwarnfirst');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026043015, 'pgosce');
    }

    if ($oldversion < 2026050101) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('pgosce_criterion');

        $field = new xmldb_field('markinputtype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'global',
            'maxmark');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('markbuttonstep', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '0',
            'markinputtype');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026050101, 'pgosce');
    }

    if ($oldversion < 2026050102) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('pgosce');

        $field = new xmldb_field('showstudentinstructions', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0',
            'displaystudentreports');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_default($table, $field);
        }

        upgrade_mod_savepoint(true, 2026050102, 'pgosce');
    }

    if ($oldversion < 2026050105) {
        upgrade_mod_savepoint(true, 2026050105, 'pgosce');
    }

    if ($oldversion < 2026050106) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('pgosce');

        $field = new xmldb_field('showgradesingradebook', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0',
            'showstudentinstructions');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026050106, 'pgosce');
    }

    if ($oldversion < 2026050107) {
        $instances = $DB->get_records('pgosce', [], '',
            'id, course, displaystudentreports, showstudentinstructions, showgradesingradebook');
        foreach ($instances as $pgosce) {
            $hidden = empty($pgosce->showgradesingradebook) ? 1 : 0;
            $DB->set_field('grade_items', 'hidden', $hidden, [
                'courseid' => $pgosce->course,
                'itemtype' => 'mod',
                'itemmodule' => 'pgosce',
                'iteminstance' => $pgosce->id,
                'itemnumber' => 0,
            ]);
        }

        upgrade_mod_savepoint(true, 2026050107, 'pgosce');
    }

    if ($oldversion < 2026050108) {
        upgrade_mod_savepoint(true, 2026050108, 'pgosce');
    }

    if ($oldversion < 2026050109) {
        $instances = $DB->get_records('pgosce', [], '', 'id, course, showgradesingradebook');
        foreach ($instances as $pgosce) {
            $DB->set_field('grade_items', 'hidden', empty($pgosce->showgradesingradebook) ? 1 : 0, [
                'courseid' => $pgosce->course,
                'itemtype' => 'mod',
                'itemmodule' => 'pgosce',
                'iteminstance' => $pgosce->id,
                'itemnumber' => 0,
            ]);
        }

        upgrade_mod_savepoint(true, 2026050109, 'pgosce');
    }

    return true;
}
