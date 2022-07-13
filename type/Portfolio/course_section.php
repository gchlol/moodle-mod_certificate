<?php

namespace mod_certificate\type\Portfolio;

use stdClass;

class course_section {
    /**
     * @var string $header Header string for the section.
     */
    public $header;

    /**
     * @var string $description Description string for the section.
     */
    public $description;

    /**
     * @var stdClass[] $courses Array of completed courses with `fullname` and `timecompleted` components.
     */
    public $courses;

    /**
     * @var bool $required Whether section output is required regardless of courses being empty or not.
     */
    public $required;

    public function __construct($header, $description, $courses, $required) {
        $this->header = $header;
        $this->description = $description;
        $this->courses = $courses;
        $this->required = $required;
    }
}