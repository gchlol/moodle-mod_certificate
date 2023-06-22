<?php

namespace mod_certificate\util;

use coding_exception;
use context;
use context_system;
use core_user\fields;
use dml_exception;
use user_picture;

class user_field_util {

    /**
     * Get the display name of a field by name.
     *
     * @param string $field Field name.
     * @return string Field display name.
     * @throws coding_exception
     */
    public static function get_field_name(string $field): string {
        if (self::use_legacy()) {
            /** @noinspection PhpDeprecationInspection */
            return get_user_field_name($field);
        }

        return fields::get_display_name($field);
    }

    /**
     * Get a list of user name fields.
     *
     * @return string[] List of name field names.
     */
    public static function get_name_fields(): array {
        if (self::use_legacy()) {
            /** @noinspection PhpDeprecationInspection */
            return get_all_user_name_fields();
        }

        return fields::get_name_fields();
    }

    /**
     * Get a list of extra user fields.
     *
     * @param context|null $context Optional field context.
     * @param string[]|null $exclude Optional list of fields to exclude.
     * @return string[] List of extra field names.
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_extra_fields(?context $context = null, ?array $exclude = null): array {
        if ($context === null) {
            $context = context_system::instance();
        }

        if (self::use_legacy()) {
            /** @noinspection PhpDeprecationInspection */
            return get_extra_user_fields($context, $exclude);
        }

        $user_fields = fields::for_identity($context, false);
        if ($exclude !== null) {
            $user_fields->excluding(...$exclude);
        }

        return $user_fields->get_required_fields();
    }

    /**
     * Get string list of fields required for selection in SQL to output user pictures.
     *
     * @param string $table_prefix Optional user field table prefix.
     * @param string[]|null $extra_fields Optional list of fields to include.
     * @return string SQL select string.
     */
    public static function user_pic_select(string $table_prefix = '', ?array $extra_fields = null): string {
        if (self::use_legacy()) {
            /** @noinspection PhpDeprecationInspection */
            return user_picture::fields($table_prefix, $extra_fields);
        }

        $user_fields = fields::for_userpic();
        if ($extra_fields !== null) {
            $user_fields->including(...$extra_fields);
        }

        return $user_fields->get_sql($table_prefix, false, '', '', false)->selects;
    }

    /**
     * Indicate whether legacy user field functions should be used instead of the new classes.
     *
     * @return bool Use legacy functions.
     */
    private static function use_legacy(): bool {
        return !class_exists('\core_user\fields');
    }
}