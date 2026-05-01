<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Library callbacks for PG OSCE.
 *
 * @package    mod_pgosce
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/pgosce/locallib.php');
require_once($CFG->libdir . '/gradelib.php');

/**
 * Supported module features.
 *
 * @param string $feature
 * @return mixed
 */
function pgosce_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_COMPLETION_HAS_RULES:
            return false;
        case FEATURE_MOD_PURPOSE:
            return defined('MOD_PURPOSE_ASSESSMENT') ? MOD_PURPOSE_ASSESSMENT : null;
        default:
            return null;
    }
}

/**
 * Add activity instance.
 *
 * @param stdClass $data
 * @return int
 */
function pgosce_add_instance(stdClass $data) {
    global $DB;

    $now = time();
    $data->timecreated = $now;
    $data->timemodified = $now;
    if (!isset($data->displaystudentreports)) {
        $data->displaystudentreports = 0;
    }
    if (!isset($data->showstudentinstructions)) {
        $data->showstudentinstructions = 0;
    }
    if (!isset($data->showgradesingradebook)) {
        $data->showgradesingradebook = 0;
    }
    if (empty($data->markinputtype)) {
        $data->markinputtype = 'buttons';
    }
    if (empty($data->markbuttonstep)) {
        $data->markbuttonstep = 0.5;
    }
    $data->timelimit = empty($data->timelimit) ? 0 : max(0, (int)$data->timelimit);
    $data->timerwarnfirst = empty($data->timerwarnfirst) ? 2 : max(0, (int)$data->timerwarnfirst);
    $data->timerwarnsecond = empty($data->timerwarnsecond) ? 1 : max(0, (int)$data->timerwarnsecond);

    $id = $DB->insert_record('pgosce', $data);
    $data->id = $id;
    pgosce_seed_default_rubric($id);
    pgosce_grade_item_update($data);

    return $id;
}

/**
 * Update activity instance.
 *
 * @param stdClass $data
 * @return bool
 */
function pgosce_update_instance(stdClass $data) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();
    if (!isset($data->displaystudentreports)) {
        $data->displaystudentreports = 0;
    }
    if (!isset($data->showstudentinstructions)) {
        $data->showstudentinstructions = 0;
    }
    if (!isset($data->showgradesingradebook)) {
        $data->showgradesingradebook = 0;
    }
    if (empty($data->markinputtype)) {
        $data->markinputtype = 'buttons';
    }
    if (empty($data->markbuttonstep)) {
        $data->markbuttonstep = 0.5;
    }
    $data->timelimit = empty($data->timelimit) ? 0 : max(0, (int)$data->timelimit);
    $data->timerwarnfirst = empty($data->timerwarnfirst) ? 2 : max(0, (int)$data->timerwarnfirst);
    $data->timerwarnsecond = empty($data->timerwarnsecond) ? 1 : max(0, (int)$data->timerwarnsecond);

    $result = $DB->update_record('pgosce', $data);
    pgosce_grade_item_update($data);
    pgosce_update_grades($data, 0, false);

    return $result;
}

/**
 * Delete activity instance.
 *
 * @param int $id
 * @return bool
 */
function pgosce_delete_instance($id) {
    global $DB;

    if (!$pgosce = $DB->get_record('pgosce', ['id' => $id])) {
        return false;
    }

    $context = null;
    if ($cm = get_coursemodule_from_instance('pgosce', $id, 0, false, IGNORE_MISSING)) {
        $context = context_module::instance($cm->id);
    }
    pgosce_delete_attempts($id);
    pgosce_delete_rubric($id, $context);
    $DB->delete_records('pgosce_assessor', ['pgosceid' => $id]);
    $DB->delete_records('pgosce', ['id' => $id]);
    pgosce_grade_item_delete($pgosce);

    return true;
}

/**
 * Dynamically hide PG OSCE activities from users who should not see them.
 *
 * @param cm_info $cm
 */
function pgosce_cm_info_dynamic(cm_info $cm) {
    global $DB;

    $pgosce = $DB->get_record('pgosce', ['id' => $cm->instance],
        'id, course, displaystudentreports, showstudentinstructions, showgradesingradebook', IGNORE_MISSING);
    if (!$pgosce) {
        return;
    }

    $context = context_module::instance($cm->id);
    if (!pgosce_can_view_activity($pgosce, $context)) {
        $cm->set_available(false, 0);
        $cm->set_user_visible(false);
        $cm->set_no_view_link();
        $cm->set_content('', true);
        $cm->set_after_link('');
        if (method_exists($cm, 'set_custom_cmlist_item')) {
            $cm->set_custom_cmlist_item(true);
        }
    }
}

/**
 * Create or update the grade item.
 *
 * @param stdClass $pgosce
 * @param mixed $grades
 * @return int
 */
function pgosce_grade_item_update(stdClass $pgosce, $grades = null) {
    if (!empty($pgosce->course) && !empty($pgosce->id)) {
        pgosce_prune_empty_duplicate_grade_items($pgosce->course, $pgosce->id);
    }

    $params = [
        'itemname' => $pgosce->name,
        'idnumber' => isset($pgosce->cmidnumber) ? $pgosce->cmidnumber : null,
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax' => max(0, (float)$pgosce->grade),
        'grademin' => 0,
        'hidden' => (empty($pgosce->showgradesingradebook) && empty($pgosce->displaystudentreports) &&
            empty($pgosce->showstudentinstructions)) ? 1 : 0,
    ];

    if (isset($pgosce->gradepass)) {
        $params['gradepass'] = $pgosce->gradepass;
    }
    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/pgosce', $pgosce->course, 'mod', 'pgosce', $pgosce->id, 0, $grades, $params);
}

/**
 * Remove duplicate empty grade items left by interrupted restores/copies.
 *
 * Moodle's grade API expects exactly one grade item per module instance. This
 * only removes duplicates that have no grade_grades rows attached, preserving
 * the first grade item with marks where one exists.
 *
 * @param int $courseid
 * @param int $pgosceid
 */
function pgosce_prune_empty_duplicate_grade_items($courseid, $pgosceid) {
    global $DB;

    $params = [
        'courseid' => $courseid,
        'itemtype' => 'mod',
        'itemmodule' => 'pgosce',
        'iteminstance' => $pgosceid,
        'itemnumber' => 0,
    ];
    $items = $DB->get_records('grade_items', $params, 'id ASC');
    if (count($items) <= 1) {
        return;
    }

    $keepid = 0;
    foreach ($items as $item) {
        if ($DB->record_exists('grade_grades', ['itemid' => $item->id])) {
            $keepid = $item->id;
            break;
        }
    }
    if (!$keepid) {
        $first = reset($items);
        $keepid = $first->id;
    }

    foreach ($items as $item) {
        if ($item->id == $keepid || $DB->record_exists('grade_grades', ['itemid' => $item->id])) {
            continue;
        }
        if ($gradeitem = grade_item::fetch(['id' => $item->id])) {
            $gradeitem->delete('mod/pgosce');
        } else {
            $DB->delete_records('grade_items', ['id' => $item->id]);
        }
    }
}

/**
 * Delete grade item.
 *
 * @param stdClass $pgosce
 * @return int
 */
function pgosce_grade_item_delete(stdClass $pgosce) {
    if (!empty($pgosce->course) && !empty($pgosce->id)) {
        pgosce_prune_empty_duplicate_grade_items($pgosce->course, $pgosce->id);
    }

    return grade_update('mod/pgosce', $pgosce->course, 'mod', 'pgosce', $pgosce->id, 0, null, ['deleted' => 1]);
}

/**
 * Return grades for Moodle gradebook.
 *
 * @param stdClass $pgosce
 * @param int $userid
 * @return array|false
 */
function pgosce_get_user_grades(stdClass $pgosce, $userid = 0) {
    global $DB;

    $params = ['pgosceid' => $pgosce->id, 'status' => PGOSCE_STATUS_FINAL];
    if ($userid) {
        $params['userid'] = $userid;
    }

    $attempts = $DB->get_records('pgosce_attempt', $params, '', 'id, userid');
    if (!$attempts) {
        return false;
    }

    $userids = [];
    foreach ($attempts as $attempt) {
        $userids[$attempt->userid] = $attempt->userid;
    }

    $grades = [];
    foreach ($userids as $uid) {
        $grade = pgosce_calculate_student_grade($pgosce, $uid);
        if ($grade) {
            $grades[$uid] = $grade;
        }
    }

    return $grades ? $grades : false;
}

/**
 * Push grades into the gradebook.
 *
 * @param stdClass $pgosce
 * @param int $userid
 * @param bool $nullifnone
 */
function pgosce_update_grades(stdClass $pgosce, $userid = 0, $nullifnone = true) {
    $grades = pgosce_get_user_grades($pgosce, $userid);
    if ($grades) {
        pgosce_grade_item_update($pgosce, $grades);
    } else if ($userid && $nullifnone) {
        $grade = (object)['userid' => $userid, 'rawgrade' => null];
        pgosce_grade_item_update($pgosce, $grade);
    } else {
        pgosce_grade_item_update($pgosce);
    }
}

/**
 * Reset gradebook items for this module.
 *
 * @param int $courseid
 * @param string $type
 */
function pgosce_reset_gradebook($courseid, $type = '') {
    global $DB;

    $instances = $DB->get_records('pgosce', ['course' => $courseid]);
    foreach ($instances as $pgosce) {
        pgosce_grade_item_update($pgosce, 'reset');
    }
}

/**
 * Add PG OSCE options to Moodle course reset form.
 *
 * @param MoodleQuickForm $mform
 */
function pgosce_reset_course_form_definition($mform) {
    $mform->addElement('header', 'pgosceheader', get_string('modulenameplural', 'pgosce'));
    $mform->addElement('advcheckbox', 'reset_pgosce_attempts', get_string('resetattempts', 'pgosce'));
    $mform->addElement('advcheckbox', 'reset_pgosce_rubrics', get_string('resetrubrics', 'pgosce'));
    $mform->addHelpButton('reset_pgosce_rubrics', 'resetrubrics', 'pgosce');
}

/**
 * Default reset settings.
 *
 * @param stdClass $course
 * @return array
 */
function pgosce_reset_course_form_defaults($course) {
    return [
        'reset_pgosce_attempts' => 1,
        'reset_pgosce_rubrics' => 0,
    ];
}

/**
 * Reset PG OSCE user data during Moodle course reset.
 *
 * @param stdClass $data
 * @return array
 */
function pgosce_reset_userdata($data) {
    global $DB;

    $status = [];
    $component = get_string('modulenameplural', 'pgosce');
    $instances = $DB->get_records('pgosce', ['course' => $data->courseid]);

    if (!empty($data->reset_pgosce_attempts)) {
        foreach ($instances as $pgosce) {
            pgosce_delete_attempts($pgosce->id);
            pgosce_grade_item_update($pgosce, 'reset');
        }
        $status[] = [
            'component' => $component,
            'item' => get_string('resetattempts', 'pgosce'),
            'error' => false,
        ];
    }

    if (!empty($data->reset_pgosce_rubrics)) {
        foreach ($instances as $pgosce) {
            $context = null;
            if ($cm = get_coursemodule_from_instance('pgosce', $pgosce->id, $data->courseid, false, IGNORE_MISSING)) {
                $context = context_module::instance($cm->id);
            }
            pgosce_delete_rubric($pgosce->id, $context);
        }
        $status[] = [
            'component' => $component,
            'item' => get_string('resetrubrics', 'pgosce'),
            'error' => false,
        ];
    }

    return $status;
}

/**
 * Serve rubric editor files.
 *
 * @param stdClass $course
 * @param stdClass|cm_info $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function pgosce_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    if (!in_array($filearea, ['intro', PGOSCE_FILEAREA_SECTION, PGOSCE_FILEAREA_QUESTION, PGOSCE_FILEAREA_CRITERION])) {
        return false;
    }

    require_login($course, true, $cm);
    if (!has_capability('mod/pgosce:view', $context)) {
        return false;
    }

    if ($filearea !== 'intro') {
        $pgosce = $DB->get_record('pgosce', ['id' => $cm->instance],
            'id, course, showstudentinstructions, displaystudentreports', IGNORE_MISSING);
        if (!$pgosce) {
            return false;
        }
        if (!pgosce_can_view_activity($pgosce, $context)) {
            return false;
        }
        $canseerubricfiles = pgosce_can_view_activity($pgosce, $context) &&
            (pgosce_can_assess_station($pgosce, $context) ||
                has_capability('mod/pgosce:manage', $context) ||
                has_capability('mod/pgosce:viewreports', $context));

        if (!$canseerubricfiles) {
            if ($filearea === PGOSCE_FILEAREA_SECTION &&
                    empty($pgosce->showstudentinstructions) && empty($pgosce->displaystudentreports)) {
                return false;
            }
            if (in_array($filearea, [PGOSCE_FILEAREA_QUESTION, PGOSCE_FILEAREA_CRITERION]) &&
                    empty($pgosce->displaystudentreports)) {
                return false;
            }
        }
    }

    $itemid = $filearea === 'intro' ? 0 : array_shift($args);
    $filename = array_pop($args);
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    $file = get_file_storage()->get_file($context->id, 'mod_pgosce', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Add module links to settings navigation.
 *
 * @param settings_navigation $settings
 * @param navigation_node $node
 */
function pgosce_extend_settings_navigation(settings_navigation $settings, navigation_node $node) {
    global $PAGE;

    if (empty($PAGE->cm)) {
        return;
    }
    $context = context_module::instance($PAGE->cm->id);
    if (has_capability('mod/pgosce:manage', $context)) {
        $node->add(get_string('managerubric', 'pgosce'), new moodle_url('/mod/pgosce/manage.php', ['id' => $PAGE->cm->id]));
    }
    if (has_capability('mod/pgosce:assignassessors', $context)) {
        $node->add(get_string('assignassessors', 'pgosce'), new moodle_url('/mod/pgosce/assign.php', ['id' => $PAGE->cm->id]));
    }
    if (has_capability('mod/pgosce:import', $context)) {
        $node->add(get_string('giftimportexport', 'pgosce'), new moodle_url('/mod/pgosce/gift.php', ['id' => $PAGE->cm->id]));
    }
    if (has_capability('mod/pgosce:export', $context)) {
        $node->add(get_string('export', 'pgosce'), new moodle_url('/mod/pgosce/export.php', ['id' => $PAGE->cm->id]));
    }
}
