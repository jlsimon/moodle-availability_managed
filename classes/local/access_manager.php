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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace availability_managed\local;

/**
 * Resolves managed access using a request-level course/user cache.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access_manager {
    /** @var array permitted item sets keyed by course and user */
    private static array $cache = [];

    /** @var rule_repository rule persistence */
    private rule_repository $repository;

    /**
     * Constructor.
     *
     * @param rule_repository|null $repository optional repository
     */
    public function __construct(?rule_repository $repository = null) {
        $this->repository = $repository ?? new rule_repository();
    }

    /**
     * Determine whether a user passes the managed condition.
     *
     * @param int $courseid course id
     * @param string $itemtype section or cm
     * @param int $itemid item id
     * @param int $userid user id
     * @return bool
     */
    public function is_allowed(int $courseid, string $itemtype, int $itemid, int $userid): bool {
        if (!in_array($itemtype, rule_repository::ITEM_TYPES, true)) {
            return false;
        }
        $key = $courseid . ':' . $userid;
        if (!array_key_exists($key, self::$cache)) {
            self::$cache[$key] = $this->build_allowed_set($courseid, $userid);
        }
        return isset(self::$cache[$key][$itemtype . ':' . $itemid]);
    }

    /**
     * Reset request-level state, primarily after writes and in tests.
     */
    public static function reset_cache(): void {
        self::$cache = [];
    }

    /**
     * Load all matching rules once and reduce them to item lookups.
     *
     * @param int $courseid course id
     * @param int $userid user id
     * @return array
     */
    private function build_allowed_set(int $courseid, int $userid): array {
        global $CFG;

        require_once($CFG->libdir . '/grouplib.php');
        if (
            !$this->repository->is_course_enabled($courseid)
                || !is_enrolled(\context_course::instance($courseid), $userid, '', true)
        ) {
            return [];
        }
        $groupids = array_map('intval', array_keys(groups_get_all_groups($courseid, $userid, 0, 'g.id')));
        $groupset = array_fill_keys($groupids, true);
        $allowed = [];

        foreach ($this->repository->get_enabled_for_course($courseid) as $rule) {
            $matches = ($rule->scope === 'course' && (int) $rule->scopeid === $courseid)
                || ($rule->scope === 'user' && (int) $rule->scopeid === $userid)
                || ($rule->scope === 'group' && isset($groupset[(int) $rule->scopeid]));
            if ($matches) {
                $allowed[$rule->itemtype . ':' . $rule->itemid] = true;
            }
        }
        return $allowed;
    }
}
