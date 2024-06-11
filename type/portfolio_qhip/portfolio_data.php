<?php

namespace mod_certificate\type\portfolio_qhip;

use dml_exception;
use mod_certificate\type\Portfolio\course_section;
use stdClass;

require_once(__DIR__ . '/../Portfolio/portfolio_data.php');

class portfolio_data extends \mod_certificate\type\Portfolio\portfolio_data {

    /**
     * @inheritDoc
     */
    public static function get_course_section_data(int $user_id, bool $debug = false): array {
        $course_data = [
            new course_section(
                'QHIP Courses',
                '',
                self::get_qhip_courses($user_id),
                false
            ),
        ];

        if (
            $debug ||
            isset($_GET['debug'])
        ) {
            $course_data = self::populate_debug_data($course_data);
        }

        return $course_data;
    }

    /**
     * Get QHIP course data.
     *
     * @param int $user_id Target user ID.
     * @return stdClass[] List of QHIP Courses.
     * @throws dml_exception
     */
    private static function get_qhip_courses(int $user_id): array {
        global $DB;

        return $DB->get_records_sql(
            "
                select  qhiptr.*
                from    (
                            select  course.fullname,
                                    course_recompletions.timecompleted
                
                            from    {local_recompletion_cc} course_recompletions
                                    join {course} course on
                                        course.id = course_recompletions.course
                
                            where   course_recompletions.timecompleted is not null and
                                    course_recompletions.userid = :user_recompletion and
                                    course.category in (25, 26)
                
                        union all
                
                            select  course.fullname,
                                    course_completions.timecompleted
                
                            from    {course_completions} course_completions
                                    join {course} course on
                                        course.id = course_completions.course
                
                            where   course_completions.timecompleted is not null and
                                    course_completions.userid = :user_completion and
                                    course.category in (25, 26)
                
                        union all
                
                            select  case
                                        when course_modules.id = '13527' then (select fullname from {course} where id = 606)
                                        when course_modules.id = '13507' then (select fullname from {course} where id = 607)
                                        when course_modules.id = '13516' then (select fullname from {course} where id = 608)
                                        when course_modules.id = '13509' then (select fullname from {course} where id = 609)
                                        when course_modules.id = '13517' then (select fullname from {course} where id = 610)
                                        when course_modules.id = '13522' then (select fullname from {course} where id = 611)
                                        when course_modules.id = '13523' then (select fullname from {course} where id = 612)
                                        when course_modules.id = '13524' then (select fullname from {course} where id = 613)
                                        when course_modules.id = '13525' then (select fullname from {course} where id = 614)
                                        when course_modules.id = '13526' then (select fullname from {course} where id = 615)
                                        when course_modules.id = '26878' then (select fullname from {course} where id = 616)
                                        when course_modules.id = '26879' then (select fullname from {course} where id = 617)
                                        when course_modules.id = '13528' then (select fullname from {course} where id = 620)
                                        when course_modules.id = '13529' then (select fullname from {course} where id = 619)
                                        when course_modules.id = '26883' then (select fullname from {course} where id = 618)
                                        when course_modules.id = '26884' then (select fullname from {course} where id = 621)
                                        when course_modules.id = '26885' then (select fullname from {course} where id = 622)
                                        when course_modules.id = '13531' then (select fullname from {course} where id = 624)
                                        when course_modules.id = '26894' then (select fullname from {course} where id = 625)
                                        when course_modules.id = '26895' then (select fullname from {course} where id = 626)
                                        when course_modules.id = '26896' then (select fullname from {course} where id = 627)
                                        else course.fullname
                                    end as 'fullname',
                                    modules_completion.timemodified as 'timecompleted'
                
                            from    {course} course
                                    join {enrol} enrol on
                                        enrol.courseid = course.id
                                    join {user_enrolments} user_enrolments on
                                        user_enrolments.enrolid = enrol.id
                                    left join {course_modules} course_modules ON
                                        course_modules.course = course.id
                                    left join {modules} modules ON
                                        modules.id = course_modules.module
                                    left join {course_modules_completion} modules_completion ON
                                        modules_completion.coursemoduleid = course_modules.id and
                                        modules_completion.userid = user_enrolments.userid
                
                            where   -- include only QHIP categories both the current and archive
                                    course.category in (25, 26) and
                                    course.id in (470, 471, 472, 473) and
                                    user_enrolments.userid = :user_modules and
                                    modules.name = 'scorm' and
                                    modules_completion.completionstate in (1, 2)
                        ) qhiptr
                
                order by qhiptr.fullname, qhiptr.timecompleted desc
            ",
            [
                'user_recompletion' => $user_id,
                'user_completion' => $user_id,
                'user_modules' => $user_id,
            ]
        );
    }
}