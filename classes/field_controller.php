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

/**
 * Customfields text plugin
 *
 * @package   customfield_textformat
 * @copyright 2018 David Matamoros <davidmc@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace customfield_textformat;

defined('MOODLE_INTERNAL') || die;

/**
 * Class field
 *
 * @copyright 2018 David Matamoros <davidmc@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package customfield_textformat
 */
class field_controller extends \core_customfield\field_controller {
    /**
     * Plugin type text
     */
    const TYPE = 'text';

    /**
     * Add fields for editing a text field.
     *
     * @param \MoodleQuickForm $mform
     */
    public function config_form_definition(\MoodleQuickForm $mform) {

        $mform->addElement('header', 'header_specificsettings', get_string('specificsettings', 'customfield_textformat'));
        $mform->setExpanded('header_specificsettings', true);

        $mform->addElement('text', 'configdata[defaultvalue]', get_string('defaultvalue', 'core_customfield'),
            ['size' => 50]);
        $mform->setType('configdata[defaultvalue]', PARAM_TEXT);

        $mform->addElement('text', 'configdata[displaysize]', get_string('displaysize', 'customfield_textformat'), ['size' => 6]);
        $mform->setType('configdata[displaysize]', PARAM_INT);
        if (!$this->get_configdata_property('displaysize')) {
            $mform->setDefault('configdata[displaysize]', 50);
        }
        $mform->addRule('configdata[displaysize]', null, 'numeric', null, 'client');

        $mform->addElement('text', 'configdata[maxlength]', get_string('maxlength', 'customfield_textformat'), ['size' => 6]);
        $mform->setType('configdata[maxlength]', PARAM_INT);
        if (!$this->get_configdata_property('maxlength')) {
            $mform->setDefault('configdata[maxlength]', 1333);
        }
        $mform->addRule('configdata[maxlength]', null, 'numeric', null, 'client');

        $mform->addElement('selectyesno', 'configdata[ispassword]', get_string('ispassword', 'customfield_textformat'));
        $mform->setType('configdata[ispassword]', PARAM_INT);

        $mform->addElement('text', 'configdata[link]', get_string('islink', 'customfield_textformat'), ['size' => 50]);
        $mform->setType('configdata[link]', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('configdata[link]', 'islink', 'customfield_textformat');

        $mform->disabledIf('configdata[link]', 'configdata[ispassword]', 'eq', 1);

        $linkstargetoptions = array(
            ''       => get_string('none', 'customfield_textformat'),
            '_blank' => get_string('newwindow', 'customfield_textformat'),
            '_self'  => get_string('sameframe', 'customfield_textformat'),
            '_top'   => get_string('samewindow', 'customfield_textformat')
        );
        $mform->addElement('select', 'configdata[linktarget]', get_string('linktarget', 'customfield_textformat'),
            $linkstargetoptions);

        $mform->disabledIf('configdata[linktarget]', 'configdata[link]', 'eq', '');
    }

    /**
     * Validate the data on the field configuration form
     *
     * @param array $data from the add/edit profile field form
     * @param array $files
     * @return array associative array of error messages
     */
    public function config_form_validation(array $data, $files = array()) : array {
        global $CFG;
        $errors = parent::config_form_validation($data, $files);

        $maxlength = (int)$data['configdata']['maxlength'];
        if ($maxlength < 1 || $maxlength > 1333) {
            $errors['configdata[maxlength]'] = get_string('errorconfigmaxlen', 'customfield_textformat');
        }

        $displaysize = (int)$data['configdata']['displaysize'];
        if ($displaysize < 1 || $displaysize > 200) {
            $errors['configdata[displaysize]'] = get_string('errorconfigdisplaysize', 'customfield_textformat');
        }

        if (isset($data['configdata']['link'])) {
            $link = $data['configdata']['link'];
            if (strlen($link)) {
                require_once($CFG->dirroot . '/lib/validateurlsyntax.php');
                if (strpos($link, '$$') === false) {
                    $errors['configdata[link]'] = get_string('errorconfiglinkplaceholder', 'customfield_textformat');
                } else if (!validateUrlSyntax(str_replace('$$', 'XYZ', $link), 's+H?S?F-E-u-P-a?I?p?f?q?r?')) {
                    // This validation is more strict than PARAM_URL - it requires the protocol and it must be either http or https.
                    $errors['configdata[link]'] = get_string('errorconfiglinksyntax', 'customfield_textformat');
                }
            }
        }

        return $errors;
    }

    /**
     * Does this custom field type support being used as part of the block_myoverview
     * custom field grouping?
     * @return bool
     */
    public function supports_course_grouping(): bool {
        return true;
    }

    /**
     * If this field supports course grouping, then this function needs overriding to
     * return the formatted values for this.
     * @param array $values the used values that need formatting
     * @return array
     */
    public function course_grouping_format_values($values): array {
        $ret = [];
        foreach ($values as $value) {
            $ret[$value] = format_string($value);
        }
        $ret[BLOCK_MYOVERVIEW_CUSTOMFIELD_EMPTY] = get_string('nocustomvalue', 'block_myoverview',
            $this->get_formatted_name());
        return $ret;
    }
}
