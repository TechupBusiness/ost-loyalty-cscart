<?php

namespace Techup\Helper;

use Tygh\Settings;
use Tygh\Registry;

class SettingsHelper extends Singleton {

	private $settings;
	private $languages;

	public function __construct() {
		$this->settings = Settings::instance();
	}
	
	/**
	 * Checks if option exists
	 *
	 * @param $addOnId string
	 * @param $optionName string
	 *
	 * @return bool
	 */
	public function hasOption($addOnId, $optionName) {
		return $this->settings->isExists($optionName, $addOnId);
	}

	/**
	 * Creates or updates an option (translation should already exist in language file)
	 *
	 * @param $addOnId int
	 * @param $tabName string
	 * @param $name string
	 * @param $value string
	 * @param $position int
	 * @param $type string
	 * @param $variants string[]
	 * @param $handler string
	 *
	 * @return integer
	 */
	public function updateOption( $addOnId, $tabName, $name, $value, $position = null, $type = null, $variants = array(), $handler = '' ) {

		$section = $this->settings->getSectionByName($addOnId, Settings::ADDON_SECTION, false);
		$section_id = $section['section_id'];
		$tab_section_id = (int) db_get_field('SELECT section_id FROM ?:settings_sections WHERE `parent_id`=?i AND `name`=?s', $section_id, $tabName);

		$settings_data = [
			'edition_type' => 'ROOT',
			'section_id' => $section_id,
			'section_tab_id' => $tab_section_id,
			'value' => $value,
			'is_global' => 'N',
			'handler' => $handler,
		];

		if($position!=null) $settings_data['position'] = $position;
		if($name!=null) $settings_data['name'] = $name;
		if($type!=null) $settings_data['type'] = $type;

		$object_id = $this->settings->getId($name, $addOnId);
		if($object_id>0) {
			$settings_data['object_id'] = $object_id;
		}
		$updateResult = $this->settings->update($settings_data); // does not update value!
		if(empty($object_id)) {
			$object_id = $updateResult;
		}

		// Update value
		$this->settings->updateValueById($object_id, $value );

		// save variants
		$variantIds = [];
		$pos = 10;
		foreach($variants as $v) {
			$variant_data = [
				'object_id' => $object_id,
				'name' => $v,
				'position' => $pos,
			];
			$pos += 10;
			$variantId = $this->settings->updateVariant($variant_data);
			$variantIds[$v] = $variantId;
		}

		// Save languages from PO file
		if(empty($this->languages[$addOnId])) {
			$this->languages[ $addOnId ] = LanguagesHelper::getInstance()->read( $addOnId );
		}
		$languages = $this->languages[ $addOnId ];

		foreach($languages as $lang_iso=>$lang_data) {

			if(!empty($languages[$lang_iso]['SettingsOptions'][$name])) {
				$description_data = [
					'object_id' => $object_id,
					'object_type' => Settings::SETTING_DESCRIPTION,
					'lang_code' => $lang_iso,
					'value' => $languages[$lang_iso]['SettingsOptions'][$name],
					'tooltip' => $languages[$lang_iso]['SettingsTooltips'][$name] ?? '',
				];
				db_replace_into ('settings_descriptions', $description_data);
			}

			foreach($variantIds as $variantName=>$variantId) {
				if(!empty($languages[$lang_iso]['SettingsVariants'][$variantName])) {
					$description_data = [
						'object_id' => $variantId,
						'object_type' => Settings::VARIANT_DESCRIPTION,
						'lang_code' => $lang_iso,
						'value' => $languages[$lang_iso]['SettingsVariants'][$variantName],
					];
					db_replace_into ('settings_descriptions', $description_data);
				}
			}
		}

		return $object_id;
	}


	private function extractSettingDescriptions($languages) {

	}

	public function removeOption($addOnId, $optionName) {
		return $this->settings->remove($optionName, $addOnId);
	}

	/**
	 * Get option values
	 *
	 * @param $addOnId string
	 * @param $optionName string
	 *
	 * @return bool|mixed
	 */
	public function getOptionValue($addOnId, $optionName) {
		return $this->settings->getValue($optionName, $addOnId);
	}

	public function createTab() {
		//$section_id = $this->settings->getSectionByName($addOnId, Settings::ADDON_SECTION, false);
		//$tab_section_id = db_get_field( db_query("INSERT INTO ?:settings_sections (`parent_id`, `edition_type`, `name`, `position`, `type`) VALUES ('$section_id', 'ROOT', 'general', '0', 'TAB')");

	}

	/**
	 * Clears cs-cart cache to reload languages etc.
	 */
	public function clearCache() {
		Registry::cleanup();
	}

	/**
	 * Unpacks setting value
	 * @see copy of private \Tygh\Settings->_unserialize
	 *
	 * @param  mixed $value       Setting value
	 * @param  bool  $force_parse
	 * @return mixed Unpacked value
	 */
	public function unpackSettingStringToArray($value, $force_parse = false)
	{
		if (strpos($value, '#M#') === 0) {
			parse_str(str_replace('#M#', '', $value), $value);
		} elseif ($force_parse) {
			parse_str($value, $value);
		}

		return $value;
	}

	/**
	 * Packs setting value
	 * @see copy of private \Tygh\Settings->_serialize
	 *
	 * @param  mixed $value Setting value
	 * @return mixed Packed value
	 */
	public function packSettingArrayToString($value)
	{
		if (is_array($value)) {
			$value = '#M#' . implode('=Y&', $value) . '=Y';
		}

		return $value;
	}

	/**
	 * Returns array of types for addons setting
	 * @see copy of private \Tygh\AXmlScheme->_getTypes
	 *
	 * @return array
	 */
	public function getTypes()
	{
		return array (
			'input' => 'I',
			'textarea' => 'T',
			'radiogroup' => 'R',
			'selectbox' => 'S',
			'password' => 'P',
			'checkbox' => 'C',
			'multiple select' => 'M',
			'multiple checkboxes' => 'N',
			'countries list' => 'X',
			'states list' => 'W',
			'file' => 'F',
			'info' => 'O',
			'header' => 'H',
			'selectable_box' => 'B',
			'template' => 'E',
			'permanent_template' => 'Z',
			'hidden' => 'D'
		);
	}
}