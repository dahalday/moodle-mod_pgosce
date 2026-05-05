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
 * Normalise a mark input display type.
 *
 * @param string|null $type Submitted type.
 * @param string $default Default to use when invalid.
 * @return string
 */
function pgosce_normalise_mark_input_type($type, $default = 'global') {
    $type = (string)$type;
    return in_array($type, ['global', 'buttons', 'textbox'], true) ? $type : $default;
}

/**
 * Normalise a mark button step.
 *
 * @param mixed $step Submitted step.
 * @param float $default Default to use when invalid.
 * @return float
 */
function pgosce_normalise_mark_button_step($step, $default = 0.0) {
    $step = (float)$step;
    foreach ([0.0, 1.0, 0.5, 0.25] as $allowed) {
        if (abs($step - $allowed) < 0.00001) {
            return $allowed;
        }
    }
    return $default;
}

/**
 * Resolve per-criterion mark display settings against the activity defaults.
 *
 * @param stdClass|array $criterion Criterion record.
 * @param stdClass $pgosce Activity record.
 * @return array [$inputtype, $buttonstep]
 */
function pgosce_resolve_mark_display($criterion, stdClass $pgosce) {
    $criterioninput = is_array($criterion) ? ($criterion['markinputtype'] ?? 'global') :
        ($criterion->markinputtype ?? 'global');
    $criterionstep = is_array($criterion) ? ($criterion['markbuttonstep'] ?? 0) :
        ($criterion->markbuttonstep ?? 0);

    $globalinput = pgosce_normalise_mark_input_type($pgosce->markinputtype ?? 'buttons', 'buttons');
    if ($globalinput === 'global') {
        $globalinput = 'buttons';
    }
    $globalstep = pgosce_normalise_mark_button_step($pgosce->markbuttonstep ?? 0.5, 0.5);
    if (abs($globalstep) < 0.00001) {
        $globalstep = 0.5;
    }

    $inputtype = pgosce_normalise_mark_input_type($criterioninput);
    if ($inputtype === 'global') {
        $inputtype = $globalinput;
    }

    $buttonstep = pgosce_normalise_mark_button_step($criterionstep);
    if (abs($buttonstep) < 0.00001) {
        $buttonstep = $globalstep;
    }

    return [$inputtype, $buttonstep];
}

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
                        [
                            'description' => '',
                            'maxmark' => 1,
                            'markinputtype' => 'global',
                            'markbuttonstep' => 0,
                        ],
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
                    'markinputtype' => pgosce_normalise_mark_input_type($criterion->markinputtype ?? 'global'),
                    'markbuttonstep' => pgosce_normalise_mark_button_step($criterion->markbuttonstep ?? 0),
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
 * Convert plain text blocks into simple HTML for Moodle editor fields.
 *
 * @param string $text
 * @return string
 */
function pgosce_gift_plain_to_html($text) {
    $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
    if ($text === '') {
        return '';
    }

    $paragraphs = preg_split("/\n{2,}/", $text);
    $html = [];
    foreach ($paragraphs as $paragraph) {
        $lines = array_map('s', explode("\n", trim($paragraph)));
        $html[] = '<p>' . implode('<br />', $lines) . '</p>';
    }

    return implode("\n", $html);
}

/**
 * Convert stored rubric HTML into compact text for PG OSCE GIFT export.
 *
 * @param string $html
 * @return string
 */
function pgosce_gift_html_to_text($html) {
    $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $html);
    $text = preg_replace('/<\/\s*(p|div|li|h[1-6])\s*>/i', "\n", $text);
    $text = preg_replace('/<\s*li[^>]*>/i', '- ', $text);
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
    $text = preg_replace("/[ \t]+\n/", "\n", $text);
    $text = preg_replace("/\n{3,}/", "\n\n", $text);

    return trim($text);
}

/**
 * Format a mark for PG OSCE GIFT export.
 *
 * @param float $mark
 * @return string
 */
function pgosce_gift_format_mark($mark) {
    $formatted = rtrim(rtrim(sprintf('%.5F', (float)$mark), '0'), '.');
    return $formatted === '' ? '0' : $formatted;
}

/**
 * Export the current rubric as PG OSCE GIFT-style plain text.
 *
 * @param stdClass $pgosce
 * @return string
 */
function pgosce_export_gift(stdClass $pgosce) {
    $lines = [
        '# PG OSCE GIFT',
        '# Import this text from the PG OSCE activity, Edit rubric > PG OSCE GIFT.',
        '::Station:: ' . $pgosce->name,
        '[Settings]',
        'ShowStudentInstructions: ' . (empty($pgosce->showstudentinstructions) ? 'no' : 'yes'),
        'ReleaseStudentReports: ' . (empty($pgosce->displaystudentreports) ? 'no' : 'yes'),
        'ShowGradesInGradebook: ' . (empty($pgosce->showgradesingradebook) ? 'no' : 'yes'),
        'AssessorIdentifierDisplay: ' . (empty($pgosce->assessoridentifierdisplay) ? 'both' : $pgosce->assessoridentifierdisplay),
        '',
    ];

    foreach (pgosce_get_rubric($pgosce->id) as $section) {
        $lines[] = '[Section] ' . $section['name'];
        $description = pgosce_gift_html_to_text($section['description']);
        if ($description !== '') {
            $lines[] = $description;
        }
        $lines[] = '';

        foreach ($section['questions'] as $question) {
            $lines[] = '[Question] ' . $question['title'];
            $prompt = pgosce_gift_html_to_text($question['prompt']);
            if ($prompt !== '') {
                $lines[] = 'Prompt: ' . str_replace("\n", "\n", $prompt);
            }
            foreach ($question['criteria'] as $criterion) {
                $description = pgosce_gift_html_to_text($criterion['description']);
                $description = trim(preg_replace('/\s+/', ' ', $description));
                $lines[] = '= ' . $description . ' ::' . pgosce_gift_format_mark($criterion['maxmark']);
            }
            $lines[] = '';
        }
    }

    return trim(implode("\n", $lines)) . "\n";
}

/**
 * Parse PG OSCE GIFT-style text into a rubric array.
 *
 * @param string $text
 * @return array
 */
function pgosce_parse_gift($text) {
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $lines = explode("\n", $text);
    $rubric = [];
    $sectionindex = -1;
    $questionindex = -1;
    $mode = '';

    foreach ($lines as $rawline) {
        $line = trim($rawline);

        if ($line === '') {
            if ($mode === 'section' && $sectionindex >= 0 &&
                    trim($rubric[$sectionindex]['description']) !== '') {
                $rubric[$sectionindex]['description'] .= "\n\n";
            } else if ($mode === 'question' && $sectionindex >= 0 && $questionindex >= 0 &&
                    trim($rubric[$sectionindex]['questions'][$questionindex]['prompt']) !== '') {
                $rubric[$sectionindex]['questions'][$questionindex]['prompt'] .= "\n\n";
            }
            continue;
        }

        if (strpos($line, '#') === 0 || preg_match('/^::\s*Station\s*::/i', $line) ||
                preg_match('/^\[Settings\]$/i', $line) ||
                preg_match('/^(ShowStudentInstructions|ReleaseStudentReports|ShowGradesInGradebook|AssessorIDNumberOnly|AssessorIdentifierDisplay)\s*:/i', $line)) {
            continue;
        }

        if (preg_match('/^\[Section(?::|\])\s*(.*?)\]?$/i', $line, $matches)) {
            $name = trim($matches[1]);
            if ($name === '') {
                $name = get_string('section', 'pgosce');
            }
            $rubric[] = [
                'name' => $name,
                'description' => '',
                'questions' => [],
            ];
            $sectionindex = count($rubric) - 1;
            $questionindex = -1;
            $mode = 'section';
            continue;
        }

        if (preg_match('/^\[Question(?::|\])\s*(.*?)\]?$/i', $line, $matches)) {
            if ($sectionindex < 0) {
                $rubric[] = [
                    'name' => get_string('section', 'pgosce'),
                    'description' => '',
                    'questions' => [],
                ];
                $sectionindex = 0;
            }
            $title = trim($matches[1]);
            if ($title === '') {
                $title = get_string('question', 'pgosce');
            }
            $rubric[$sectionindex]['questions'][] = [
                'title' => $title,
                'prompt' => '',
                'criteria' => [],
            ];
            $questionindex = count($rubric[$sectionindex]['questions']) - 1;
            $mode = 'question';
            continue;
        }

        if ($sectionindex >= 0 && $questionindex >= 0 &&
                preg_match('/^(?:=|\*|-)\s*(.*?)\s*::\s*([0-9]+(?:\.[0-9]+)?)\s*$/', $line, $matches)) {
            $rubric[$sectionindex]['questions'][$questionindex]['criteria'][] = [
                'description' => pgosce_gift_plain_to_html($matches[1]),
                'maxmark' => (float)$matches[2],
            ];
            $mode = 'question';
            continue;
        }

        if ($sectionindex >= 0 && $questionindex >= 0) {
            if (preg_match('/^Prompt\s*:\s*(.*)$/i', $line, $matches)) {
                $line = $matches[1];
            }
            if ($rubric[$sectionindex]['questions'][$questionindex]['prompt'] !== '' &&
                    substr($rubric[$sectionindex]['questions'][$questionindex]['prompt'], -2) !== "\n\n") {
                $rubric[$sectionindex]['questions'][$questionindex]['prompt'] .= "\n";
            }
            $rubric[$sectionindex]['questions'][$questionindex]['prompt'] .= $line;
            $mode = 'question';
        } else if ($sectionindex >= 0) {
            if ($rubric[$sectionindex]['description'] !== '' &&
                    substr($rubric[$sectionindex]['description'], -2) !== "\n\n") {
                $rubric[$sectionindex]['description'] .= "\n";
            }
            $rubric[$sectionindex]['description'] .= $line;
            $mode = 'section';
        }
    }

    foreach ($rubric as $sectionkey => $section) {
        $rubric[$sectionkey]['description'] = pgosce_gift_plain_to_html($section['description']);
        foreach ($section['questions'] as $questionkey => $question) {
            $rubric[$sectionkey]['questions'][$questionkey]['prompt'] = pgosce_gift_plain_to_html($question['prompt']);
        }
    }

    return $rubric;
}

/**
 * Parse optional PG OSCE GIFT settings.
 *
 * @param string $text
 * @return array
 */
function pgosce_parse_gift_settings($text) {
    $settings = [];
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    foreach (explode("\n", $text) as $line) {
        $line = trim($line);
        if (preg_match('/^ShowStudentInstructions\s*:\s*(yes|no|1|0|true|false)$/i', $line, $matches)) {
            $settings['showstudentinstructions'] = in_array(strtolower($matches[1]), ['yes', '1', 'true']) ? 1 : 0;
        }
        if (preg_match('/^ReleaseStudentReports\s*:\s*(yes|no|1|0|true|false)$/i', $line, $matches)) {
            $settings['displaystudentreports'] = in_array(strtolower($matches[1]), ['yes', '1', 'true']) ? 1 : 0;
        }
        if (preg_match('/^ShowGradesInGradebook\s*:\s*(yes|no|1|0|true|false)$/i', $line, $matches)) {
            $settings['showgradesingradebook'] = in_array(strtolower($matches[1]), ['yes', '1', 'true']) ? 1 : 0;
        }
        if (preg_match('/^AssessorIdentifierDisplay\s*:\s*(both|moodleuserid|idnumber)$/i', $line, $matches)) {
            $settings['assessoridentifierdisplay'] = strtolower($matches[1]);
        }
    }

    return $settings;
}

/**
 * Total possible marks from an unsaved rubric array.
 *
 * @param array $rubric
 * @return float
 */
function pgosce_get_total_maxmark_from_rubric(array $rubric) {
    $total = 0.0;
    foreach ($rubric as $section) {
        if (empty($section['questions']) || !is_array($section['questions'])) {
            continue;
        }
        foreach ($section['questions'] as $question) {
            if (empty($question['criteria']) || !is_array($question['criteria'])) {
                continue;
            }
            foreach ($question['criteria'] as $criterion) {
                $total += isset($criterion['maxmark']) ? (float)$criterion['maxmark'] : 0.0;
            }
        }
    }

    return $total;
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
                $markinputtype = pgosce_normalise_mark_input_type($criterion['markinputtype'] ?? 'global');
                $markbuttonstep = pgosce_normalise_mark_button_step($criterion['markbuttonstep'] ?? 0);
                $criterionrecord = (object)[
                    'questionid' => $questionid,
                    'description' => clean_text($criterion['description'], FORMAT_HTML),
                    'maxmark' => max(0, $maxmark),
                    'markinputtype' => $markinputtype,
                    'markbuttonstep' => $markbuttonstep,
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

/**
 * Get a display-safe student identifier.
 *
 * @param stdClass $student
 * @return string
 */
function pgosce_get_student_identifier(stdClass $student, $mode = 'both') {
    if (!in_array($mode, ['both', 'moodleuserid', 'idnumber'])) {
        $mode = 'both';
    }

    $identifiers = [];
    $idnumber = isset($student->idnumber) ? trim($student->idnumber) : '';
    if ($mode === 'both' || $mode === 'moodleuserid' || ($mode === 'idnumber' && $idnumber === '')) {
        $identifiers[] = html_writer::span(get_string('moodleuseridvalue', 'pgosce', (int)$student->id),
            'pgosce-student-identifier-item');
    }
    if (($mode === 'both' || $mode === 'idnumber') && $idnumber !== '') {
        $identifiers[] = html_writer::span(get_string('studentidvalue', 'pgosce', s($idnumber)),
            'pgosce-student-identifier-item');
    }

    return implode(' ', $identifiers);
}

/**
 * Format student identity for the current assessor/editor view.
 *
 * Editing teachers, managers, admins and course creators see name plus Moodle
 * user identifiers. Assigned non-editing teachers see identifiers only.
 *
 * @param stdClass $student
 * @param stdClass $pgosce
 * @param context_module $context
 * @return string
 */
function pgosce_format_student_display(stdClass $student, stdClass $pgosce, context_module $context) {
    $identifier = pgosce_get_student_identifier($student, 'both');
    if (!pgosce_can_view_student_names($context) && has_capability('mod/pgosce:assess', $context)) {
        $mode = empty($pgosce->assessoridentifierdisplay) ? 'both' : $pgosce->assessoridentifierdisplay;
        return html_writer::span(pgosce_get_student_identifier($student, $mode), 'pgosce-student-identifier');
    }

    return html_writer::span(s(fullname($student)), 'pgosce-student-fullname') .
        html_writer::span($identifier, 'text-muted small d-block pgosce-student-identifier');
}

/**
 * Can the current user see student names in assessor/editor views?
 *
 * @param context_module $context
 * @param int|null $userid
 * @return bool
 */
function pgosce_can_view_student_names(context_module $context, $userid = null) {
    global $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    if (pgosce_has_full_access($context, $userid)) {
        return true;
    }

    $coursecontext = $context->get_course_context(IGNORE_MISSING);
    if ($coursecontext && (has_capability('moodle/course:update', $coursecontext, $userid) ||
            has_capability('moodle/course:create', $coursecontext, $userid))) {
        return true;
    }

    return has_capability('moodle/course:create', context_system::instance(), $userid);
}

/**
 * Does this user have unrestricted PG OSCE management access?
 *
 * @param context_module $context
 * @param int|null $userid
 * @return bool
 */
function pgosce_has_full_access(context_module $context, $userid = null) {
    global $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $isswitchedrole = false;
    if ($userid == $USER->id && function_exists('is_role_switched')) {
        $coursecontext = $context->get_course_context(IGNORE_MISSING);
        if ($coursecontext) {
            $isswitchedrole = is_role_switched($coursecontext->instanceid);
        }
    }

    if ($isswitchedrole) {
        return has_capability('mod/pgosce:manage', $context, $userid) ||
            has_capability('mod/pgosce:assignassessors', $context, $userid);
    }

    return has_capability('mod/pgosce:manage', $context, $userid) ||
        has_capability('mod/pgosce:assignassessors', $context, $userid) ||
        has_capability('moodle/site:config', context_system::instance(), $userid);
}

/**
 * Is the user assigned to assess this station, either directly or by course default?
 *
 * @param stdClass $pgosce
 * @param int $userid
 * @return bool
 */
function pgosce_is_assigned_assessor(stdClass $pgosce, $userid) {
    global $DB;

    return $DB->record_exists('pgosce_assessor', [
        'course' => $pgosce->course,
        'pgosceid' => 0,
        'userid' => $userid,
    ]) || $DB->record_exists('pgosce_assessor', [
        'course' => $pgosce->course,
        'pgosceid' => $pgosce->id,
        'userid' => $userid,
    ]);
}

/**
 * Can this user assess this specific PG OSCE station?
 *
 * @param stdClass $pgosce
 * @param context_module $context
 * @param int|null $userid
 * @return bool
 */
function pgosce_can_assess_station(stdClass $pgosce, context_module $context, $userid = null) {
    global $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }
    if (!has_capability('mod/pgosce:assess', $context, $userid)) {
        return false;
    }
    if (pgosce_has_full_access($context, $userid)) {
        return true;
    }

    return pgosce_is_assigned_assessor($pgosce, $userid);
}

/**
 * Can this user see this PG OSCE activity in the course or direct activity view?
 *
 * Editing teachers, managers and administrators always see it. Non-editing
 * teachers see only assigned stations. Students see the activity when
 * candidate section instructions are released, or as a minimal gradebook-only
 * shell when gradebook visibility is enabled.
 *
 * @param stdClass $pgosce
 * @param context_module $context
 * @param int|null $userid
 * @return bool
 */
function pgosce_can_view_activity(stdClass $pgosce, context_module $context, $userid = null) {
    global $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }
    if (!has_capability('mod/pgosce:view', $context, $userid)) {
        return false;
    }
    if (pgosce_has_full_access($context, $userid)) {
        return true;
    }
    if (has_capability('mod/pgosce:assess', $context, $userid)) {
        return pgosce_is_assigned_assessor($pgosce, $userid);
    }

    return !empty($pgosce->showstudentinstructions) || !empty($pgosce->showgradesingradebook);
}

/**
 * Get possible non-editing teacher assessors for assignment.
 *
 * @param context_module $context
 * @return array
 */
function pgosce_get_assignable_assessors(context_module $context) {
    $users = get_enrolled_users($context, 'mod/pgosce:assess', 0, 'u.*', 'u.lastname, u.firstname', 0, 0, true);
    foreach ($users as $key => $user) {
        if (pgosce_has_full_access($context, $user->id)) {
            unset($users[$key]);
        }
    }

    return $users;
}

/**
 * Save assigned assessors for a course default or a single station.
 *
 * @param int $courseid
 * @param int $pgosceid Zero means all PG OSCE stations in the course.
 * @param array $userids
 */
function pgosce_save_assessor_assignments($courseid, $pgosceid, array $userids) {
    global $DB;

    $DB->delete_records('pgosce_assessor', ['course' => $courseid, 'pgosceid' => $pgosceid]);
    $now = time();
    foreach (array_unique($userids) as $userid) {
        if (!$userid) {
            continue;
        }
        $DB->insert_record('pgosce_assessor', (object)[
            'course' => $courseid,
            'pgosceid' => $pgosceid,
            'userid' => $userid,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
    rebuild_course_cache($courseid, true);
}
