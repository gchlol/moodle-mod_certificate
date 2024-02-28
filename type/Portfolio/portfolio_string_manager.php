<?php

namespace mod_certificate\type\Portfolio;

use core_component;
use core_string_manager_standard;

class portfolio_string_manager extends core_string_manager_standard {

    private ?string $portfolio_root;

    /**
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
     * @inheritDoc
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
            include "$this->portfolio_root/en/$file.php";
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