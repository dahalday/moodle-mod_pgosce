<?php
// This file is part of Moodle - http://moodle.org/

/**
 * PG OSCE GIFT import/export page.
 *
 * @package    mod_pgosce
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/pgosce/lib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'pgosce');
$pgosce = $DB->get_record('pgosce', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pgosce:import', $context);

$PAGE->set_url('/mod/pgosce/gift.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(get_string('giftimportexport', 'pgosce'));
$PAGE->set_heading($course->fullname);
$PAGE->requires->css(new moodle_url('/mod/pgosce/styles.css'));

if ($action === 'export' && confirm_sesskey()) {
    $filename = clean_filename($pgosce->name . '-pgosce.gift');
    @header('Content-Type: text/plain; charset=utf-8');
    @header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo pgosce_export_gift($pgosce);
    die;
}

if (data_submitted() && confirm_sesskey() && $action === 'import') {
    $gifttext = optional_param('gifttext', '', PARAM_RAW);
    if (!empty($_FILES['giftfile']['tmp_name']) && is_uploaded_file($_FILES['giftfile']['tmp_name'])) {
        $gifttext = (string)file_get_contents($_FILES['giftfile']['tmp_name']);
    }

    $rubric = pgosce_parse_gift($gifttext);
    if ($rubric && pgosce_get_total_maxmark_from_rubric($rubric) > 0) {
        pgosce_save_rubric_array($pgosce->id, $rubric, $context);
        $updates = (object)[
            'id' => $pgosce->id,
            'timemodified' => time(),
        ];
        foreach (pgosce_parse_gift_settings($gifttext) as $name => $value) {
            $updates->{$name} = $value;
        }
        $DB->update_record('pgosce', $updates);
        redirect($PAGE->url, get_string('giftimported', 'pgosce'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    redirect($PAGE->url, get_string('invalidgift', 'pgosce'), null, \core\output\notification::NOTIFY_ERROR);
}

echo $OUTPUT->header();
echo html_writer::start_div('pgosce-shell');
echo html_writer::start_div('pgosce-hero');
echo html_writer::tag('h2', get_string('giftimportexport', 'pgosce'));
echo html_writer::tag('p', get_string('giftimportexportintro', 'pgosce'));
echo html_writer::end_div();

if (pgosce_has_attempts($pgosce->id)) {
    echo $OUTPUT->notification(get_string('giftreplacewarning', 'pgosce'), 'warning');
}

echo html_writer::start_tag('form', [
    'method' => 'post',
    'enctype' => 'multipart/form-data',
    'action' => new moodle_url('/mod/pgosce/gift.php', ['id' => $cm->id, 'action' => 'import']),
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::div(
    html_writer::tag('label', get_string('giftfile', 'pgosce'), ['for' => 'id_giftfile']) .
    html_writer::empty_tag('input', [
        'type' => 'file',
        'name' => 'giftfile',
        'id' => 'id_giftfile',
        'accept' => '.gift,.txt,text/plain',
        'class' => 'form-control',
    ]),
    'form-group'
);
echo html_writer::div(
    html_writer::tag('label', get_string('gifttext', 'pgosce'), ['for' => 'id_gifttext']) .
    html_writer::tag('textarea', s(pgosce_export_gift($pgosce)), [
        'name' => 'gifttext',
        'id' => 'id_gifttext',
        'rows' => 24,
        'class' => 'form-control',
    ]),
    'form-group'
);
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'class' => 'btn btn-primary',
    'value' => get_string('importgift', 'pgosce'),
]);
echo ' ';
echo html_writer::link(
    new moodle_url('/mod/pgosce/gift.php', ['id' => $cm->id, 'action' => 'export', 'sesskey' => sesskey()]),
    get_string('exportgift', 'pgosce'),
    ['class' => 'btn btn-secondary']
);
echo ' ';
echo html_writer::link(new moodle_url('/mod/pgosce/manage.php', ['id' => $cm->id]), get_string('managerubric', 'pgosce'),
    ['class' => 'btn btn-link']);
echo html_writer::end_tag('form');
echo html_writer::end_div();

echo $OUTPUT->footer();
