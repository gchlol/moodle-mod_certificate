<?php

namespace mod_certificate\type\Portfolio;

use core_component;
use core_string_manager_standard;

class portfolio_string_manager extends core_string_manager_standard {

    /**
     * @param string|null $local_root Optional local language override directory root.
     */
    public function __construct(?string $local_root = null) {
        $core_manager = get_string_manager();

        parent::__construct(
            $core_manager->otherroot,
            $local_root ?? $core_manager->localroot,
            $core_manager->translist,
            $core_manager->transaliases
        );
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

        if ($disablelocal) {
            return $string;
        }

        [ $plugin_type, $plugin_name ] = core_component::normalize_component($component);
        $file = $plugin_type === 'mod' ? $plugin_name : "{$plugin_type}_$plugin_name";

        // Inject additional strings from local lang file that are otherwise removed in parent call.
        if (file_exists("$this->localroot/en_local/$file.php")) {
            include "$this->localroot/en_local/$file.php";
        }

        $dependencies = $this->get_language_dependencies($lang);
        foreach ($dependencies as $dependency) {
            if (file_exists("$this->localroot/{$dependency}_local/$file.php")) {
                include("$this->localroot/{$dependency}_local/$file.php");
            }
        }

        return $string;
    }
}