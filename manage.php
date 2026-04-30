<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Rubric editor for PG OSCE.
 *
 * @package    mod_pgosce
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/pgosce/lib.php');

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'pgosce');
$pgosce = $DB->get_record('pgosce', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pgosce:manage', $context);

$PAGE->set_url('/mod/pgosce/manage.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(get_string('managerubric', 'pgosce'));
$PAGE->set_heading($course->fullname);
$PAGE->requires->css(new moodle_url('/mod/pgosce/styles.css'));

/**
 * Render a text input.
 *
 * @param string $name
 * @param string $value
 * @param string $label
 * @param string $datafield
 * @return string
 */
function pgosce_render_text_input($name, $value, $label, $datafield = '') {
    $id = html_writer::random_id('pgosce_');
    $attrs = [
        'type' => 'text',
        'id' => $id,
        'name' => $name,
        'value' => $value,
        'class' => 'form-control',
    ];
    if ($datafield !== '') {
        $attrs['data-field'] = $datafield;
    }
    return html_writer::div(
        html_writer::tag('label', $label, ['for' => $id]) .
        html_writer::empty_tag('input', $attrs),
        'form-group'
    );
}

/**
 * Render a hidden input.
 *
 * @param string $name
 * @param mixed $value
 * @param string $datafield
 * @return string
 */
function pgosce_render_hidden_input($name, $value, $datafield = '') {
    $attrs = [
        'type' => 'hidden',
        'name' => $name,
        'value' => $value,
    ];
    if ($datafield !== '') {
        $attrs['data-field'] = $datafield;
    }
    return html_writer::empty_tag('input', $attrs);
}

/**
 * Render a textarea.
 *
 * @param string $name
 * @param string $value
 * @param string $label
 * @param int $rows
 * @param string $datafield
 * @param bool $richeditor
 * @param string $draftname
 * @param int $draftitemid
 * @param string $draftdatafield
 * @return string
 */
function pgosce_render_textarea($name, $value, $label, $rows = 2, $datafield = '', $richeditor = false,
        $draftname = '', $draftitemid = 0, $draftdatafield = '') {
    global $context;

    $id = html_writer::random_id('pgosce_');
    $attrs = [
        'id' => $id,
        'name' => $name,
        'class' => 'form-control',
        'rows' => $rows,
    ];
    if ($datafield !== '') {
        $attrs['data-field'] = $datafield;
    }
    if ($richeditor) {
        $editor = editors_get_preferred_editor(FORMAT_HTML);
        $editor->use_editor($id, pgosce_editor_options($context));
    }
    $hidden = '';
    if ($draftname !== '') {
        $hidden = pgosce_render_hidden_input($draftname, $draftitemid, $draftdatafield);
    }
    return html_writer::div(
        html_writer::tag('label', $label, ['for' => $id]) .
        html_writer::tag('textarea', s($value), $attrs) .
        $hidden,
        'form-group'
    );
}

/**
 * Render a number input.
 *
 * @param string $name
 * @param float $value
 * @param string $label
 * @param string $datafield
 * @return string
 */
function pgosce_render_number_input($name, $value, $label, $datafield = '') {
    $id = html_writer::random_id('pgosce_');
    $attrs = [
        'type' => 'number',
        'step' => '0.01',
        'min' => '0',
        'id' => $id,
        'name' => $name,
        'value' => $value,
        'class' => 'form-control',
    ];
    if ($datafield !== '') {
        $attrs['data-field'] = $datafield;
    }
    return html_writer::div(
        html_writer::tag('label', $label, ['for' => $id]) .
        html_writer::empty_tag('input', $attrs),
        'form-group'
    );
}

/**
 * Render one criterion editor.
 *
 * @param array $criterion
 * @param int $sectionindex
 * @param int $questionindex
 * @param int $criterionindex
 * @return string
 */
function pgosce_render_criterion_editor(array $criterion, $sectionindex, $questionindex, $criterionindex) {
    global $context;

    $prefix = 'rubric[' . $sectionindex . '][questions][' . $questionindex . '][criteria][' . $criterionindex . ']';
    $criterionid = !empty($criterion['id']) ? (int)$criterion['id'] : 0;
    $description = isset($criterion['description']) ? $criterion['description'] : '';
    $maxmark = isset($criterion['maxmark']) ? $criterion['maxmark'] : 1;
    $descriptiondraftid = 0;
    $description = pgosce_prepare_editor_content(
        $context,
        PGOSCE_FILEAREA_CRITERION,
        $criterionid,
        $description,
        $descriptiondraftid
    );

    $output = html_writer::start_div('pgosce-criterion border rounded p-2 mb-2');
    $output .= pgosce_render_hidden_input($prefix . '[id]', $criterionid, 'criterion-id');
    $output .= html_writer::start_div('row');
    $output .= html_writer::div(
        pgosce_render_textarea($prefix . '[description]', $description, get_string('criteria', 'pgosce'), 2,
            'criterion-description', true, $prefix . '[descriptionitemid]', $descriptiondraftid,
            'criterion-descriptionitemid'),
        'col-md-9'
    );
    $output .= html_writer::div(
        pgosce_render_number_input($prefix . '[maxmark]', $maxmark, get_string('maxmark', 'pgosce'), 'criterion-maxmark'),
        'col-md-2'
    );
    $output .= html_writer::div(
        html_writer::tag('label', '&nbsp;') .
        html_writer::tag('button', '<span aria-hidden="true">&#128465;</span>', [
            'type' => 'button',
            'class' => 'btn btn-outline-danger pgosce-remove pgosce-icon-button',
            'title' => get_string('remove', 'pgosce'),
            'aria-label' => get_string('remove', 'pgosce'),
        ]),
        'col-md-1 pgosce-criterion-remove'
    );
    $output .= html_writer::end_div();
    $output .= html_writer::end_div();

    return $output;
}

/**
 * Render one question editor.
 *
 * @param array $question
 * @param int $sectionindex
 * @param int $questionindex
 * @return string
 */
function pgosce_render_question_editor(array $question, $sectionindex, $questionindex) {
    global $context;

    $prefix = 'rubric[' . $sectionindex . '][questions][' . $questionindex . ']';
    $questionid = !empty($question['id']) ? (int)$question['id'] : 0;
    $title = isset($question['title']) ? $question['title'] : '';
    $prompt = isset($question['prompt']) ? $question['prompt'] : '';
    $criteria = !empty($question['criteria']) && is_array($question['criteria']) ? $question['criteria'] : [[]];
    $promptdraftid = 0;
    $prompt = pgosce_prepare_editor_content(
        $context,
        PGOSCE_FILEAREA_QUESTION,
        $questionid,
        $prompt,
        $promptdraftid
    );

    $output = html_writer::start_div('pgosce-question card mb-3');
    $output .= html_writer::start_div('card-body');
    $output .= pgosce_render_hidden_input($prefix . '[id]', $questionid, 'question-id');
    $output .= html_writer::start_div('d-flex justify-content-between align-items-center mb-2');
    $output .= html_writer::tag('h5', get_string('question', 'pgosce'), ['class' => 'mb-0']);
    $output .= html_writer::tag('button', '<span aria-hidden="true">&#128465;</span>', [
        'type' => 'button',
        'class' => 'btn btn-outline-danger pgosce-remove pgosce-icon-button',
        'title' => get_string('removequestion', 'pgosce'),
        'aria-label' => get_string('removequestion', 'pgosce'),
    ]);
    $output .= html_writer::end_div();
    $output .= html_writer::start_div('row');
    $output .= html_writer::div(pgosce_render_text_input($prefix . '[title]', $title,
        get_string('questiontitle', 'pgosce'), 'question-title'), 'col-md-4');
    $output .= html_writer::div(pgosce_render_textarea($prefix . '[prompt]', $prompt,
        get_string('questionprompt', 'pgosce'), 2, 'question-prompt', true, $prefix . '[promptitemid]', $promptdraftid,
        'question-promptitemid'),
        'col-md-8');
    $output .= html_writer::end_div();
    $output .= html_writer::tag('h6', get_string('criteria', 'pgosce'));
    $output .= html_writer::start_div('pgosce-criteria');

    $criterionindex = 0;
    foreach ($criteria as $criterion) {
        $output .= pgosce_render_criterion_editor($criterion, $sectionindex, $questionindex, $criterionindex++);
    }

    $output .= html_writer::end_div();
    $output .= html_writer::empty_tag('input', [
        'type' => 'button',
        'class' => 'btn btn-sm btn-secondary pgosce-add-criterion',
        'value' => get_string('addcriterion', 'pgosce'),
    ]);
    $output .= html_writer::end_div();
    $output .= html_writer::end_div();

    return $output;
}

/**
 * Render one section editor.
 *
 * @param array $section
 * @param int $sectionindex
 * @return string
 */
function pgosce_render_section_editor(array $section, $sectionindex) {
    global $context;

    $prefix = 'rubric[' . $sectionindex . ']';
    $sectionid = !empty($section['id']) ? (int)$section['id'] : 0;
    $name = isset($section['name']) ? $section['name'] : '';
    $description = isset($section['description']) ? $section['description'] : '';
    $questions = !empty($section['questions']) && is_array($section['questions']) ? $section['questions'] : [[]];
    $descriptiondraftid = 0;
    $description = pgosce_prepare_editor_content(
        $context,
        PGOSCE_FILEAREA_SECTION,
        $sectionid,
        $description,
        $descriptiondraftid
    );

    $output = html_writer::start_div('pgosce-section card mb-4');
    $output .= html_writer::start_div('card-body');
    $output .= pgosce_render_hidden_input($prefix . '[id]', $sectionid, 'section-id');
    $output .= html_writer::start_div('d-flex justify-content-between align-items-center mb-3');
    $output .= html_writer::tag('h4', get_string('section', 'pgosce'), ['class' => 'mb-0']);
    $output .= html_writer::tag('button', '<span aria-hidden="true">&#128465;</span>', [
        'type' => 'button',
        'class' => 'btn btn-outline-danger pgosce-remove pgosce-icon-button',
        'title' => get_string('removesection', 'pgosce'),
        'aria-label' => get_string('removesection', 'pgosce'),
    ]);
    $output .= html_writer::end_div();
    $output .= html_writer::start_div('row');
    $output .= html_writer::div(pgosce_render_text_input($prefix . '[name]', $name,
        get_string('sectionname', 'pgosce'), 'section-name'), 'col-md-4');
    $output .= html_writer::div(pgosce_render_textarea($prefix . '[description]', $description,
        get_string('sectiondescription', 'pgosce'), 2, 'section-description', true,
        $prefix . '[descriptionitemid]', $descriptiondraftid, 'section-descriptionitemid'), 'col-md-8');
    $output .= html_writer::end_div();
    $output .= html_writer::start_div('pgosce-questions');

    $questionindex = 0;
    foreach ($questions as $question) {
        $output .= pgosce_render_question_editor($question, $sectionindex, $questionindex++);
    }

    $output .= html_writer::end_div();
    $output .= html_writer::empty_tag('input', [
        'type' => 'button',
        'class' => 'btn btn-secondary pgosce-add-question',
        'value' => get_string('addquestion', 'pgosce'),
    ]);
    $output .= html_writer::end_div();
    $output .= html_writer::end_div();

    return $output;
}

if (data_submitted() && confirm_sesskey()) {
    $rubric = [];
    if (isset($_POST['rubric']) && is_array($_POST['rubric'])) {
        $rubric = clean_param_array($_POST['rubric'], PARAM_RAW, true);
    }
    $showstudentinstructions = optional_param('showstudentinstructions', 0, PARAM_BOOL);
    if (!empty($rubric)) {
        $DB->update_record('pgosce', (object)[
            'id' => $pgosce->id,
            'showstudentinstructions' => $showstudentinstructions,
            'timemodified' => time(),
        ]);
        $pgosce->showstudentinstructions = $showstudentinstructions;
        pgosce_save_rubric_array($pgosce->id, $rubric, $context);
        redirect($PAGE->url, get_string('rubricsaved', 'pgosce'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    $json = optional_param('rubricjson', '', PARAM_RAW);
    if ($json !== '' && pgosce_save_rubric_json($pgosce->id, $json)) {
        $DB->update_record('pgosce', (object)[
            'id' => $pgosce->id,
            'showstudentinstructions' => $showstudentinstructions,
            'timemodified' => time(),
        ]);
        $pgosce->showstudentinstructions = $showstudentinstructions;
        redirect($PAGE->url, get_string('rubricsaved', 'pgosce'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    redirect($PAGE->url, get_string('invalidrubric', 'pgosce'), null, \core\output\notification::NOTIFY_ERROR);
}

$rubric = pgosce_get_rubric($pgosce->id);
if (!$rubric) {
    $rubric = pgosce_default_rubric();
}

$PAGE->requires->js_init_code("
(function() {
    function closest(el, selector) {
        while (el && el.nodeType === 1) {
            if (el.matches && el.matches(selector)) {
                return el;
            }
            el = el.parentNode;
        }
        return null;
    }
    function renameInputs(root) {
        var sections = root.querySelectorAll('.pgosce-section');
        for (var s = 0; s < sections.length; s++) {
            var section = sections[s];
            if (section.querySelector('[data-field=\"section-id\"]')) {
                section.querySelector('[data-field=\"section-id\"]').name = 'rubric[' + s + '][id]';
            }
            section.querySelector('[data-field=\"section-name\"]').name = 'rubric[' + s + '][name]';
            section.querySelector('[data-field=\"section-description\"]').name = 'rubric[' + s + '][description]';
            if (section.querySelector('[data-field=\"section-descriptionitemid\"]')) {
                section.querySelector('[data-field=\"section-descriptionitemid\"]').name = 'rubric[' + s + '][descriptionitemid]';
            }
            var questions = section.querySelectorAll('.pgosce-question');
            for (var q = 0; q < questions.length; q++) {
                var question = questions[q];
                if (question.querySelector('[data-field=\"question-id\"]')) {
                    question.querySelector('[data-field=\"question-id\"]').name = 'rubric[' + s + '][questions][' + q + '][id]';
                }
                question.querySelector('[data-field=\"question-title\"]').name = 'rubric[' + s + '][questions][' + q + '][title]';
                question.querySelector('[data-field=\"question-prompt\"]').name = 'rubric[' + s + '][questions][' + q + '][prompt]';
                if (question.querySelector('[data-field=\"question-promptitemid\"]')) {
                    question.querySelector('[data-field=\"question-promptitemid\"]').name = 'rubric[' + s + '][questions][' + q + '][promptitemid]';
                }
                var criteria = question.querySelectorAll('.pgosce-criterion');
                for (var c = 0; c < criteria.length; c++) {
                    if (criteria[c].querySelector('[data-field=\"criterion-id\"]')) {
                        criteria[c].querySelector('[data-field=\"criterion-id\"]').name = 'rubric[' + s + '][questions][' + q + '][criteria][' + c + '][id]';
                    }
                    criteria[c].querySelector('[data-field=\"criterion-description\"]').name = 'rubric[' + s + '][questions][' + q + '][criteria][' + c + '][description]';
                    criteria[c].querySelector('[data-field=\"criterion-maxmark\"]').name = 'rubric[' + s + '][questions][' + q + '][criteria][' + c + '][maxmark]';
                    if (criteria[c].querySelector('[data-field=\"criterion-descriptionitemid\"]')) {
                        criteria[c].querySelector('[data-field=\"criterion-descriptionitemid\"]').name = 'rubric[' + s + '][questions][' + q + '][criteria][' + c + '][descriptionitemid]';
                    }
                }
            }
        }
    }
    function criterionHtml() {
        return '<div class=\"pgosce-criterion border rounded p-2 mb-2\">' +
            '<div class=\"row\">' +
            '<div class=\"col-md-9\"><div class=\"form-group\"><label>" . addslashes_js(get_string('criteria', 'pgosce')) . "</label><textarea data-field=\"criterion-description\" class=\"form-control\" rows=\"2\"></textarea></div></div>' +
            '<div class=\"col-md-2\"><div class=\"form-group\"><label>" . addslashes_js(get_string('maxmark', 'pgosce')) . "</label><input data-field=\"criterion-maxmark\" type=\"number\" step=\"0.01\" min=\"0\" value=\"1\" class=\"form-control\"></div></div>' +
            '<div class=\"col-md-1 pgosce-criterion-remove\"><label>&nbsp;</label><button type=\"button\" class=\"btn btn-outline-danger pgosce-remove pgosce-icon-button\" title=\"" . addslashes_js(get_string('remove', 'pgosce')) . "\" aria-label=\"" . addslashes_js(get_string('remove', 'pgosce')) . "\"><span aria-hidden=\"true\">&#128465;</span></button></div>' +
            '</div></div>';
    }
    function questionHtml() {
        return '<div class=\"pgosce-question card mb-3\"><div class=\"card-body\">' +
            '<div class=\"d-flex justify-content-between align-items-center mb-2\"><h5 class=\"mb-0\">" . addslashes_js(get_string('question', 'pgosce')) . "</h5><button type=\"button\" class=\"btn btn-outline-danger pgosce-remove pgosce-icon-button\" title=\"" . addslashes_js(get_string('removequestion', 'pgosce')) . "\" aria-label=\"" . addslashes_js(get_string('removequestion', 'pgosce')) . "\"><span aria-hidden=\"true\">&#128465;</span></button></div>' +
            '<div class=\"row\"><div class=\"col-md-4\"><div class=\"form-group\"><label>" . addslashes_js(get_string('questiontitle', 'pgosce')) . "</label><input data-field=\"question-title\" type=\"text\" class=\"form-control\"></div></div>' +
            '<div class=\"col-md-8\"><div class=\"form-group\"><label>" . addslashes_js(get_string('questionprompt', 'pgosce')) . "</label><textarea data-field=\"question-prompt\" class=\"form-control\" rows=\"2\"></textarea></div></div></div>' +
            '<h6>" . addslashes_js(get_string('criteria', 'pgosce')) . "</h6><div class=\"pgosce-criteria\">' + criterionHtml() + '</div>' +
            '<input type=\"button\" class=\"btn btn-sm btn-secondary pgosce-add-criterion\" value=\"" . addslashes_js(get_string('addcriterion', 'pgosce')) . "\">' +
            '</div></div>';
    }
    function sectionHtml() {
        return '<div class=\"pgosce-section card mb-4\"><div class=\"card-body\">' +
            '<div class=\"d-flex justify-content-between align-items-center mb-3\"><h4 class=\"mb-0\">" . addslashes_js(get_string('section', 'pgosce')) . "</h4><button type=\"button\" class=\"btn btn-outline-danger pgosce-remove pgosce-icon-button\" title=\"" . addslashes_js(get_string('removesection', 'pgosce')) . "\" aria-label=\"" . addslashes_js(get_string('removesection', 'pgosce')) . "\"><span aria-hidden=\"true\">&#128465;</span></button></div>' +
            '<div class=\"row\"><div class=\"col-md-4\"><div class=\"form-group\"><label>" . addslashes_js(get_string('sectionname', 'pgosce')) . "</label><input data-field=\"section-name\" type=\"text\" class=\"form-control\"></div></div>' +
            '<div class=\"col-md-8\"><div class=\"form-group\"><label>" . addslashes_js(get_string('sectiondescription', 'pgosce')) . "</label><textarea data-field=\"section-description\" class=\"form-control\" rows=\"2\"></textarea></div></div></div>' +
            '<div class=\"pgosce-questions\">' + questionHtml() + '</div>' +
            '<input type=\"button\" class=\"btn btn-secondary pgosce-add-question\" value=\"" . addslashes_js(get_string('addquestion', 'pgosce')) . "\">' +
            '</div></div>';
    }
    var editor = document.getElementById('pgosce-rubric-editor');
    if (!editor) {
        return;
    }
    var form = document.getElementById('pgosce-rubric-form');
    editor.addEventListener('click', function(e) {
        var target = e.target;
        if (target.className.indexOf('pgosce-add-section') !== -1) {
            var wrapper = document.createElement('div');
            wrapper.innerHTML = sectionHtml();
            editor.insertBefore(wrapper.firstChild, target);
            renameInputs(editor);
        } else if (target.className.indexOf('pgosce-add-question') !== -1) {
            var section = closest(target, '.pgosce-section');
            var questions = section.querySelector('.pgosce-questions');
            var wrapper = document.createElement('div');
            wrapper.innerHTML = questionHtml();
            questions.appendChild(wrapper.firstChild);
            renameInputs(editor);
        } else if (target.className.indexOf('pgosce-add-criterion') !== -1) {
            var question = closest(target, '.pgosce-question');
            var criteria = question.querySelector('.pgosce-criteria');
            var wrapper = document.createElement('div');
            wrapper.innerHTML = criterionHtml();
            criteria.appendChild(wrapper.firstChild);
            renameInputs(editor);
        } else if (closest(target, '.pgosce-remove')) {
            var removebutton = closest(target, '.pgosce-remove');
            var item = closest(removebutton, '.pgosce-criterion') || closest(removebutton, '.pgosce-question') || closest(removebutton, '.pgosce-section');
            if (item) {
                item.parentNode.removeChild(item);
                renameInputs(editor);
            }
        }
    });
    if (form) {
        form.addEventListener('submit', function() {
            renameInputs(editor);
        });
    }
    renameInputs(editor);
})();");

echo $OUTPUT->header();
echo html_writer::start_div('pgosce-shell');
echo html_writer::start_div('pgosce-hero');
echo html_writer::tag('h2', get_string('managerubric', 'pgosce'));
echo html_writer::tag('p', get_string('rubricformhint', 'pgosce'));
echo html_writer::end_div();

if (pgosce_has_attempts($pgosce->id)) {
    echo $OUTPUT->notification(get_string('rubriceditafterattempts', 'pgosce'), 'warning');
}

echo html_writer::start_tag('form', ['method' => 'post', 'id' => 'pgosce-rubric-form']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::start_div('card mb-3');
echo html_writer::start_div('card-body');
echo html_writer::tag('h4', get_string('studentviewsettings', 'pgosce'));
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'showstudentinstructions', 'value' => 0]);
echo html_writer::checkbox(
    'showstudentinstructions',
    1,
    !empty($pgosce->showstudentinstructions),
    get_string('showstudentinstructions', 'pgosce'),
    ['id' => 'id_showstudentinstructions']
);
echo html_writer::div(get_string('showstudentinstructions_help', 'pgosce'), 'form-text text-muted');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('', ['id' => 'pgosce-rubric-editor']);

$sectionindex = 0;
foreach ($rubric as $section) {
    echo pgosce_render_section_editor($section, $sectionindex++);
}

echo html_writer::empty_tag('input', [
    'type' => 'button',
    'class' => 'btn btn-secondary mb-3 pgosce-add-section',
    'value' => get_string('addsection', 'pgosce'),
]);
echo html_writer::end_div();

echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'class' => 'btn btn-primary',
    'value' => get_string('save', 'pgosce'),
]);
echo ' ';
echo html_writer::link(new moodle_url('/mod/pgosce/view.php', ['id' => $cm->id]), get_string('backtoactivity', 'pgosce'), ['class' => 'btn btn-secondary']);
echo html_writer::end_tag('form');
echo html_writer::end_div();

echo $OUTPUT->footer();
