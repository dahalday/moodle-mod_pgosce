<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Backup task for PG OSCE.
 *
 * @package    mod_pgosce
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/pgosce/backup/moodle2/backup_pgosce_stepslib.php');

/**
 * Defines the PG OSCE activity backup task.
 */
class backup_pgosce_activity_task extends backup_activity_task {
    /**
     * No custom backup settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Add the structure step.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_pgosce_activity_structure_step('pgosce_structure', 'pgosce.xml'));
    }

    /**
     * Encode links to this activity.
     *
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        $search = '/(' . $base . '\/mod\/pgosce\/index.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@PGOSCEINDEX*$2@$', $content);

        $search = '/(' . $base . '\/mod\/pgosce\/view.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@PGOSCEVIEWBYID*$2@$', $content);

        $search = '/(' . $base . '\/mod\/pgosce\/manage.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@PGOSCEVIEWBYID*$2@$', $content);

        $search = '/(' . $base . '\/mod\/pgosce\/export.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@PGOSCEVIEWBYID*$2@$', $content);

        $search = '/(' . $base . '\/mod\/pgosce\/report.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@PGOSCEVIEWBYID*$2@$', $content);

        return $content;
    }
}
