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
 * Immutable reconciliation counters.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reconciliation_result {
    /** @var int missing markers added */
    public readonly int $added;

    /** @var int duplicate markers removed */
    public readonly int $duplicatesremoved;

    /** @var int orphan rules removed */
    public readonly int $orphanrulesremoved;

    /** @var int items inspected */
    public readonly int $itemschecked;

    /**
     * Constructor.
     *
     * @param int $added missing markers added
     * @param int $duplicatesremoved duplicate markers removed
     * @param int $orphanrulesremoved orphan rules removed
     * @param int $itemschecked items inspected
     */
    public function __construct(int $added, int $duplicatesremoved, int $orphanrulesremoved, int $itemschecked) {
        $this->added = $added;
        $this->duplicatesremoved = $duplicatesremoved;
        $this->orphanrulesremoved = $orphanrulesremoved;
        $this->itemschecked = $itemschecked;
    }
}
