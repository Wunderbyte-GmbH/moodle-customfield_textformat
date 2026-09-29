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

namespace customfield_textformat;

use core_course\reportbuilder\datasource\courses;
use core_reportbuilder\manager;

/**
 * Tests the context used to format the default value.
 *
 * @package    customfield_textformat
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(data_controller::class)]
final class default_value_context_test extends \advanced_testcase {
    /** @var \core_customfield\field_controller */
    private $field;

    /**
     * Creates a course custom field with a default value.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_customfield');
        $category = $generator->create_category();
        $this->field = $generator->create_field([
            'categoryid' => $category->get('id'),
            'shortname' => 'textdefault',
            'type' => 'textformat',
            'configdata' => [
                'defaultvalue' => 'Defvalue',
            ],
        ]);
    }

    /**
     * Reads the page context without triggering the magic getter.
     *
     * @return \context|null
     */
    private function get_raw_page_context(): ?\context {
        global $PAGE;
        return \Closure::bind(fn() => $this->_context ?? null, $PAGE, \moodle_page::class)();
    }

    /**
     * Without a page context the default value is formatted without touching $PAGE.
     */
    public function test_default_value_without_page_context(): void {
        global $PAGE;
        $PAGE = new \moodle_page();

        $value = data_controller::create(0, null, $this->field)->get_default_value();

        $this->assertSame('Defvalue', $value);
        $this->assertDebuggingNotCalled();
        $this->assertNull($this->get_raw_page_context());
    }

    /**
     * With a page context the default value is formatted in that context.
     */
    public function test_default_value_uses_page_context(): void {
        global $PAGE;
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $PAGE = new \moodle_page();
        $PAGE->set_context($coursecontext);

        $controller = data_controller::create(0, null, $this->field);
        $method = new \ReflectionMethod($controller, 'get_page_context');

        $this->assertSame($coursecontext->id, $method->invoke($controller)->id);
    }

    /**
     * Report builder web services build the report before validate_context() sets the page context.
     */
    public function test_report_builder_build_without_page_context(): void {
        global $PAGE;
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_reportbuilder');
        $report = $generator->create_report(['name' => 'Courses', 'source' => courses::class, 'default' => false]);
        $PAGE = new \moodle_page();

        manager::get_report_from_id($report->get('id'));

        $this->assertDebuggingNotCalled();
        $this->assertNull($this->get_raw_page_context());
    }
}
