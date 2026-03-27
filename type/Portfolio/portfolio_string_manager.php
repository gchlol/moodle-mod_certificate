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

use core_component;
use core_string_manager_standard;

/**
 * Custom portfolio string manager.
 *
 * @package    mod_certificate
 * @copyright  2022 Gold Coast Health
 * @author     Nicholas Lambell
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class portfolio_string_manager extends core_string_manager_standard {

    /**
     * @var string|null Optional portfolio language override directory root.
     */
    private ?string $portfolio_root;

    /**
     * Constructor.
     *
     * @param string|null $portfolio_root Optional portfolio language override directory root.
     */
    public function __construct(?string $portfolio_root = null) {
        $core_manager = get_string_manager();

        parent::__construct(
            $core_manager->otherroot,
            $core_manager->localroot,
            $core_manager->translist,
            $core_manager->transaliases
        );

        $this->portfolio_root = $portfolio_root;
    }

    /**
     * Load all strings for one component.
     *
     * @param string $component The module the string is associated with
     * @param string $lang Language
     * @param bool $disablecache Do not use caches, force fetching the strings from sources
     * @param bool $disablelocal Do not use customized strings in xx_local language packs
     * @return array of all string for given component and lang
     */
    public function load_component_strings($component, $lang, $disablecache = false, $disablelocal = false): array {
        $string = parent::load_component_strings(
            $component,
            $lang,
            $disablecache,
            $disablelocal
        );

        if (!$this->portfolio_root) {
            return $string;
        }

        $file = self::get_component_file_name($component);

        // Inject additional strings from portfolio lang file that are otherwise removed in parent call.
        if (file_exists("$this->portfolio_root/en/$file.php")) {
            include("$this->portfolio_root/en/$file.php");
        }

        $dependencies = $this->get_language_dependencies($lang);
        foreach ($dependencies as $dependency) {
            if (file_exists("$this->portfolio_root/$dependency/$file.php")) {
                include("$this->portfolio_root/$dependency/$file.php");
            }
        }

        return $string;
    }

    /**
     * Get language file name for a given component.
     *
     * @param string $component Component.
     * @return string Language file name.
     */
    private static function get_component_file_name(string $component): string {
        [ $plugin_type, $plugin_name ] = core_component::normalize_component($component);
        if ($plugin_type === 'core') {
            return $plugin_name ?? 'moodle';
        }

        return $plugin_type === 'mod' ? $plugin_name : "{$plugin_type}_$plugin_name";
    }
}
