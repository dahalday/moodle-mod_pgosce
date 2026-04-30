<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Local helpers for PG OSCE.
 *
 * @package    mod_pgosce
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/editorlib.php');
require_once($CFG->libdir . '/filelib.php');

define('PGOSCE_STATUS_DRAFT', 0);
define('PGOSCE_STATUS_FINAL', 1);
define('PGOSCE_FILEAREA_SECTION', 'section');
define('PGOSCE_FILEAREA_QUESTION', 'question');
define('PGOSCE_FILEAREA_CRITERION', 'criterion');

/**
 * Blank editable rubric used when a station has not been configured yet.
 *
 * @return array
 */
function pgosce_default_rubric() {
    return [
        [
            'name' => '',
            'description' => '',
            'questions' => [
                [
                    'title' => '',
                    'prompt' => '',
                    'criteria' => [
                        ['description' => '', 'maxmark' => 1],
                    ],
                ],
            ],
        ],
    ];
}

/**
 * New activities start with no saved rubric content.
 *
 * @param int $pgosceid
 */
function pgosce_seed_default_rubric($pgosceid) {
    // Intentionally blank. Teachers create the station questions and marks.
}

/**
 * Common editor options for rubric rich text fields.
 *
 * @param context_module $context
 * @return array
 */
function pgosce_editor_options(context_module $context) {
    global $CFG;

    $maxfiles = defined('EDITOR_UNLIMITED_FILES') ? EDITOR_UNLIMITED_FILES : -1;

    return [
        'context' => $context,
        'maxbytes' => $CFG->maxbytes,
        'maxfiles' => $maxfiles,
        'subdirs' => 0,
        'trusttext' => false,
        'noclean' => false,
    ];
}

/**
 * Prepare rich text and files for a Moodle editor.
 *
 * @param context_module $context
 * @param string $filearea
 * @param int $itemid
 * @param string $text
 * @param int $draftitemid
 * @return string
 */
function pgosce_prepare_editor_content(context_module $context, $filearea, $itemid, $text, &$draftitemid) {
    $draftitemid = 0;
    return file_prepare_draft_area(
        $draftitemid,
        $context->id,
        'mod_pgosce',
        $filearea,
        $itemid,
        pgosce_editor_options($context),
        $text
    );
}

/**
 * Save rich text and editor files.
 *
 * @param context_module $context
 * @param string $filearea
 * @param int $itemid
 * @param string $text
 * @param int $draftitemid
 * @return string
 */
function pgosce_save_editor_content(context_module $context, $filearea, $itemid, $text, $draftitemid) {
    $text = clean_text($text, FORMAT_HTML);
    if (empty($draftitemid)) {
        return $text;
    }

    return file_save_draft_area_files(
        $draftitemid,
        $context->id,
        'mod_pgosce',
        $filearea,
        $itemid,
        pgosce_editor_options($context),
        $text
    );
}

/**
 * Format rich rubric text with pluginfile URLs rewritten.
 *
 * @param context_module $context
 * @param string $filearea
 * @param int $itemid
 * @param string $text
 * @return string
 */
function pgosce_format_editor_content(context_module $context, $filearea, $itemid, $text) {
    $text = file_rewrite_pluginfile_urls($text, 'pluginfile.php', $context->id, 'mod_pgosce', $filearea, $itemid);
    return format_text($text, FORMAT_HTML, ['context' => $context]);
}

/**
 * Get a nested rubric.
 *
 * @param int $pgosceid
 * @return array
 */
function pgosce_get_rubric($pgosceid) {
    global $DB;

    $sections = $DB->get_records('pgosce_section', ['pgosceid' => $pgosceid], 'sortorder ASC, id ASC');
    $rubric = [];
    foreach ($sections as $section) {
        $sectiondata = [
            'id' => $section->id,
            'name' => $section->name,
            'description' => $section->description,
            'questions' => [],
        ];
        $questions = $DB->get_records('pgosce_question', ['sectionid' => $section->id], 'sortorder ASC, id ASC');
        foreach ($questions as $question) {
            $questiondata = [
                'id' => $question->id,
                'title' => $question->title,
                'prompt' => $question->prompt,
                'criteria' => [],
            ];
            $criteria = $DB->get_records('pgosce_criterion', ['questionid' => $question->id], 'sortorder ASC, id ASC');
            foreach ($criteria as $criterion) {
                $questiondata['criteria'][] = [
                    'id' => $criterion->id,
                    'description' => $criterion->description,
                    'maxmark' => (float)$criterion->maxmark,
                ];
            }
            $sectiondata['questions'][] = $questiondata;
        }
        $rubric[] = $sectiondata;
    }

    return $rubric;
}

/**
 * Convert an activity rubric to editable JSON.
 *
 * @param int $pgosceid
 * @return string
 */
function pgosce_rubric_json($pgosceid) {
    return json_encode(pgosce_get_rubric($pgosceid), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

/**
 * Save a JSON rubric.
 *
 * @param int $pgosceid
 * @param string $json
 * @return bool
 */
function pgosce_save_rubric_json($pgosceid, $json) {
    $rubric = json_decode($json, true);
    if (!is_array($rubric)) {
        return false;
    }
    pgosce_save_rubric_array($pgosceid, $rubric);
    return true;
}

/**
 * Save a nested rubric array.
 *
 * @param int $pgosceid
 * @param array $rubric
 */
function pgosce_save_rubric_array($pgosceid, array $rubric, context_module $context = null) {
    global $DB;

    $existingsectionids = array_keys($DB->get_records('pgosce_section', ['pgosceid' => $pgosceid], '', 'id'));
    $existingquestionids = $DB->get_fieldset_sql(
        "SELECT q.id
           FROM {pgosce_question} q
           JOIN {pgosce_section} s ON s.id = q.sectionid
          WHERE s.pgosceid = ?",
        [$pgosceid]
    );
    $existingcriterionids = $DB->get_fieldset_sql(
        "SELECT c.id
           FROM {pgosce_criterion} c
           JOIN {pgosce_question} q ON q.id = c.questionid
           JOIN {pgosce_section} s ON s.id = q.sectionid
          WHERE s.pgosceid = ?",
        [$pgosceid]
    );
    $keepsectionids = [];
    $keepquestionids = [];
    $keepcriterionids = [];
    $sectionorder = 0;

    foreach ($rubric as $section) {
        if (empty($section['name']) || empty($section['questions']) || !is_array($section['questions'])) {
            continue;
        }
        $sectionid = !empty($section['id']) ? (int)$section['id'] : 0;
        $isexistingsection = $sectionid && in_array($sectionid, $existingsectionids);
        $sectionrecord = (object)[
            'pgosceid' => $pgosceid,
            'name' => clean_param($section['name'], PARAM_TEXT),
            'description' => isset($section['description']) ? clean_text($section['description'], FORMAT_HTML) : '',
            'sortorder' => $sectionorder++,
        ];
        if ($isexistingsection) {
            $sectionrecord->id = $sectionid;
            $DB->update_record('pgosce_section', $sectionrecord);
        } else {
            $sectionid = $DB->insert_record('pgosce_section', $sectionrecord);
        }
        if ($context && !empty($section['descriptionitemid'])) {
            $sectionrecord->id = $sectionid;
            $sectionrecord->description = pgosce_save_editor_content(
                $context,
                PGOSCE_FILEAREA_SECTION,
                $sectionid,
                $sectionrecord->description,
                (int)$section['descriptionitemid']
            );
            $DB->update_record('pgosce_section', $sectionrecord);
        }
        $keepsectionids[] = $sectionid;

        $questionorder = 0;
        foreach ($section['questions'] as $question) {
            if (empty($question['title']) || empty($question['criteria']) || !is_array($question['criteria'])) {
                continue;
            }
            $questionid = !empty($question['id']) ? (int)$question['id'] : 0;
            $isexistingquestion = $questionid && in_array($questionid, $existingquestionids);
            $questionrecord = (object)[
                'sectionid' => $sectionid,
                'title' => clean_param($question['title'], PARAM_TEXT),
                'prompt' => isset($question['prompt']) ? clean_text($question['prompt'], FORMAT_HTML) : '',
                'sortorder' => $questionorder++,
            ];
            if ($isexistingquestion) {
                $questionrecord->id = $questionid;
                $DB->update_record('pgosce_question', $questionrecord);
            } else {
                $questionid = $DB->insert_record('pgosce_question', $questionrecord);
            }
            if ($context && !empty($question['promptitemid'])) {
                $questionrecord->id = $questionid;
                $questionrecord->prompt = pgosce_save_editor_content(
                    $context,
                    PGOSCE_FILEAREA_QUESTION,
                    $questionid,
                    $questionrecord->prompt,
                    (int)$question['promptitemid']
                );
                $DB->update_record('pgosce_question', $questionrecord);
            }
            $keepquestionids[] = $questionid;

            $criterionorder = 0;
            foreach ($question['criteria'] as $criterion) {
                if (empty($criterion['description'])) {
                    continue;
                }
                $criterionid = !empty($criterion['id']) ? (int)$criterion['id'] : 0;
                $isexistingcriterion = $criterionid && in_array($criterionid, $existingcriterionids);
                $maxmark = isset($criterion['maxmark']) ? (float)$criterion['maxmark'] : 1;
                $criterionrecord = (object)[
                    'questionid' => $questionid,
                    'description' => clean_text($criterion['description'], FORMAT_HTML),
                    'maxmark' => max(0, $maxmark),
                    'sortorder' => $criterionorder++,
                ];
                if ($isexistingcriterion) {
                    $criterionrecord->id = $criterionid;
                    $DB->update_record('pgosce_criterion', $criterionrecord);
                } else {
                    $criterionid = $DB->insert_record('pgosce_criterion', $criterionrecord);
                }
                if ($context && !empty($criterion['descriptionitemid'])) {
                    $criterionrecord->id = $criterionid;
                    $criterionrecord->description = pgosce_save_editor_content(
                        $context,
                        PGOSCE_FILEAREA_CRITERION,
                        $criterionid,
                        $criterionrecord->description,
                        (int)$criterion['descriptionitemid']
                    );
                    $DB->update_record('pgosce_criterion', $criterionrecord);
                }
                $keepcriterionids[] = $criterionid;
            }
        }
    }

    $removedcriteria = array_diff($existingcriterionids, $keepcriterionids);
    foreach ($removedcriteria as $criterionid) {
        $DB->delete_records('pgosce_score', ['criterionid' => $criterionid]);
        if ($context) {
            get_file_storage()->delete_area_files($context->id, 'mod_pgosce', PGOSCE_FILEAREA_CRITERION, $criterionid);
        }
        $DB->delete_records('pgosce_criterion', ['id' => $criterionid]);
    }

    foreach (array_diff($existingquestionids, $keepquestionids) as $questionid) {
        if ($context) {
            get_file_storage()->delete_area_files($context->id, 'mod_pgosce', PGOSCE_FILEAREA_QUESTION, $questionid);
        }
        $DB->delete_records('pgosce_question', ['id' => $questionid]);
    }

    foreach (array_diff($existingsectionids, $keepsectionids) as $sectionid) {
        if ($context) {
            get_file_storage()->delete_area_files($context->id, 'mod_pgosce', PGOSCE_FILEAREA_SECTION, $sectionid);
        }
        $DB->delete_records('pgosce_section', ['id' => $sectionid]);
    }
}

/**
 * Delete a rubric for an activity.
 *
 * @param int $pgosceid
 */
function pgosce_delete_rubric($pgosceid, context_module $context = null) {
    global $DB;

    $sections = $DB->get_records('pgosce_section', ['pgosceid' => $pgosceid], '', 'id');
    foreach ($sections as $section) {
        $questions = $DB->get_records('pgosce_question', ['sectionid' => $section->id], '', 'id');
        foreach ($questions as $question) {
            $criteria = $DB->get_records('pgosce_criterion', ['questionid' => $question->id], '', 'id');
            foreach ($criteria as $criterion) {
                $DB->delete_records('pgosce_score', ['criterionid' => $criterion->id]);
                if ($context) {
                    get_file_storage()->delete_area_files($context->id, 'mod_pgosce', PGOSCE_FILEAREA_CRITERION, $criterion->id);
                }
            }
            $DB->delete_records('pgosce_criterion', ['questionid' => $question->id]);
            if ($context) {
                get_file_storage()->delete_area_files($context->id, 'mod_pgosce', PGOSCE_FILEAREA_QUESTION, $question->id);
            }
        }
        $DB->delete_records('pgosce_question', ['sectionid' => $section->id]);
        if ($context) {
            get_file_storage()->delete_area_files($context->id, 'mod_pgosce', PGOSCE_FILEAREA_SECTION, $section->id);
        }
    }
    $DB->delete_records('pgosce_section', ['pgosceid' => $pgosceid]);
}

/**
 * Delete all assessment attempts and criterion scores for an activity.
 *
 * @param int $pgosceid
 */
function pgosce_delete_attempts($pgosceid) {
    global $DB;

    $attempts = $DB->get_records('pgosce_attempt', ['pgosceid' => $pgosceid], '', 'id');
    foreach ($attempts as $attempt) {
        $DB->delete_records('pgosce_score', ['attemptid' => $attempt->id]);
    }
    $DB->delete_records('pgosce_attempt', ['pgosceid' => $pgosceid]);
}

/**
 * Does this activity have assessment attempts?
 *
 * @param int $pgosceid
 * @return bool
 */
function pgosce_has_attempts($pgosceid) {
    global $DB;
    return $DB->record_exists('pgosce_attempt', ['pgosceid' => $pgosceid]);
}

/**
 * Return all criteria for the activity.
 *
 * @param int $pgosceid
 * @return array
 */
function pgosce_get_criteria($pgosceid) {
    global $DB;

    $sql = "SELECT c.*
              FROM {pgosce_criterion} c
              JOIN {pgosce_question} q ON q.id = c.questionid
              JOIN {pgosce_section} s ON s.id = q.sectionid
             WHERE s.pgosceid = ?
          ORDER BY s.sortorder, q.sortorder, c.sortorder, c.id";
    return $DB->get_records_sql($sql, [$pgosceid]);
}

/**
 * Total possible marks for the current rubric.
 *
 * @param int $pgosceid
 * @return float
 */
function pgosce_get_total_maxmark($pgosceid) {
    $total = 0.0;
    foreach (pgosce_get_criteria($pgosceid) as $criterion) {
        $total += (float)$criterion->maxmark;
    }
    return $total;
}

/**
 * Get or create an attempt for one assessor/student pair.
 *
 * @param int $pgosceid
 * @param int $userid
 * @param int $assessorid
 * @return stdClass
 */
function pgosce_get_or_create_attempt($pgosceid, $userid, $assessorid) {
    global $DB;

    $params = ['pgosceid' => $pgosceid, 'userid' => $userid, 'assessorid' => $assessorid];
    if ($attempt = $DB->get_record('pgosce_attempt', $params)) {
        return $attempt;
    }

    $now = time();
    $attempt = (object)$params;
    $attempt->status = PGOSCE_STATUS_DRAFT;
    $attempt->generalcomment = '';
    $attempt->timecreated = $now;
    $attempt->timemodified = $now;
    $attempt->id = $DB->insert_record('pgosce_attempt', $attempt);

    return $attempt;
}

/**
 * Get scores for an attempt, keyed by criterion id.
 *
 * @param int $attemptid
 * @return array
 */
function pgosce_get_scores($attemptid) {
    global $DB;
    return $DB->get_records('pgosce_score', ['attemptid' => $attemptid], '', 'criterionid, mark, comment, id');
}

/**
 * Calculate an attempt score.
 *
 * @param stdClass $attempt
 * @return array
 */
function pgosce_calculate_attempt(stdClass $attempt) {
    $scores = pgosce_get_scores($attempt->id);
    $criteria = pgosce_get_criteria($attempt->pgosceid);
    $earned = 0.0;
    $max = 0.0;
    foreach ($criteria as $criterion) {
        $max += (float)$criterion->maxmark;
        if (isset($scores[$criterion->id])) {
            $earned += min((float)$criterion->maxmark, max(0, (float)$scores[$criterion->id]->mark));
        }
    }
    $percentage = $max > 0 ? ($earned / $max) * 100 : 0;
    return ['earned' => $earned, 'max' => $max, 'percentage' => $percentage];
}

/**
 * Average finalized attempts for a student.
 *
 * @param stdClass $pgosce
 * @param int $userid
 * @return stdClass|null
 */
function pgosce_calculate_student_grade(stdClass $pgosce, $userid) {
    global $DB;

    $attempts = $DB->get_records('pgosce_attempt', [
        'pgosceid' => $pgosce->id,
        'userid' => $userid,
        'status' => PGOSCE_STATUS_FINAL,
    ]);
    if (!$attempts) {
        return null;
    }

    $percent = 0.0;
    $count = 0;
    foreach ($attempts as $attempt) {
        $calc = pgosce_calculate_attempt($attempt);
        $percent += $calc['percentage'];
        $count++;
    }

    $average = $count ? $percent / $count : 0;
    $grade = ((float)$pgosce->grade * $average) / 100;

    return (object)[
        'userid' => $userid,
        'rawgrade' => $grade,
        'percentage' => $average,
    ];
}

/**
 * Get enrolled students for the activity.
 *
 * @param context_module $context
 * @return array
 */
function pgosce_get_students(context_module $context) {
    $users = get_enrolled_users($context, 'mod/pgosce:view', 0, 'u.*', 'u.lastname, u.firstname', 0, 0, true);
    foreach ($users as $key => $user) {
        if (has_capability('mod/pgosce:assess', $context, $user->id)) {
            unset($users[$key]);
        }
    }
    return $users;
}
