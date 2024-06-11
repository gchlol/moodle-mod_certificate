<?php

namespace mod_certificate\type\Portfolio;

use dml_exception;
use stdClass;

require_once(__DIR__ . '/../Portfolio/course_section.php');

class portfolio_data {

    private const REQUIRED_WORD = 'required';

    /**
     * Gets the formatted course completion data for a given user.
     *
     * @param int $user_id ID of the user to retrieve completion data for.
     * @return course_section[] List of {@link course_section} data.
     * @throws dml_exception
     */
    public static function get_course_section_data(int $user_id, bool $debug = false): array {
        $header_fields = self::get_header_custom_fields();

        $course_data = [];
        foreach ($header_fields as $header_field) {
            $description = $header_field->description ?? '';
            $required = false;

            if ($description) {
                $description = self::cleanse_field_description($description);
                $required = self::is_field_required($description);

                if ($required) {
                    $description = self::strip_required_word($description);
                }
            }

            $course_data[] = new course_section(
                $header_field->name,
                $description,
                self::get_courses($header_field->id, $user_id),
                $required
            );
        }

        if (
            $debug ||
            isset($_GET['debug'])
        ) {
            $course_data = self::populate_debug_data($course_data);
        }

        return $course_data;
    }

    /**
     * Get the courses identified by the given custom field id that have been completed by the given user.
     *
     * @param int $custom_field_id Custom field ID that courses must have a value for.
     * @param int $user_id ID of the user to retrieve completion data for.
     * @return stdClass[] List of courses completed by the user within the custom field filtering.
     * @throws dml_exception
     */
    private static function get_courses(int $custom_field_id, int $user_id): array {
        global $DB;

        $course_sql = "
            SELECT  c.fullname,
                    max(cc.timecompleted) as timecompleted
            FROM    {course} c
                    INNER JOIN {customfield_data} cd ON
                        cd.instanceid = c.id AND
                        cd.intvalue = 1 AND
                        cd.fieldid = ? 
                    INNER JOIN (
                        (
                            SELECT  *
                            FROM    {course_completions} ccc 
                            WHERE   ccc.userid = ?
                        ) UNION
                        (
                            SELECT  *
                            FROM    {local_recompletion_cc} rcc
                            WHERE   rcc.userid = ?
                        )
                    ) cc ON
                        cc.course = c.id AND
                        cc.userid = ? AND 
                        cc.timecompleted IS NOT NULL
            GROUP BY c.id
            ORDER BY c.fullname
        ";

        // Moodle doesn't allow reusing named params so, we need to do this instead
        $course_params = [ $custom_field_id, $user_id, $user_id, $user_id ];

        return $DB->get_records_sql(
            $course_sql,
            $course_params
        );
    }

    /**
     * Get the list of custom fields identified by the `port_` prefix as portfolio headers.
     *
     * @return stdClass[] A list of custom field values containing; `id`, `name`, and `description`.
     * @throws dml_exception
     */
    private static function get_header_custom_fields(): array {
        global $DB;

        return $DB->get_records_select(
            'customfield_field',
            $DB->sql_like('shortname', ':shortname'),
            ['shortname' => 'port_%'],
            'sortorder',
            'id, name, description'
        );
    }

    /**
     * Searches for the {@link REQUIRED_WORD} at the beginning of a string.
     *
     * @param string $description String to be searched.
     * @return bool Whether the {@link REQUIRED_WORD} was found.
     */
    private static function is_field_required(string $description): bool {
        return substr($description, 0, strlen(self::REQUIRED_WORD)) === self::REQUIRED_WORD;
    }

    /**
     * Removes the {@link REQUIRED_WORD} from the beginning of a string.
     *
     * @param string $description String to operate on.
     * @return string Input string less the {@link REQUIRED_WORD}.
     */
    private static function strip_required_word(string $description): string {
        return substr($description, strlen(self::REQUIRED_WORD));
    }

    /**
     * Cleanse a description string of invalid content such as HTML tags.
     *
     * @param string $description String to operate on.
     * @return string Input string less invalid content.
     */
    private static function cleanse_field_description(string $description): string {
        return strip_tags($description);
    }

    /**
     * Add debug data to an existing list of course sections.
     *
     * @param course_section[] $base_course_data Base list of course sections.
     * @return course_section[] Updated list of course sections.
     */
    protected static function populate_debug_data(array $base_course_data): array {
        $course_data = [];

        foreach ($base_course_data as $index => $base_course_section) {
            $course_section = clone $base_course_section;

            // Skip empty required sections to debug required text output
            if (
                $course_section->required &&
                empty($course_section->courses)
            ) {
                $course_data[$index] = $course_section;

                continue;
            }

            $course_count = count($course_section->courses);
            $rand_limit = mt_rand(5, 50);

            for ($offset = max($course_count, 1); $offset <= $rand_limit ; $offset++) {
                $fullname = "Example Course #$offset";

                // Make every 1 in 10 courses have a long name to trigger wrapping
                if (mt_rand(0, 10) == 0) {
                    $fullname .= ' - Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua';
                }

                $course_section->courses[] = (object)[
                    'fullname' => $fullname,
                    'timecompleted' => mt_rand(1, time()),
                ];
            }

            $course_data[$index] = $course_section;
        }

        return $course_data;
    }
}