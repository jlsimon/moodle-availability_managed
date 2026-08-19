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
 * Adds and removes the managed marker without changing unrelated conditions.
 *
 * All JSON manipulation is kept in this class. Persistence is deliberately
 * narrow and followed by a course cache rebuild, matching Moodle core's own
 * availability info persistence behaviour.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class availability_tree_manager {
    /** Managed condition type. */
    private const TYPE = 'managed';

    /**
     * Ensure a course module has exactly one, top-level ANDed marker.
     *
     * @param int $cmid course_modules.id
     */
    public function ensure_managed_condition_for_cm(int $cmid): void {
        global $DB;

        $record = $DB->get_record('course_modules', ['id' => $cmid], 'id,course,availability', MUST_EXIST);
        $availability = self::ensure_in_json($record->availability);
        if ($availability !== $record->availability) {
            $DB->set_field('course_modules', 'availability', $availability, ['id' => $cmid]);
            self::rebuild_course_cache((int) $record->course);
        }
    }

    /**
     * Ensure a course section has exactly one, top-level ANDed marker.
     *
     * @param int $sectionid course_sections.id
     */
    public function ensure_managed_condition_for_section(int $sectionid): void {
        $this->update_section($sectionid, true);
    }

    /**
     * Remove all managed markers from a course module.
     *
     * @param int $cmid course_modules.id
     */
    public function remove_managed_condition_from_cm(int $cmid): void {
        global $DB;

        $record = $DB->get_record('course_modules', ['id' => $cmid], 'id,course,availability', MUST_EXIST);
        $availability = self::remove_from_json($record->availability);
        if ($availability !== $record->availability) {
            $DB->set_field('course_modules', 'availability', $availability, ['id' => $cmid]);
            self::rebuild_course_cache((int) $record->course);
        }
    }

    /**
     * Remove all managed markers from a course section.
     *
     * @param int $sectionid course_sections.id
     */
    public function remove_managed_condition_from_section(int $sectionid): void {
        $this->update_section($sectionid, false);
    }

    /**
     * Check a course module for a managed marker.
     *
     * @param int $cmid course_modules.id
     * @return bool
     */
    public function has_managed_condition_for_cm(int $cmid): bool {
        global $DB;
        $json = $DB->get_field('course_modules', 'availability', ['id' => $cmid], MUST_EXIST);
        return self::has_in_json($json);
    }

    /**
     * Check a course section for a managed marker.
     *
     * @param int $sectionid course_sections.id
     * @return bool
     */
    public function has_managed_condition_for_section(int $sectionid): bool {
        global $DB;
        $json = $DB->get_field('course_sections', 'availability', ['id' => $sectionid], MUST_EXIST);
        return self::has_in_json($json);
    }

    /**
     * Pure JSON operation used by persistence methods and unit tests.
     *
     * @param string|null $json availability JSON
     * @return string canonical availability JSON
     */
    public static function ensure_in_json(?string $json): string {
        $root = self::decode($json);
        if ($root === null) {
            return self::encode(self::new_root());
        }

        if (self::is_canonical($root)) {
            return self::encode($root);
        }

        self::remove_from_node($root);
        if (empty($root->c)) {
            $root = self::new_root();
        } else if ($root->op === '&') {
            $root->c[] = self::marker();
            $root->showc[] = false;
        } else {
            self::convert_root_to_nested($root);
            $root = (object) [
                'op' => '&',
                'c' => [$root, self::marker()],
                'showc' => [false, false],
            ];
        }
        return self::encode($root);
    }

    /**
     * Pure JSON removal operation.
     *
     * @param string|null $json availability JSON
     * @return string|null null when no restrictions remain
     */
    public static function remove_from_json(?string $json): ?string {
        $root = self::decode($json);
        if ($root === null) {
            return null;
        }
        self::remove_from_node($root);
        return empty($root->c) ? null : self::encode($root);
    }

    /**
     * Check JSON recursively.
     *
     * @param string|null $json availability JSON
     * @return bool
     */
    public static function has_in_json(?string $json): bool {
        $root = self::decode($json);
        return $root !== null && self::node_contains_managed($root);
    }

    /**
     * Count managed markers recursively.
     *
     * @param string|null $json availability JSON
     * @return int
     */
    public static function count_in_json(?string $json): int {
        $root = self::decode($json);
        return $root === null ? 0 : self::count_in_node($root);
    }

    /**
     * Update a section and its timestamp.
     *
     * @param int $sectionid section id
     * @param bool $ensure true to ensure, false to remove
     */
    private function update_section(int $sectionid, bool $ensure): void {
        global $DB;

        $record = $DB->get_record('course_sections', ['id' => $sectionid], 'id,course,availability', MUST_EXIST);
        $availability = $ensure
            ? self::ensure_in_json($record->availability)
            : self::remove_from_json($record->availability);
        if ($availability !== $record->availability) {
            $DB->update_record('course_sections', (object) [
                'id' => $sectionid,
                'availability' => $availability,
                'timemodified' => time(),
            ]);
            self::rebuild_course_cache((int) $record->course);
        }
    }

    /**
     * Decode and structurally validate a Moodle availability tree.
     *
     * @param string|null $json availability JSON
     * @return \stdClass|null
     */
    private static function decode(?string $json): ?\stdClass {
        if ($json === null || $json === '') {
            return null;
        }
        try {
            $root = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \coding_exception('Invalid availability JSON', $exception->getMessage());
        }
        if (!is_object($root)) {
            throw new \coding_exception('Invalid availability tree structure');
        }
        self::normalise_legacy_display_fields($root, true);
        self::validate_tree($root, true);
        return $root;
    }

    /**
     * Validate every tree node before it can be rewritten.
     *
     * @param \stdClass $tree tree node
     * @param bool $root whether this is the root node
     */
    private static function validate_tree(\stdClass $tree, bool $root): void {
        if (
            !isset($tree->op) || !is_string($tree->op)
                || !isset($tree->c) || !is_array($tree->c)
        ) {
            throw new \coding_exception('Invalid availability tree structure');
        }
        $useschildren = $root && in_array($tree->op, ['&', '!|'], true);
        if (
            $useschildren && (!isset($tree->showc) || !is_array($tree->showc)
                || count($tree->c) !== count($tree->showc))
        ) {
            throw new \coding_exception('Invalid availability root display structure');
        }
        if (!$useschildren && (!isset($tree->show) || !is_bool($tree->show))) {
            throw new \coding_exception('Invalid availability group display structure');
        }
        foreach ($tree->c as $child) {
            if (self::is_tree($child)) {
                self::validate_tree($child, false);
            } else if (!is_object($child) || !isset($child->type) || !is_string($child->type)) {
                throw new \coding_exception('Invalid availability condition structure');
            }
        }
    }

    /**
     * Convert Phase 0 legacy display fields to Moodle's representation.
     *
     * @param \stdClass $tree tree node
     * @param bool $root whether this is the root node
     */
    private static function normalise_legacy_display_fields(\stdClass $tree, bool $root): void {
        if ($root && isset($tree->show) && is_array($tree->show) && !isset($tree->showc)) {
            $tree->showc = $tree->show;
            unset($tree->show);
        }
        foreach ($tree->c ?? [] as $child) {
            if (self::is_tree($child)) {
                if (isset($child->show) && is_array($child->show)) {
                    $child->show = !in_array(false, $child->show, true);
                }
                self::normalise_legacy_display_fields($child, false);
            }
        }
    }

    /**
     * Convert a root group to the nested-group display representation.
     *
     * @param \stdClass $tree root tree
     */
    private static function convert_root_to_nested(\stdClass $tree): void {
        if (isset($tree->showc)) {
            $tree->show = !in_array(false, $tree->showc, true);
            unset($tree->showc);
        }
    }

    /**
     * Rebuild course caches after an availability field changes.
     *
     * @param int $courseid course id
     */
    private static function rebuild_course_cache(int $courseid): void {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        rebuild_course_cache($courseid, true);
    }

    /**
     * Remove markers recursively, including empty nested trees.
     *
     * @param \stdClass $tree tree node
     */
    private static function remove_from_node(\stdClass $tree): void {
        $children = [];
        $showchildren = isset($tree->showc) ? [] : null;
        foreach ($tree->c as $index => $child) {
            if (self::is_marker($child)) {
                continue;
            }
            if (self::is_tree($child)) {
                self::remove_from_node($child);
                if (empty($child->c)) {
                    continue;
                }
            }
            $children[] = $child;
            if ($showchildren !== null) {
                $showchildren[] = $tree->showc[$index];
            }
        }
        $tree->c = $children;
        if ($showchildren !== null) {
            $tree->showc = $showchildren;
        }
    }

    /**
     * Create a default AND root containing the marker.
     *
     * @return \stdClass
     */
    private static function new_root(): \stdClass {
        return (object) ['op' => '&', 'c' => [self::marker()], 'showc' => [false]];
    }

    /**
     * Create a marker condition.
     *
     * @return \stdClass
     */
    private static function marker(): \stdClass {
        return (object) ['type' => self::TYPE];
    }

    /**
     * Determine whether a node is the managed marker.
     *
     * @param mixed $node node
     * @return bool
     */
    private static function is_marker($node): bool {
        return is_object($node) && isset($node->type) && $node->type === self::TYPE;
    }

    /**
     * Determine whether a node is a condition group.
     *
     * @param mixed $node node
     * @return bool
     */
    private static function is_tree($node): bool {
        return is_object($node) && isset($node->op) && isset($node->c) && is_array($node->c);
    }

    /**
     * Search a group recursively for managed markers.
     *
     * @param \stdClass $node node
     * @return bool
     */
    private static function node_contains_managed(\stdClass $node): bool {
        foreach ($node->c as $child) {
            if (self::is_marker($child)) {
                return true;
            }
            if (self::is_tree($child) && self::node_contains_managed($child)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Count managed markers in a node.
     *
     * @param \stdClass $node tree node
     * @return int
     */
    private static function count_in_node(\stdClass $node): int {
        $count = 0;
        foreach ($node->c as $child) {
            if (self::is_marker($child)) {
                $count++;
            } else if (self::is_tree($child)) {
                $count += self::count_in_node($child);
            }
        }
        return $count;
    }

    /**
     * Determine whether a root is already in canonical managed form.
     *
     * @param \stdClass $root root
     * @return bool
     */
    private static function is_canonical(\stdClass $root): bool {
        if ($root->op !== '&') {
            return false;
        }
        $count = 0;
        foreach ($root->c as $child) {
            if (self::is_marker($child)) {
                $count++;
            } else if (self::is_tree($child) && self::node_contains_managed($child)) {
                return false;
            }
        }
        return $count === 1;
    }

    /**
     * Encode a tree consistently for storage.
     *
     * @param \stdClass $root root
     * @return string
     */
    private static function encode(\stdClass $root): string {
        return json_encode($root, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
