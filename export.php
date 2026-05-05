<?php
// This file is part of Moodle - http://moodle.org/

/**
 * CSV exports for PG OSCE.
 *
 * @package    mod_pgosce
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/pgosce/lib.php');

$id = required_param('id', PARAM_INT);
$questionid = optional_param('questionid', 0, PARAM_INT);
$mode = optional_param('mode', '', PARAM_ALPHA);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'pgosce');
$pgosce = $DB->get_record('pgosce', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pgosce:export', $context);

/**
 * Strip editor HTML for CSV cells.
 *
 * @param string $value
 * @return string
 */
function pgosce_export_text($value) {
    return trim(html_to_text($value, 0, false));
}

/**
 * Get a flat list of question and criterion export columns.
 *
 * @param array $rubric
 * @param int $questionid
 * @return array
 */
function pgosce_export_columns(array $rubric, $questionid = 0) {
    $columns = [];
    $questionnumber = 1;
    foreach ($rubric as $section) {
        foreach ($section['questions'] as $question) {
            $criteria = [];
            $questionmax = 0;
            $criterionnumber = 1;
            foreach ($question['criteria'] as $criterion) {
                $questionmax += (float)$criterion['maxmark'];
                $criteria[] = [
                    'id' => $criterion['id'],
                    'label' => 'Criterion ' . $questionnumber . chr(96 + min($criterionnumber, 26)) .
                        ' /' . format_float($criterion['maxmark'], 2),
                    'description' => pgosce_export_text($criterion['description']),
                    'maxmark' => (float)$criterion['maxmark'],
                ];
                $criterionnumber++;
            }
            if (!$questionid || $question['id'] == $questionid) {
                $columns[] = [
                    'id' => $question['id'],
                    'number' => $questionnumber,
                    'section' => $section['name'],
                    'title' => $question['title'],
                    'label' => 'Q. ' . $questionnumber . ' /' . format_float($questionmax, 2),
                    'maxmark' => $questionmax,
                    'criteria' => $criteria,
                ];
            }
            $questionnumber++;
        }
    }
    return $columns;
}

/**
 * Build a status label for an attempt.
 *
 * @param stdClass $attempt
 * @return string
 */
function pgosce_export_attempt_status(stdClass $attempt) {
    return $attempt->status == PGOSCE_STATUS_FINAL ? 'Finished' : 'In progress';
}

/**
 * Build summary row headers.
 *
 * @param array $columns
 * @param float $totalmax
 * @return array
 */
function pgosce_export_summary_headers(array $columns, $totalmax) {
    $headers = [
        'Last name',
        'First name',
        'ID',
        'Email address',
        'Status',
        'Assessor',
        'Completed',
        'Section',
        'Grade/' . format_float($totalmax, 2),
    ];

    foreach ($columns as $question) {
        $headers[] = $question['label'];
        foreach ($question['criteria'] as $criterion) {
            $headers[] = $criterion['label'];
        }
    }
    $headers[] = 'Percent';
    return $headers;
}

/**
 * Attempt summary row in the Excel-style format.
 *
 * @param stdClass $pgosce
 * @param stdClass $attempt
 * @param stdClass $student
 * @param stdClass $assessor
 * @param array $columns
 * @param float $totalmax
 * @return array
 */
function pgosce_export_attempt_summary_row(stdClass $pgosce, stdClass $attempt, stdClass $student, stdClass $assessor,
        array $columns, $totalmax) {
    $scores = pgosce_get_scores($attempt->id);
    $calc = pgosce_calculate_attempt($attempt);
    $sections = [];
    foreach ($columns as $question) {
        $sections[$question['section']] = $question['section'];
    }

    $row = [
        $student->lastname,
        $student->firstname,
        $student->id,
        $student->email,
        pgosce_export_attempt_status($attempt),
        fullname($assessor),
        $attempt->status == PGOSCE_STATUS_FINAL ? userdate($attempt->timemodified) : '',
        implode(', ', $sections),
        format_float($calc['earned'], 2),
    ];

    foreach ($columns as $question) {
        $questiontotal = 0;
        $criterionvalues = [];
        foreach ($question['criteria'] as $criterion) {
            $mark = isset($scores[$criterion['id']]) ? (float)$scores[$criterion['id']]->mark : 0;
            $mark = min($criterion['maxmark'], max(0, $mark));
            $questiontotal += $mark;
            $criterionvalues[] = format_float($mark, 2);
        }
        $row[] = format_float($questiontotal, 2);
        foreach ($criterionvalues as $value) {
            $row[] = $value;
        }
    }

    $row[] = $totalmax > 0 ? format_float(($calc['earned'] / $totalmax) * 100, 2) : '0';
    return $row;
}

/**
 * Keep only finalized marker submissions for averaged exports.
 *
 * Draft saves are useful in individual exports, but should not lower an
 * official average until the marker has used Save and finalize.
 *
 * @param array $attempts
 * @return array
 */
function pgosce_export_final_attempts(array $attempts) {
    return array_values(array_filter($attempts, function($attempt) {
        return $attempt->status == PGOSCE_STATUS_FINAL;
    }));
}

/**
 * Average summary row in the Excel-style format.
 *
 * @param stdClass $student
 * @param array $attempts
 * @param array $columns
 * @param float $totalmax
 * @return array
 */
function pgosce_export_average_summary_row(stdClass $student, array $attempts, array $columns, $totalmax) {
    $finalattempts = pgosce_export_final_attempts($attempts);
    $attemptcount = count($finalattempts);
    $excludedcount = count($attempts) - $attemptcount;
    $latestcompleted = 0;
    $sections = [];
    $criteriontotals = [];
    $criterioncounts = [];

    foreach ($finalattempts as $attempt) {
        $latestcompleted = max($latestcompleted, (int)$attempt->timemodified);
        $scores = pgosce_get_scores($attempt->id);
        foreach ($columns as $question) {
            $sections[$question['section']] = $question['section'];
            foreach ($question['criteria'] as $criterion) {
                $mark = isset($scores[$criterion['id']]) ? (float)$scores[$criterion['id']]->mark : 0;
                $mark = min($criterion['maxmark'], max(0, $mark));
                if (!isset($criteriontotals[$criterion['id']])) {
                    $criteriontotals[$criterion['id']] = 0;
                    $criterioncounts[$criterion['id']] = 0;
                }
                $criteriontotals[$criterion['id']] += $mark;
                $criterioncounts[$criterion['id']]++;
            }
        }
    }

    foreach ($columns as $question) {
        $sections[$question['section']] = $question['section'];
    }

    $markerlabel = 'Average of ' . $attemptcount . ' finalized marker' . ($attemptcount == 1 ? '' : 's');
    if ($excludedcount > 0) {
        $markerlabel .= ' (' . $excludedcount . ' draft' . ($excludedcount == 1 ? '' : 's') . ' excluded)';
    }

    $totalearned = 0;
    $row = [
        $student->lastname,
        $student->firstname,
        $student->id,
        $student->email,
        $attemptcount ? 'Finished' : 'Unmarked',
        $markerlabel,
        $latestcompleted ? userdate($latestcompleted) : '',
        implode(', ', $sections),
        '',
    ];

    if (!$attemptcount) {
        foreach ($columns as $question) {
            $row[] = '';
            foreach ($question['criteria'] as $unused) {
                $row[] = '';
            }
        }
        $row[] = '';
        return $row;
    }

    $questionvalues = [];
    foreach ($columns as $question) {
        $questiontotal = 0;
        $criterionvalues = [];
        foreach ($question['criteria'] as $criterion) {
            $count = !empty($criterioncounts[$criterion['id']]) ? $criterioncounts[$criterion['id']] : 0;
            $average = $count ? $criteriontotals[$criterion['id']] / $count : 0;
            $questiontotal += $average;
            $criterionvalues[] = format_float($average, 2);
        }
        $totalearned += $questiontotal;
        $questionvalues[] = format_float($questiontotal, 2);
        foreach ($criterionvalues as $value) {
            $questionvalues[] = $value;
        }
    }

    $row[8] = format_float($totalearned, 2);
    foreach ($questionvalues as $value) {
        $row[] = $value;
    }
    $row[] = $totalmax > 0 ? format_float(($totalearned / $totalmax) * 100, 2) : '0';
    return $row;
}

/**
 * Send CSV headers.
 *
 * @param string $filename
 */
function pgosce_export_start_csv($filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . clean_filename($filename) . '"');
    echo "\xEF\xBB\xBF";
}

$rubric = pgosce_get_rubric($pgosce->id);
$columns = pgosce_export_columns($rubric, $questionid);
$totalmax = 0;
foreach ($columns as $question) {
    $totalmax += $question['maxmark'];
}

if ($mode === '') {
    $PAGE->set_url('/mod/pgosce/export.php', ['id' => $id, 'questionid' => $questionid]);
    $PAGE->set_context($context);
    $PAGE->set_cm($cm, $course);
    $PAGE->set_title(get_string('export', 'pgosce'));
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('export', 'pgosce'));
    echo html_writer::tag('p', get_string('exportoptionsintro', 'pgosce'));

    $links = [
        'summaryavg' => get_string('exportsummaryavg', 'pgosce'),
        'summaryindividual' => get_string('exportsummaryindividual', 'pgosce'),
        'detailavg' => get_string('exportdetailavg', 'pgosce'),
        'detailindividual' => get_string('exportdetailindividual', 'pgosce'),
    ];
    echo html_writer::start_tag('ul');
    foreach ($links as $exportmode => $label) {
        $url = new moodle_url('/mod/pgosce/export.php', ['id' => $id, 'questionid' => $questionid, 'mode' => $exportmode]);
        echo html_writer::tag('li', html_writer::link($url, $label));
    }
    echo html_writer::end_tag('ul');
    echo html_writer::link(new moodle_url('/mod/pgosce/view.php', ['id' => $cm->id]),
        get_string('backtoactivity', 'pgosce'), ['class' => 'btn btn-secondary']);
    echo $OUTPUT->footer();
    exit;
}

$attempts = $DB->get_records('pgosce_attempt', ['pgosceid' => $pgosce->id], 'userid ASC, assessorid ASC');
$basename = format_string($pgosce->name) . ($questionid ? '-question-' . $questionid : '-all') . '-' . $mode . '.csv';
pgosce_export_start_csv($basename);
$out = fopen('php://output', 'w');

if ($mode === 'summaryavg' || $mode === 'summaryindividual') {
    fputcsv($out, pgosce_export_summary_headers($columns, $totalmax));

    if ($mode === 'summaryindividual') {
        foreach ($attempts as $attempt) {
            $student = core_user::get_user($attempt->userid);
            $assessor = core_user::get_user($attempt->assessorid);
            fputcsv($out, pgosce_export_attempt_summary_row($pgosce, $attempt, $student, $assessor, $columns, $totalmax));
        }
    } else {
        $byuser = [];
        foreach ($attempts as $attempt) {
            if (!isset($byuser[$attempt->userid])) {
                $byuser[$attempt->userid] = [];
            }
            $byuser[$attempt->userid][] = $attempt;
        }
        foreach ($byuser as $userid => $userattempts) {
            $student = core_user::get_user($userid);
            fputcsv($out, pgosce_export_average_summary_row($student, $userattempts, $columns, $totalmax));
        }
    }
} else {
    fputcsv($out, [
        'Student ID',
        'Student',
        'Assessor ID',
        'Assessor',
        'Status',
        'Section',
        'Question',
        'Criterion',
        'Mark',
        'Max mark',
        'Comment',
        'Attempt total',
        'Attempt percent',
        'Course grade',
    ]);

    if ($mode === 'detailavg') {
        $byuser = [];
        foreach ($attempts as $attempt) {
            if (!isset($byuser[$attempt->userid])) {
                $byuser[$attempt->userid] = [];
            }
            $byuser[$attempt->userid][] = $attempt;
        }
        foreach ($byuser as $userid => $userattempts) {
            $student = core_user::get_user($userid);
            $finalattempts = pgosce_export_final_attempts($userattempts);
            $attemptcount = count($finalattempts);
            $excludedcount = count($userattempts) - $attemptcount;
            $markerlabel = 'Average of ' . $attemptcount . ' finalized marker' . ($attemptcount == 1 ? '' : 's');
            if ($excludedcount > 0) {
                $markerlabel .= ' (' . $excludedcount . ' draft' . ($excludedcount == 1 ? '' : 's') . ' excluded)';
            }
            $criteriontotals = [];
            $criterioncounts = [];
            $totalearned = 0;
            foreach ($finalattempts as $attempt) {
                $scores = pgosce_get_scores($attempt->id);
                foreach ($columns as $question) {
                    foreach ($question['criteria'] as $criterion) {
                        $mark = isset($scores[$criterion['id']]) ? (float)$scores[$criterion['id']]->mark : 0;
                        $mark = min($criterion['maxmark'], max(0, $mark));
                        if (!isset($criteriontotals[$criterion['id']])) {
                            $criteriontotals[$criterion['id']] = 0;
                            $criterioncounts[$criterion['id']] = 0;
                        }
                        $criteriontotals[$criterion['id']] += $mark;
                        $criterioncounts[$criterion['id']]++;
                    }
                }
            }
            if ($attemptcount) {
                foreach ($columns as $question) {
                    foreach ($question['criteria'] as $criterion) {
                        $count = !empty($criterioncounts[$criterion['id']]) ? $criterioncounts[$criterion['id']] : 0;
                        $mark = $count ? $criteriontotals[$criterion['id']] / $count : 0;
                        $totalearned += $mark;
                    }
                }
            }
            $percent = $totalmax > 0 ? ($totalearned / $totalmax) * 100 : 0;
            foreach ($columns as $question) {
                foreach ($question['criteria'] as $criterion) {
                    $count = !empty($criterioncounts[$criterion['id']]) ? $criterioncounts[$criterion['id']] : 0;
                    $mark = ($attemptcount && $count) ? $criteriontotals[$criterion['id']] / $count : null;
                    fputcsv($out, [
                        $student->id,
                        fullname($student),
                        '',
                        $markerlabel,
                        $attemptcount ? 'Finished' : 'Unmarked',
                        $question['section'],
                        $question['title'],
                        pgosce_export_text($criterion['description']),
                        $mark === null ? '' : format_float($mark, 2),
                        $criterion['maxmark'],
                        '',
                        $attemptcount ? format_float($totalearned, 2) : '',
                        $attemptcount ? format_float($percent, 2) : '',
                        '',
                    ]);
                }
            }
        }
    } else {
        foreach ($attempts as $attempt) {
            $student = core_user::get_user($attempt->userid);
            $assessor = core_user::get_user($attempt->assessorid);
            $scores = pgosce_get_scores($attempt->id);
            $calc = pgosce_calculate_attempt($attempt);
            $grade = pgosce_calculate_student_grade($pgosce, $attempt->userid);
            foreach ($columns as $question) {
                foreach ($question['criteria'] as $criterion) {
                    $score = isset($scores[$criterion['id']]) ? $scores[$criterion['id']] : null;
                    fputcsv($out, [
                        $student->id,
                        fullname($student),
                        $assessor->id,
                        fullname($assessor),
                        pgosce_export_attempt_status($attempt),
                        $question['section'],
                        $question['title'],
                        pgosce_export_text($criterion['description']),
                        $score ? $score->mark : 0,
                        $criterion['maxmark'],
                        $score ? $score->comment : '',
                        format_float($calc['earned'], 2),
                        format_float($calc['percentage'], 2),
                        $grade ? format_float($grade->rawgrade, 2) : '',
                    ]);
                }
            }
        }
    }
}

fclose($out);
exit;
