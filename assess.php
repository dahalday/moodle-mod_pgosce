<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Assessment entry page for PG OSCE.
 *
 * @package    mod_pgosce
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/pgosce/lib.php');

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$attemptid = optional_param('attemptid', 0, PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'pgosce');
$pgosce = $DB->get_record('pgosce', ['id' => $cm->instance], '*', MUST_EXIST);
$student = core_user::get_user($userid, '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pgosce:assess', $context);
if (!pgosce_can_assess_station($pgosce, $context)) {
    throw new moodle_exception('notassignedassessor', 'pgosce');
}

$criteria = pgosce_get_criteria($pgosce->id);
if (!$criteria) {
    redirect(new moodle_url('/mod/pgosce/manage.php', ['id' => $cm->id]), get_string('createrubricfirst', 'pgosce'),
        null, \core\output\notification::NOTIFY_WARNING);
}

$hasfullaccess = pgosce_has_full_access($context);
if ($attemptid && $hasfullaccess) {
    $attempt = $DB->get_record('pgosce_attempt', [
        'id' => $attemptid,
        'pgosceid' => $pgosce->id,
        'userid' => $userid,
    ], '*', MUST_EXIST);
} else {
    $attempt = pgosce_get_or_create_attempt($pgosce->id, $userid, $USER->id);
}
if (!$hasfullaccess && $attempt->status == PGOSCE_STATUS_FINAL) {
    throw new moodle_exception('finalattemptlocked', 'pgosce');
}

if (data_submitted() && confirm_sesskey()) {
    foreach ($criteria as $criterion) {
        $mark = optional_param('mark_' . $criterion->id, 0, PARAM_FLOAT);
        $comment = optional_param('comment_' . $criterion->id, '', PARAM_TEXT);
        $mark = min((float)$criterion->maxmark, max(0, (float)$mark));
        $existing = $DB->get_record('pgosce_score', ['attemptid' => $attempt->id, 'criterionid' => $criterion->id]);
        $record = (object)[
            'attemptid' => $attempt->id,
            'criterionid' => $criterion->id,
            'mark' => $mark,
            'comment' => $comment,
        ];
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('pgosce_score', $record);
        } else {
            $DB->insert_record('pgosce_score', $record);
        }
    }

    $attempt->generalcomment = optional_param('generalcomment', '', PARAM_TEXT);
    if (optional_param('finalize', 0, PARAM_BOOL)) {
        $attempt->status = PGOSCE_STATUS_FINAL;
    }
    $attempt->timemodified = time();
    $DB->update_record('pgosce_attempt', $attempt);
    pgosce_update_grades($pgosce, $userid);

    $message = $attempt->status == PGOSCE_STATUS_FINAL ? get_string('finalized', 'pgosce') : get_string('saved', 'pgosce');
    redirect(new moodle_url('/mod/pgosce/view.php', ['id' => $cm->id]), $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

$pageparams = ['id' => $id, 'userid' => $userid];
if ($attemptid && $hasfullaccess) {
    $pageparams['attemptid'] = $attemptid;
}
$PAGE->set_url('/mod/pgosce/assess.php', $pageparams);
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(get_string('assess', 'pgosce'));
$PAGE->set_heading($course->fullname);
$PAGE->requires->css(new moodle_url('/mod/pgosce/styles.css'));
$timelimitseconds = empty($pgosce->timelimit) ? 0 : ((int)$pgosce->timelimit * 60);
$timerwarnfirstseconds = empty($pgosce->timerwarnfirst) ? 0 : ((int)$pgosce->timerwarnfirst * 60);
$timerwarnsecondseconds = empty($pgosce->timerwarnsecond) ? 0 : ((int)$pgosce->timerwarnsecond * 60);
$timerwarningtemplate = addslashes_js(get_string('timerwarning', 'pgosce', '__MINUTES__'));
$timerexpired = addslashes_js(get_string('timerexpired', 'pgosce'));
$PAGE->requires->js_init_code("
(function() {
    function updateScore() {
        var total = 0;
        var max = 0;
        var inputs = document.querySelectorAll('.pgosce-score-input');
        for (var i = 0; i < inputs.length; i++) {
            total += parseFloat(inputs[i].value || '0');
            max += parseFloat(inputs[i].getAttribute('data-max') || '0');
        }
        var totalEl = document.getElementById('pgosce-live-total');
        var percentEl = document.getElementById('pgosce-live-percent');
        if (totalEl) {
            totalEl.textContent = total.toFixed(2) + ' / ' + max.toFixed(2);
        }
        if (percentEl) {
            percentEl.textContent = max > 0 ? ((total / max) * 100).toFixed(2) + '%' : '0%';
        }
    }
    document.addEventListener('click', function(e) {
        if (e.target.className.indexOf('pgosce-score-button') === -1) {
            return;
        }
        var button = e.target;
        var input = document.getElementById(button.getAttribute('data-input'));
        if (!input) {
            return;
        }
        input.value = button.getAttribute('data-value');
        var group = button.parentNode.querySelectorAll('.pgosce-score-button');
        for (var i = 0; i < group.length; i++) {
            group[i].className = group[i].className.replace(' active', '');
        }
        button.className += ' active';
        updateScore();
    });
    document.addEventListener('input', function(e) {
        if (e.target.className.indexOf('pgosce-score-input') !== -1) {
            updateScore();
        }
    });
    var timerLimit = " . $timelimitseconds . ";
    if (timerLimit > 0) {
        var remaining = timerLimit;
        var warned = {};
        var warningTemplate = '" . $timerwarningtemplate . "';
        var expiredText = '" . $timerexpired . "';
        var warningTimes = [" . $timerwarnfirstseconds . ", " . $timerwarnsecondseconds . "];
        var timerEl = document.getElementById('pgosce-timer-value');
        var timerBox = document.getElementById('pgosce-timer-box');
        var timerToast = null;
        function formatTime(seconds) {
            var minutes = Math.floor(seconds / 60);
            var secs = seconds % 60;
            return minutes + ':' + (secs < 10 ? '0' : '') + secs;
        }
        function showTimerToast(message) {
            if (!timerToast) {
                timerToast = document.createElement('div');
                timerToast.className = 'pgosce-timer-toast';
                timerToast.setAttribute('role', 'status');
                timerToast.setAttribute('aria-live', 'polite');
                document.body.appendChild(timerToast);
            }
            timerToast.textContent = message;
            timerToast.className = 'pgosce-timer-toast show';
            window.setTimeout(function() {
                if (timerToast) {
                    timerToast.className = 'pgosce-timer-toast';
                }
            }, 3000);
        }
        function showWarning(seconds) {
            if (!seconds || warned[seconds]) {
                return;
            }
            warned[seconds] = true;
            var minutes = Math.ceil(seconds / 60);
            var message = warningTemplate.replace('__MINUTES__', minutes);
            if (timerBox) {
                timerBox.className += ' pgosce-timer-warning';
            }
            showTimerToast(message);
        }
        function tickTimer() {
            if (timerEl) {
                timerEl.textContent = remaining > 0 ? formatTime(remaining) : expiredText;
            }
            for (var i = 0; i < warningTimes.length; i++) {
                if (remaining === warningTimes[i]) {
                    showWarning(warningTimes[i]);
                }
            }
            if (remaining <= 0) {
                if (timerBox) {
                    timerBox.className += ' pgosce-timer-expired';
                }
                return;
            }
            remaining--;
            window.setTimeout(tickTimer, 1000);
        }
        tickTimer();
    }
    updateScore();
})();");

$rubric = pgosce_get_rubric($pgosce->id);
$scores = pgosce_get_scores($attempt->id);
$calc = pgosce_calculate_attempt($attempt);
$markinputtype = empty($pgosce->markinputtype) ? 'buttons' : $pgosce->markinputtype;
$markbuttonstep = empty($pgosce->markbuttonstep) ? 0.5 : (float)$pgosce->markbuttonstep;
if (!in_array($markbuttonstep, [1.0, 0.5, 0.25])) {
    $markbuttonstep = 0.5;
}

echo $OUTPUT->header();
echo html_writer::start_div('pgosce-assessment');
echo html_writer::start_div('pgosce-assessment-topbar');
echo html_writer::div(
    html_writer::span(get_string('student', 'pgosce'), 'pgosce-stat-label') .
    html_writer::span(fullname($student), 'pgosce-stat-value'),
    'pgosce-stat'
);
echo html_writer::div(
    html_writer::span(format_string($pgosce->name), 'pgosce-stat-label') .
    html_writer::span(get_string('assess', 'pgosce'), 'pgosce-stat-value'),
    'pgosce-stat'
);
echo html_writer::div(
    html_writer::span(get_string('totals', 'pgosce'), 'pgosce-stat-label') .
    html_writer::span(format_float($calc['earned'], 2) . ' / ' . format_float($calc['max'], 2), 'pgosce-stat-value',
        ['id' => 'pgosce-live-total']),
    'pgosce-stat'
);
echo html_writer::div(
    html_writer::span(get_string('percentage', 'pgosce'), 'pgosce-stat-label') .
    html_writer::span(format_float($calc['percentage'], 2) . '%', 'pgosce-stat-value',
        ['id' => 'pgosce-live-percent']),
    'pgosce-stat'
);
echo html_writer::div(
    html_writer::span(get_string('timerremaining', 'pgosce'), 'pgosce-stat-label') .
    html_writer::span($timelimitseconds > 0 ? format_float($pgosce->timelimit, 0) . ':00' : get_string('timeroff', 'pgosce'),
        'pgosce-stat-value', ['id' => 'pgosce-timer-value']),
    'pgosce-stat',
    ['id' => 'pgosce-timer-box']
);
echo html_writer::end_div();

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

$sectionnumber = 1;
foreach ($rubric as $section) {
    echo html_writer::start_tag('details', ['class' => 'pgosce-section-card', 'open' => 'open']);
    echo html_writer::start_tag('summary');
    echo html_writer::start_div('pgosce-section-title');
    echo html_writer::span($sectionnumber, 'pgosce-section-number');
    echo html_writer::span(format_string($section['name']), 'pgosce-section-name');
    echo html_writer::end_div();
    echo html_writer::end_tag('summary');
    if (!empty($section['description'])) {
        echo html_writer::div(
            pgosce_format_editor_content($context, PGOSCE_FILEAREA_SECTION, $section['id'], $section['description']),
            'pgosce-section-description'
        );
    }
    foreach ($section['questions'] as $question) {
        echo html_writer::start_div('pgosce-question');
        echo html_writer::div(format_string($question['title']), 'pgosce-question-title');
        if (!empty($question['prompt'])) {
            echo pgosce_format_editor_content($context, PGOSCE_FILEAREA_QUESTION, $question['id'], $question['prompt']);
        }
        foreach ($question['criteria'] as $criterion) {
            $score = isset($scores[$criterion['id']]) ? $scores[$criterion['id']] : null;
            $currentmark = $score ? (float)$score->mark : 0;
            $maxmark = (float)$criterion['maxmark'];
            $inputid = 'id_mark_' . $criterion['id'];
            [$criterioninputtype, $criterionbuttonstep] = pgosce_resolve_mark_display($criterion, $pgosce);
            $buttoncount = (int)round($maxmark / $criterionbuttonstep);
            $supportsbuttons = $criterioninputtype === 'buttons' &&
                abs(($buttoncount * $criterionbuttonstep) - $maxmark) < 0.00001 &&
                $maxmark <= 10;

            echo html_writer::start_div('pgosce-criterion-row');
            echo html_writer::start_div();
            echo html_writer::div(
                pgosce_format_editor_content(
                    $context,
                    PGOSCE_FILEAREA_CRITERION,
                    $criterion['id'],
                    $criterion['description']
                ),
                'pgosce-criterion-text'
            );
            $commentinput = html_writer::empty_tag('input', [
                'type' => 'text',
                'name' => 'comment_' . $criterion['id'],
                'value' => $score ? s($score->comment) : '',
                'class' => 'form-control',
                'placeholder' => get_string('comment', 'pgosce'),
            ]);
            echo html_writer::div($commentinput, 'pgosce-comment');
            echo html_writer::end_div();

            echo html_writer::start_div('pgosce-mark-entry');
            if ($supportsbuttons) {
                echo html_writer::empty_tag('input', [
                    'type' => 'hidden',
                    'id' => $inputid,
                    'name' => 'mark_' . $criterion['id'],
                    'value' => $currentmark,
                    'class' => 'pgosce-score-input',
                    'data-max' => $maxmark,
                ]);
                echo html_writer::start_div('pgosce-score-buttons');
                for ($markstep = 0; $markstep <= $buttoncount; $markstep++) {
                    $mark = $markstep * $criterionbuttonstep;
                    $marklabel = abs($mark - round($mark)) < 0.00001 ?
                        (string)(int)$mark : rtrim(rtrim(number_format($mark, 2, '.', ''), '0'), '.');
                    $classes = 'pgosce-score-button' . ($mark == 0 ? ' zero' : '');
                    if (abs($currentmark - $mark) < 0.00001) {
                        $classes .= ' active';
                    }
                    echo html_writer::tag('button', $marklabel, [
                        'type' => 'button',
                        'class' => $classes,
                        'data-input' => $inputid,
                        'data-value' => $mark,
                    ]);
                }
                echo html_writer::end_div();
            } else {
                echo html_writer::empty_tag('input', [
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0',
                    'max' => $maxmark,
                    'id' => $inputid,
                    'name' => 'mark_' . $criterion['id'],
                    'value' => $currentmark,
                    'class' => 'form-control pgosce-score-input',
                    'data-max' => $maxmark,
                ]);
            }
            echo html_writer::div('/ ' . format_float($maxmark, 2), 'pgosce-max-label');
            echo html_writer::end_div();
            echo html_writer::end_div();
        }
        echo html_writer::end_div();
    }
    echo html_writer::end_tag('details');
    $sectionnumber++;
}

echo html_writer::start_div('pgosce-panel');
echo html_writer::tag('label', get_string('generalcomment', 'pgosce'), ['for' => 'id_generalcomment']);
echo html_writer::tag('textarea', s($attempt->generalcomment), [
    'id' => 'id_generalcomment',
    'name' => 'generalcomment',
    'class' => 'form-control',
    'rows' => 4,
]);
echo html_writer::end_div();
echo html_writer::start_div('pgosce-actions');
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-secondary mr-1', 'value' => get_string('save', 'pgosce')]);
echo html_writer::empty_tag('input', ['type' => 'submit', 'name' => 'finalize', 'class' => 'btn btn-primary', 'value' => get_string('savefinal', 'pgosce')]);
echo ' ';
echo html_writer::link(new moodle_url('/mod/pgosce/view.php', ['id' => $cm->id]), get_string('backtoactivity', 'pgosce'), ['class' => 'btn btn-link']);
echo html_writer::end_div();
echo html_writer::end_tag('form');
echo html_writer::end_div();

echo $OUTPUT->footer();
