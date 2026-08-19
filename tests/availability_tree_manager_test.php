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

namespace availability_managed;

use availability_managed\local\availability_tree_manager;

/**
 * Availability tree mutation tests.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(availability_tree_manager::class)]
final class availability_tree_manager_test extends \advanced_testcase {
    public function test_ensure_empty_tree(): void {
        $actual = json_decode(availability_tree_manager::ensure_in_json(null));
        $this->assertEquals((object) [
            'op' => '&',
            'c' => [(object) ['type' => 'managed']],
            'showc' => [false],
        ], $actual);
    }

    public function test_ensure_existing_and_tree_preserves_conditions(): void {
        $original = '{"op":"&","c":[{"type":"date","d":">=","t":123},{"type":"grade","id":7,"min":50}],"showc":[true,false]}';
        $actual = json_decode(availability_tree_manager::ensure_in_json($original));

        $this->assertSame('&', $actual->op);
        $this->assertEquals((object) ['type' => 'date', 'd' => '>=', 't' => 123], $actual->c[0]);
        $this->assertEquals((object) ['type' => 'grade', 'id' => 7, 'min' => 50], $actual->c[1]);
        $this->assertEquals((object) ['type' => 'managed'], $actual->c[2]);
        $this->assertSame([true, false, false], $actual->showc);
    }

    public function test_ensure_or_tree_wraps_it_in_and(): void {
        $original = '{"op":"|","c":[{"type":"group","id":2},{"type":"group","id":3}],"show":true}';
        $actual = json_decode(availability_tree_manager::ensure_in_json($original));

        $this->assertSame('&', $actual->op);
        $this->assertSame('|', $actual->c[0]->op);
        $this->assertCount(2, $actual->c[0]->c);
        $this->assertEquals((object) ['type' => 'managed'], $actual->c[1]);
    }

    public function test_nested_tree_and_duplicate_markers_are_normalised(): void {
        $original = '{"op":"&","c":[{"op":"|","c":[{"type":"managed"},' .
            '{"type":"date","d":"<","t":456}],"show":true},' .
            '{"type":"managed"}],"showc":[true,false]}';
        $actual = json_decode(availability_tree_manager::ensure_in_json($original));

        $this->assertCount(2, $actual->c);
        $this->assertSame('date', $actual->c[0]->c[0]->type);
        $this->assertTrue($actual->c[0]->show);
        $this->assertSame('managed', $actual->c[1]->type);
    }

    public function test_ensure_is_idempotent(): void {
        $once = availability_tree_manager::ensure_in_json(
            '{"op":"&","c":[{"type":"date","d":">=","t":123}],"showc":[true]}'
        );
        $this->assertSame($once, availability_tree_manager::ensure_in_json($once));
    }

    public function test_remove_only_managed_and_preserves_unrelated_tree(): void {
        $original = '{"op":"&","c":[{"op":"|","c":[{"type":"group","id":2},' .
            '{"type":"managed"}],"show":true},' .
            '{"type":"date","d":">=","t":123},{"type":"managed"}],"showc":[false,true,false]}';
        $actual = json_decode(availability_tree_manager::remove_from_json($original));

        $this->assertSame('&', $actual->op);
        $this->assertCount(2, $actual->c);
        $this->assertEquals((object) ['type' => 'group', 'id' => 2], $actual->c[0]->c[0]);
        $this->assertTrue($actual->c[0]->show);
        $this->assertEquals((object) ['type' => 'date', 'd' => '>=', 't' => 123], $actual->c[1]);
        $this->assertSame([false, true], $actual->showc);
    }

    public function test_remove_last_condition_returns_null(): void {
        $this->assertNull(availability_tree_manager::remove_from_json(
            '{"op":"&","c":[{"type":"managed"}],"showc":[false]}'
        ));
    }

    public function test_invalid_json_fails_without_rewriting(): void {
        $this->expectException(\coding_exception::class);
        availability_tree_manager::ensure_in_json('{broken');
    }

    public function test_invalid_nested_tree_fails_without_rewriting(): void {
        $this->expectException(\coding_exception::class);
        availability_tree_manager::ensure_in_json(
            '{"op":"&","c":[{"op":"|","c":[{"type":"date"}]}],"showc":[true]}'
        );
    }

    public function test_generated_tree_is_accepted_by_moodle_core(): void {
        $json = availability_tree_manager::ensure_in_json(
            '{"op":"|","c":[{"type":"group","id":2},{"type":"date","d":">=","t":123}],"show":false}'
        );

        $tree = new \core_availability\tree(json_decode($json), false);
        $saved = $tree->save();
        $this->assertSame('&', $saved->op);
        $this->assertSame('|', $saved->c[0]->op);
        $this->assertSame('managed', $saved->c[1]->type);
    }
}
