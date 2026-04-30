<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_pgosce\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy metadata provider for PG OSCE.
 *
 * @package    mod_pgosce
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider {
    /**
     * Describe stored personal data.
     *
     * @param \core_privacy\local\metadata\collection $collection
     * @return \core_privacy\local\metadata\collection
     */
    public static function get_metadata(\core_privacy\local\metadata\collection $collection) {
        $collection->add_database_table('pgosce_attempt', [
            'userid' => 'privacy:metadata:attempt',
            'assessorid' => 'privacy:metadata:attempt',
            'generalcomment' => 'privacy:metadata:attempt',
        ], 'privacy:metadata:attempt');
        $collection->add_database_table('pgosce_score', [
            'mark' => 'privacy:metadata:score',
            'comment' => 'privacy:metadata:score',
        ], 'privacy:metadata:score');
        return $collection;
    }
}
