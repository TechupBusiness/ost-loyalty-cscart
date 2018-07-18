<?php

namespace Techup\Helper;
use Tygh\Languages\Po;
use Tygh\Addons\SchemesManager;

class LanguagesHelper extends Singleton {

	/**
	 * Reads Addon PO file and return its content
	 * @param $addOnId
	 *
	 * @return array key[0]: ISO Codes, key[0][1]: Languages|SettingsOptions|SettingsTooltips|SettingsVariants
	 */
	public function read($addOnId) {

		$languages = [];

		$addon_scheme = SchemesManager::getScheme($addOnId);

		foreach ($addon_scheme->getLanguages() as $lang_code => $_v) {
			$lang_code = strtolower($lang_code);
			$path = $addon_scheme->getPoPath($lang_code);
			if (!empty($path)) {
				$lang_data = Po::getValues($path);

				foreach($lang_data as $key=>$l) {
					$parts = explode('::', $key);
					switch($parts[0]) {
						case 'Languages':
							$languages[$lang_code][$parts[0]][$l['id']] = $l['msgstr'][0];
							break;
						case 'SettingsOptions':
						case 'SettingsTooltips':
						case 'SettingsVariants':
							$languages[$lang_code][$parts[0]][$l['id']] = $l['msgstr'][0];
							break;
					}
				}
			}
		}

		return $languages;
	}




}