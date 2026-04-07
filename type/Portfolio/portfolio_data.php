<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_certificate\type\Portfolio;

use core_customfield\field;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../Portfolio/course_section.php');

/**
 * Portfolio data fetching class.
 *
 * @package    mod_certificate
 * @copyright  2022 Nicholas Lambell
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class portfolio_data {

    /** @var string CPD value custom field short name */
    protected const FIELD_CPD = 'chp_cpd';

    /** @var string CPD show custom field short name */
    protected const FIELD_SHOW_CPD = 'showport_cpd';

    /** @var string History show custom field short name */
    protected const FIELD_SHOW_HISTORY = 'showport_history';

    /** @var string Word to indicate a required section in the custom field description */
    protected const REQUIRED_WORD = 'required';

    /**
     * @var array<string, field> Cache of custom field instances.
     */
    protected static array $fieldcache = [];

    /**
     * Gets the formatted course completion data for a given user.
     *
     * @param int $userid ID of the user to retrieve completion data for.
     * @return course_section[] List of {@link course_section} data.
     */
    public static function get_course_section_data(int $userid, bool $debug = false): array {
        $headerfields = self::get_header_custom_fields();

        $coursedata = [];
        foreach ($headerfields as $headerfield) {
            $description = $headerfield->description ?? '';
            $required = false;

            if ($description) {
                $description = self::cleanse_field_description($description);
                $required = self::is_field_required($description);

                if ($required) {
                    $description = self::strip_required_word($description);
                }
            }

            $coursedata[] = new course_section(
                $headerfield->get('name'),
                $description,
                self::get_courses($headerfield->get('id'), $userid),
                $required
            );
        }

        if (
            $debug ||
            isset($_GET['debug'])
        ) {
            $coursedata = self::populate_debug_data($coursedata);
        }

        return $coursedata;
    }

    /**
     * Get the courses identified by the given custom field id that have been completed by the given user.
     *
     * @param int $customfieldid Custom field ID that courses must have a value for.
     * @param int $userid ID of the user to retrieve completion data for.
     * @return stdClass[] List of courses completed by the user within the custom field filtering.
     */
    protected static function get_courses(int $customfieldid, int $userid): array {
        global $DB;

        [
            $completionselects,
            $completionjoins,
            $completiongroups,
            $completionparams
        ] = self::get_completion_sql($userid);
        [
            $cpdselects,
            $cpdjoins,
            $cpdparams
        ] = self::get_cpd_sql();

        $sql = "
            SELECT  cc.ident,
                    c.id,
                    c.fullname
                    $completionselects
                    $cpdselects
            FROM    {course} c
                    JOIN {customfield_data} cd ON
                        cd.instanceid = c.id AND
                        cd.intvalue = 1 AND
                        cd.fieldid = :fieldtype
                    $completionjoins
                    $cpdjoins

            $completiongroups
            ORDER BY c.fullname, cc.timecompleted DESC
        ";

        $params = array_merge(
            [ 'fieldtype' => $customfieldid ],
            $completionparams,
            $cpdparams
        );

        return $DB->get_records_sql(
            $sql,
            $params,
        );
    }

    /**
     * Get the SQL components to retrieve course completion data for a given user.
     *
     * @param int $userid Target user ID.
     * @return array{ selects: string, joins: string, groups: string, params: array } SQL components.
     */
    protected static function get_completion_sql(int $userid): array {
        $selects = ", MAX(cc.timecompleted) AS 'timecompleted'";
        $joins = "
            JOIN (
                    SELECT  *,
                            CONCAT('completion-', id) AS 'ident'
                    FROM    {course_completions}
                    WHERE   userid = :usercompletion
                UNION
                    SELECT  *,
                            CONCAT('recompletion-', id) AS 'ident'
                    FROM    {local_recompletion_cc}
                    WHERE   userid = :userrecompletion
            ) cc ON
                cc.course = c.id AND
                cc.timecompleted IS NOT NULL
        ";
        $groups = 'GROUP BY c.id';
        $params = [
            'usercompletion' => $userid,
            'userrecompletion' => $userid,
        ];

        $showhistoryfield = static::get_custom_field(self::FIELD_SHOW_HISTORY);
        if (!$showhistoryfield) {
            return [ $selects, $joins, $groups, $params ];
        }

        $historycondition = "historyshowdata.intvalue = 1";
        $fieldconfig = $showhistoryfield->get('configdata');
        if ($fieldconfig->checkbydefault) {
            $historycondition = "(
                $historycondition OR
                historyshowdata.intvalue IS NULL
            )";
        }

        $selects = "
            , IF (
                $historycondition,
                cc.timecompleted,
                MAX(cc.timecompleted)
            ) AS 'timecompleted'
        ";
        $joins .= "
            LEFT JOIN {customfield_data} historyshowdata ON
                historyshowdata.instanceid = c.id AND
                historyshowdata.fieldid = :fieldshowhistory
        ";
        $params['fieldshowhistory'] = $showhistoryfield->get('id');
        $groups = "
            GROUP BY IF (
                $historycondition,
                cc.ident,
                c.id
            )
        ";

        return [ $selects, $joins, $groups, $params ];
    }

    /**
     * Get the SQL components to retrieve CPD data for courses.
     *
     * @return array{ selects: string, joins: string, params: array } SQL components.
     */
    protected static function get_cpd_sql(): array {
        $showcpdfield = static::get_custom_field(self::FIELD_SHOW_CPD);
        if (!$showcpdfield) {
            return [ '', '', [] ];
        }

        $cpdfield = static::get_custom_field(self::FIELD_CPD);
        if (!$cpdfield) {
            return [ '', '', [] ];
        }

        $cpdcondition = "cpdshowdata.intvalue = 1";
        $fieldconfig = $showcpdfield->get('configdata');
        if ($fieldconfig->checkbydefault) {
            $cpdcondition = "(
                $cpdcondition OR
                cpdshowdata.intvalue IS NULL
            )";
        }

        $joins = "
            LEFT JOIN {customfield_data} cpdshowdata ON
                cpdshowdata.instanceid = c.id AND
                cpdshowdata.fieldid = :fieldshowcpd AND
                cpdshowdata.intvalue = 1
            LEFT JOIN {customfield_data} cpddata ON
                cpddata.instanceid = c.id AND
                cpddata.fieldid = :fieldcpd AND
                $cpdcondition
        ";
        $params = [
            'fieldshowcpd' => $showcpdfield->get('id'),
            'fieldcpd' => $cpdfield->get('id'),
        ];

        return [
            ', cpddata.value AS cpd',
            $joins,
            $params,
        ];
    }

    /**
     * Get the list of custom fields identified by the `port_` prefix as portfolio headers.
     *
     * @return field[] A list of custom field instances.
     */
    protected static function get_header_custom_fields(): array {
        global $DB;

        return field::get_records_select(
            $DB->sql_like('shortname', ':shortname'),
            [ 'shortname' => 'port\_%' ],
            'sortorder'
        );
    }

    /**
     * Get a custom field instance by short name. Results are cached for performance.
     *
     * @param string $shortname Custom field short name.
     * @return field|null Custom field instance if found, null otherwise.
     */
    protected static function get_custom_field(string $shortname): ?field {
        if (isset(static::$fieldcache[$shortname])) {
            return static::$fieldcache[$shortname];
        }

        $field = field::get_record([ 'shortname' => $shortname ]);
        if (!$field) {
            return null;
        }

        static::$fieldcache[$shortname] = $field;

        return $field;
    }

    /**
     * Searches for the {@link REQUIRED_WORD} at the beginning of a string.
     *
     * @param string $description String to be searched.
     * @return bool Whether the {@link REQUIRED_WORD} was found.
     */
    protected static function is_field_required(string $description): bool {
        return str_starts_with($description, self::REQUIRED_WORD);
    }

    /**
     * Removes the {@link REQUIRED_WORD} from the beginning of a string.
     *
     * @param string $description String to operate on.
     * @return string Input string less the {@link REQUIRED_WORD}.
     */
    protected static function strip_required_word(string $description): string {
        return substr($description, strlen(self::REQUIRED_WORD));
    }

    /**
     * Cleanse a description string of invalid content such as HTML tags.
     *
     * @param string $description String to operate on.
     * @return string Input string less invalid content.
     */
    protected static function cleanse_field_description(string $description): string {
        return strip_tags($description);
    }

    /**
     * Generate debug data based on existing base course data.
     *
     * @param course_section[] $basecoursedata List of base course data.
     * @return course_section[] List of course data with debug courses added.
     */
    protected static function populate_debug_data(array $basecoursedata): array {
        $coursedata = [];

        foreach ($basecoursedata as $index => $basecoursesection) {
            $coursesection = clone $basecoursesection;

            // Skip empty required sections to debug required text output.
            if (
                $coursesection->required &&
                empty($coursesection->courses)
            ) {
                $coursedata[$index] = $coursesection;

                continue;
            }

            $coursecount = count($coursesection->courses);
            $randlimit = mt_rand(5, 50);

            for ($offset = max($coursecount, 1); $offset <= $randlimit; $offset++) {
                $fullname = "Example Course #$offset";

                // Make every 1 in 10 courses have a long name to trigger wrapping.
                if (mt_rand(0, 10) == 0) {
                    $fullname .= ' - Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua';
                }

                // Make every 1 in 15 courses have CPD data to trigger CPD display.
                $cpd = null;
                if (mt_rand(0, 15) == 0) {
                    $cpd = (string)mt_rand(15, 60);
                }

                // Make every 1 in 20 courses have at least two completions to trigger grouping.
                $completioncount = 1;
                if (mt_rand(0, 20) == 0) {
                    $completioncount = mt_rand(2, 5);
                }

                for ($completionoffset = 0; $completionoffset < $completioncount; $completionoffset++) {
                    $coursesection->courses[] = (object)[
                        'id' => $offset,
                        'fullname' => $fullname,
                        'timecompleted' => mt_rand(1, time()),
                        'cpd' => $cpd,
                    ];
                }
            }

            $coursedata[$index] = $coursesection;
        }

        return $coursedata;
    }
}
