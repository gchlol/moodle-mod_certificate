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

use stdClass;

/**
 * Course section data container.
 *
 * @package    mod_certificate
 * @copyright  2022 Gold Coast Health
 * @author     Nicholas Lambell
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_section {
    /**
     * @var string $header Header string for the section.
     */
    public string $header;

    /**
     * @var string $description Description string for the section.
     */
    public string $description;

    /**
     * @var stdClass[] $courses Array of completed courses with `fullname` and `timecompleted` components.
     */
    public array $courses;

    /**
     * @var bool $required Whether section output is required regardless of courses being empty or not.
     */
    public bool $required;

    /**
     * Constructor.
     *
     * @param $header
     * @param $description
     * @param $courses
     * @param $required
     */
    public function __construct($header, $description, $courses, $required) {
        $this->header = $header;
        $this->description = $description;
        $this->courses = $courses;
        $this->required = $required;
    }
}
