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
 * Writes the product audit trail.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class audit_repository {
    /**
     * Record one functional change.
     *
     * @param int $courseid course id
     * @param string $action action identifier
     * @param int $userid acting user id
     * @param string|null $itemtype affected item type
     * @param int|null $itemid affected item id
     * @param string|null $oldvalue previous serialized value
     * @param string|null $newvalue new serialized value
     */
    public function record(
        int $courseid,
        string $action,
        int $userid,
        ?string $itemtype = null,
        ?int $itemid = null,
        ?string $oldvalue = null,
        ?string $newvalue = null
    ): void {
        global $DB;
        $record = compact('courseid', 'itemtype', 'itemid', 'action', 'oldvalue', 'newvalue', 'userid');
        $record['timecreated'] = time();
        $DB->insert_record('availability_managed_audit', (object) $record);
    }
}
