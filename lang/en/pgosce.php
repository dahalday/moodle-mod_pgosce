<?php
// This file is part of Moodle - http://moodle.org/

/**
 * English strings for PG OSCE.
 *
 * @package    mod_pgosce
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'PG OSCE';
$string['modulename'] = 'PG OSCE';
$string['modulename_help'] = 'Use PG OSCE to mark structured oral or clinical examination stations with editable sections, questions, criteria, comments and gradebook synchronization.';
$string['modulenameplural'] = 'PG OSCEs';
$string['pluginadministration'] = 'PG OSCE administration';
$string['pgosce:addinstance'] = 'Add a PG OSCE activity';
$string['pgosce:view'] = 'View PG OSCE';
$string['pgosce:manage'] = 'Manage PG OSCE rubric';
$string['pgosce:assess'] = 'Assess students in PG OSCE';
$string['pgosce:viewreports'] = 'View PG OSCE reports';
$string['pgosce:export'] = 'Export PG OSCE data';
$string['privacy:metadata:attempt'] = 'Stores OSCE assessment attempts for students.';
$string['privacy:metadata:score'] = 'Stores marks and comments entered against OSCE criteria.';
$string['defaultgrade'] = 'Maximum grade';
$string['displaystudentreports'] = 'Release student reports';
$string['displaystudentreports_help'] = 'When enabled, students can view their own released assessment report.';
$string['showstudentinstructions'] = 'Show section instructions to students';
$string['showstudentinstructions_help'] = 'When enabled, students can see rubric section names and section descriptions as candidate-facing station instructions. Questions, criteria, marks and assessor comments remain hidden from students.';
$string['rubric'] = 'Rubric';
$string['managerubric'] = 'Edit rubric';
$string['giftimportexport'] = 'PG OSCE GIFT import/export';
$string['giftimportexportintro'] = 'Paste PG OSCE GIFT text, or upload a .gift/.txt file, to replace the current station rubric. Export copies the current rubric into the same reusable text format.';
$string['gifttext'] = 'PG OSCE GIFT text';
$string['giftfile'] = 'PG OSCE GIFT file';
$string['importgift'] = 'Import PG OSCE GIFT';
$string['exportgift'] = 'Download PG OSCE GIFT';
$string['giftimported'] = 'PG OSCE GIFT rubric imported';
$string['invalidgift'] = 'The PG OSCE GIFT text could not be imported. Check that it has at least one section, question and marking criterion.';
$string['giftreplacewarning'] = 'Importing PG OSCE GIFT replaces the current rubric. Existing marks attached to removed criteria will be deleted.';
$string['rubricjson'] = 'Rubric JSON';
$string['rubricjson_help'] = 'Edit sections, questions and answer-key criteria. Marks are calculated from the criterion max marks.';
$string['rubricsaved'] = 'Rubric saved';
$string['invalidrubric'] = 'The rubric could not be saved. Check that each section, question and criterion has the required text.';
$string['rubricformhint'] = 'Create the station marking scheme below. Add the station or domain, then add questions, answer-key criteria and marks.';
$string['rubriclocked'] = 'This rubric is locked because assessment attempts already exist.';
$string['rubriceditafterattempts'] = 'This rubric already has assessment attempts. You can still edit it, but changed or removed criteria may require existing marks to be reviewed.';
$string['createrubricfirst'] = 'Create the station questions, answer-key criteria and marks before assessing students.';
$string['candidateinstructions'] = 'Candidate instructions';
$string['nostationinstructions'] = 'No station instructions are available yet.';
$string['studentviewsettings'] = 'Student view';
$string['section'] = 'Section';
$string['sectionname'] = 'Section name';
$string['sectiondescription'] = 'Section description';
$string['addsection'] = 'Add section';
$string['removesection'] = 'Remove section';
$string['question'] = 'Question';
$string['questiontitle'] = 'Question title';
$string['questionprompt'] = 'Question prompt';
$string['addquestion'] = 'Add question';
$string['removequestion'] = 'Remove question';
$string['addcriterion'] = 'Add criterion';
$string['remove'] = 'Remove';
$string['nostudents'] = 'No enrolled students were found.';
$string['student'] = 'Student';
$string['status'] = 'Status';
$string['score'] = 'Score';
$string['percentage'] = 'Percentage';
$string['grade'] = 'Grade';
$string['actions'] = 'Actions';
$string['assess'] = 'Assess';
$string['report'] = 'Report';
$string['export'] = 'Export';
$string['exportall'] = 'Export all marks';
$string['exportquestion'] = 'Export question data';
$string['exportoptionsintro'] = 'Choose an Excel-style CSV export. Average exports combine scores across markers; individual exports keep each marker on a separate row.';
$string['exportsummaryavg'] = 'Excel-style CSV: average score across markers';
$string['exportsummaryindividual'] = 'Excel-style CSV: individual marker scores';
$string['exportdetailavg'] = 'Detailed CSV: average criterion scores';
$string['exportdetailindividual'] = 'Detailed CSV: individual marker criterion scores';
$string['save'] = 'Save';
$string['savefinal'] = 'Save and finalize';
$string['saved'] = 'Assessment saved';
$string['finalized'] = 'Assessment finalized and gradebook updated';
$string['generalcomment'] = 'General comment';
$string['comment'] = 'Comment';
$string['mark'] = 'Mark';
$string['maxmark'] = 'Max mark';
$string['notassessed'] = 'Not assessed';
$string['inprogress'] = 'In progress';
$string['complete'] = 'Complete';
$string['criteria'] = 'Criteria';
$string['questions'] = 'Questions';
$string['totals'] = 'Totals';
$string['viewownreport'] = 'View my OSCE report';
$string['reportnotavailable'] = 'Your OSCE report is not available yet.';
$string['backtoactivity'] = 'Back to activity';
$string['bulktemplatehint'] = 'Teachers create the station questions, answer-key criteria and marks for each OSCE station.';
$string['resetattempts'] = 'Delete PG OSCE assessment attempts and grades';
$string['resetrubrics'] = 'Delete PG OSCE station rubrics/questions';
$string['resetrubrics_help'] = 'This removes the station sections, questions, rich-text content, uploaded rubric files and criteria. Use this only when you want a fully blank PG OSCE activity after course reset.';
