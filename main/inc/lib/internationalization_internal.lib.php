<?php
/* For licensing terms, see /license.txt */
/**
 * File: internationalization_internal.lib.php
 * Main API extension library for Chamilo 1.8.7 LMS,
 * contains functions for internal use only.
 * License: GNU General Public License Version 3 (Free Software Foundation)
 * @author Ivan Tcholakov, <ivantcholakov@gmail.com>, 2009, 2010
 * @author More authors, mentioned in the correpsonding fragments of this source
 *
 * Note: All functions and data structures here are not to be used directly.
 * See the file internationalization.lib.php which contains the "public" API.
 * @package chamilo.library
 */
/**
 * Global variables used by some callback functions
 */
$_api_encoding = null;
$_api_collator = null;
/**
 * This function returns an array of those languages that can use Latin 1 encoding.
 * Appendix to "Language support"
 * @return array	The array of languages that can use Latin 1 encoding (ISO-8859-15, ISO-8859-1, WINDOWS-1252, ...).
 * Note: The returned language identificators are purified, without suffixes.
 */
function _api_get_latin1_compatible_languages() {
    static $latin1_languages;
    if (!isset($latin1_languages)) {
        $latin1_languages = array();
        $encodings = & _api_non_utf8_encodings();
        foreach ($encodings as $key => $value) {
            if (api_is_latin1($value[0])) {
                $latin1_languages[] = $key;
            }
        }
    }
    return $latin1_languages;
}


/**
 * Appendix to "Language recognition"
 * Based on the publication:
 * W. B. Cavnar and J. M. Trenkle. N-gram-based text categorization.
 * Proceedings of SDAIR-94, 3rd Annual Symposium on Document Analysis
 * and Information Retrieval, 1994.
 * @link http://citeseer.ist.psu.edu/cache/papers/cs/810/http:zSzzSzwww.info.unicaen.frzSz~giguetzSzclassifzSzcavnar_trenkle_ngram.pdf/n-gram-based-text.pdf
 */

/**
 * Generates statistical, based on n-grams language profile from the given text.
 * @param string $string				The input text. It should be UTF-8 encoded. Practically it should be at least 3000 characters long, 40000 characters size is for increased accuracy.
 * @param int $n_grams_max (optional)	The size of the array of the generated n-grams.
 * @param int $n_max (optional)			The limit if the number of characters that a n-gram may contain.
 * @return array						An array that contains cunstructed n-grams, sorted in reverse order by their frequences. Frequences are not stored in the array.
 */
function &_api_generate_n_grams(&$string, $encoding, $n_grams_max = 350, $n_max = 4) {
    if (empty($string)) {
        return array();
    }
    // We construct only lowercase n-grams if it is applicable for the given language.
    // Removing all puntuation and some other non-letter characters. Apostrophe characters stay.
    // Splitting the sample text into separate words.
    $words = preg_split('/_/u', preg_replace('/[\x00-\x1F\x20-\x26\x28-\x3E\?@\x5B-\x60{|}~\x7F]/u', '_', ' '.api_strtolower(api_utf8_encode($string, $encoding), 'UTF-8').' '), -1, PREG_SPLIT_NO_EMPTY);
    $prefix = '_'; // Beginning of a word.
    $suffix = str_repeat('_', $n_max); // End of a word. Only the last '_' stays.
    $n_grams = array(); // The array that will contain the constructed n-grams.
    foreach ($words as $word) {
        $k = api_strlen($word, 'UTF-8') + 1;
        $word = $prefix.$word.$suffix;
        for ($n = 1; $n <= $n_max; $n++) {
            for ($i = 0; $i < $k; $i++) {
                $n_gram = api_utf8_decode(api_substr($word, $i, $n, 'UTF-8'), $encoding);
                if (isset($n_grams[$n_gram])) {
                    $n_grams[$n_gram]++;
                } else {
                    $n_grams[$n_gram] = 1;
                }
            }
        }
    }
    // Sorting the n-grams in reverse order by their frequences.
    arsort($n_grams);
    // Reduction the number of n-grams.
    return array_keys(array_slice($n_grams, 0, $n_grams_max));
}

/**
 *
 * The value $max_delta = 80000 is good enough for speed and detection accuracy.
 * If you set the value of $max_delta too low, no language will be recognized.
 * $max_delta = 400 * 350 = 140000 is the best detection with lowest speed.
 */
function & _api_compare_n_grams(&$n_grams, $encoding, $max_delta = LANGUAGE_DETECT_MAX_DELTA) {
    static $language_profiles;
    if (!isset($language_profiles)) {
        // Reading the language profile files from the internationalization database.
        $exceptions = array('.', '..', 'CVS', '.htaccess', '.svn', '_svn', 'index.html');
        $path = str_replace("\\", '/', dirname(__FILE__).'/internationalization_database/language_detection/language_profiles/');
        $non_utf8_encodings = & _api_non_utf8_encodings();
        if (is_dir($path)) {
            if ($handle = @opendir($path)) {
                while (($dir_entry = @readdir($handle)) !== false) {
                    if (api_in_array_nocase($dir_entry, $exceptions)) continue;
                    if (strpos($dir_entry, '.txt') === false) continue;
                    $dir_entry_full_path = $path .'/'. $dir_entry;
                    if (@filetype($dir_entry_full_path) != 'dir') {
                        if (false !== $data = @file_get_contents($dir_entry_full_path)) {
                            $language = basename($dir_entry_full_path, '.txt');
                            $encodings = array('UTF-8');
                            if (!empty($non_utf8_encodings[$language])) {
                                $encodings = array_merge($encodings, $non_utf8_encodings[$language]);
                            }
                            foreach ($encodings as $enc) {
                                $data_enc = api_utf8_decode($data, $enc);
                                if (empty($data_enc)) {
                                    continue;
                                }
                                $key = $language.':'.$enc;
                                $language_profiles[$key]['data'] = array_flip(explode("\n", $data_enc));
                                $language_profiles[$key]['language'] = $language;
                                $language_profiles[$key]['encoding'] = $enc;
                            }
                        }
                    }
                }
            }
        }
        @closedir($handle);
        ksort($language_profiles);
    }
    if (!is_array($n_grams) || empty($n_grams)) {
        return array();
    }
    // Comparison between the input n-grams and the lanuage profiles.
    foreach ($language_profiles as $key => &$language_profile) {
        if (!api_is_language_supported($language_profile['language']) || !api_equal_encodings($encoding, $language_profile['encoding'])) {
            continue;
        }
        $delta = 0; // This is a summary measurment for matching between the input text and the current language profile.
        // Searching each n-gram from the input text into the language profile.
        foreach ($n_grams as $rank => &$n_gram) {
            if (isset($language_profile['data'][$n_gram])) {
                // The n-gram has been found, the difference between places in both
                // arrays is calculated (so called delta-points are adopted for
                // measuring distances between n-gram ranks.
                $delta += abs($rank - $language_profile['data'][$n_gram]);
            } else {
                // The n-gram has not been found in the profile. We add then
                // a large enough "distance" in delta-points.
                $delta += 400;
            }
            // Abort: This language already differs too much.
            if ($delta > $max_delta) {
                break;
            }
        }
        // Include only non-aborted languages in result array.
        if ($delta < ($max_delta - 400)) {
            $result[$key] = $delta;
        }
    }
    if (!isset($result)) {
        return array();
    }
    asort($result);
    return $result;
}


/**
 * Appendix to "Date and time formats"
 */

/**
 * Returns an array of translated week days and months, short and normal names.
 * @param string $language (optional)	Language indentificator. If it is omited, the current interface language is assumed.
 * @return array						Returns a multidimensional array with translated week days and months.
 */
function &_api_get_day_month_names($language = null) {
    static $date_parts = array();
    if (empty($language)) {
        $language = api_get_interface_language();
    }
    if (!isset($date_parts[$language])) {
        $week_day = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
        $month = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
        for ($i = 0; $i < 7; $i++) {
            $date_parts[$language]['days_short'][] = get_lang($week_day[$i].'Short', '', $language);
            $date_parts[$language]['days_long'][] = get_lang($week_day[$i].'Long', '', $language);
        }
        for ($i = 0; $i < 12; $i++) {
            $date_parts[$language]['months_short'][] = get_lang($month[$i].'Short', '', $language);
            $date_parts[$language]['months_long'][] = get_lang($month[$i].'Long', '', $language);
        }
    }
    return $date_parts[$language];
}


/**
 * Appendix to "Name order conventions"
 */

/**
 * Returns returns person name convention for a given language.
 * @param string $language	The input language.
 * @param string $type		The type of the requested convention. It may be 'format' for name order convention or 'sort_by' for name sorting convention.
 * @return mixed			Depending of the requested type, the returned result may be string or boolean; null is returned on error;
 */
function _api_get_person_name_convention($language, $type) {
    static $conventions;
    $language = api_purify_language_id($language);
    if (!isset($conventions)) {
        $file = dirname(__FILE__).'/internationalization_database/name_order_conventions.php';
        if (file_exists($file)) {
            $conventions = include ($file);
        } else {
            $conventions = array('english' => array('format' => 'title first_name last_name', 'sort_by' => 'first_name'));
        }
        $search1 = array('FIRST_NAME', 'LAST_NAME', 'TITLE');
        $replacement1 = array('%F', '%L', '%T');
        $search2 = array('first_name', 'last_name', 'title');
        $replacement2 = array('%f', '%l', '%t');
        foreach (array_keys($conventions) as $key) {
            $conventions[$key]['format'] = str_replace($search1, $replacement1, $conventions[$key]['format']);
            $conventions[$key]['format'] = _api_validate_person_name_format(_api_clean_person_name(str_replace('%', ' %', str_ireplace($search2, $replacement2, $conventions[$key]['format']))));
            $conventions[$key]['sort_by'] = strtolower($conventions[$key]['sort_by']) != 'last_name' ? true : false;
        }
    }
    switch ($type) {
        case 'format':
            return is_string($conventions[$language]['format']) ? $conventions[$language]['format'] : '%t %f %l';
        case 'sort_by':
            return is_bool($conventions[$language]['sort_by']) ? $conventions[$language]['sort_by'] : true;
    }
    return null;
}

/**
 * Replaces non-valid formats for person names with the default (English) format.
 * @param string $format	The input format to be verified.
 * @return bool				Returns the same format if is is valid, otherwise returns a valid English format.
 */
function _api_validate_person_name_format($format) {
    if (empty($format) || stripos($format, '%f') === false || stripos($format, '%l') === false) {
        return '%t %f %l';
    }
    return $format;
}

/**
 * Removes leading, trailing and duplicate whitespace and/or commas in a full person name.
 * Cleaning is needed for the cases when not all parts of the name are available or when the name is constructed using a "dirty" pattern.
 * @param string $person_name	The input person name.
 * @return string				Returns cleaned person name.
 */
function _api_clean_person_name($person_name) {
    return preg_replace(array('/\s+/', '/, ,/', '/,+/', '/^[ ,]/', '/[ ,]$/'), array(' ', ', ', ',', '', ''), $person_name);
}


/**
 * Appendix to "Multibyte string conversion functions"
 */

/**
 * This is a php-implementation of a function that is similar to mb_convert_encoding() from mbstring extension.
 * The function converts a given string from one to another character encoding.
 * @param string $string					The string being converted.
 * @param string $to_encoding				The encoding that $string is being converted to.
 * @param string $from_encoding				The encoding that $string is being converted from.
 * @return string							Returns the converted string.
 */
function _api_convert_encoding(&$string, $to_encoding, $from_encoding) {
    $str = (string)$string;
    static $character_map = array();
    static $utf8_compatible = array('UTF-8', 'US-ASCII');
    if (empty($str)) {
        return $str;
    }
    $to_encoding = api_refine_encoding_id($to_encoding);
    $from_encoding = api_refine_encoding_id($from_encoding);
    if (api_equal_encodings($to_encoding, $from_encoding)) {
        return $str;
    }
    if ($to_encoding == 'HTML-ENTITIES') {
        return api_htmlentities($str, ENT_QUOTES, $from_encoding);
    }
    if ($from_encoding == 'HTML-ENTITIES') {
        return api_html_entity_decode($str, ENT_QUOTES, $to_encoding);
    }
    $to = _api_get_character_map_name($to_encoding);
    $from = _api_get_character_map_name($from_encoding);
    if (empty($to) || empty($from) || $to == $from || (in_array($to, $utf8_compatible) && in_array($from, $utf8_compatible))) {
        return $str;
    }
    if (!isset($character_map[$to])) {
        $character_map[$to] = &_api_parse_character_map($to);
    }
    if ($character_map[$to] === false) {
        return $str;
    }
    if (!isset($character_map[$from])) {
        $character_map[$from] = &_api_parse_character_map($from);
    }
    if ($character_map[$from] === false) {
        return $str;
    }
    if ($from != 'UTF-8') {
        $len = api_byte_count($str);
        $codepoints = array();
        for ($i = 0; $i < $len; $i++) {
            $ord = ord($str[$i]);
            if ($ord > 127) {
                if (isset($character_map[$from]['local'][$ord])) {
                    $codepoints[] = $character_map[$from]['local'][$ord];
                } else {
                    $codepoints[] = 0xFFFD; // U+FFFD REPLACEMENT CHARACTER is the general substitute character in the Unicode Standard.
                }
            } else {
                $codepoints[] = $ord;
            }
        }
    } else {
        $codepoints = _api_utf8_to_unicode($str);
    }
    if ($to != 'UTF-8') {
        foreach ($codepoints as $i => &$codepoint) {
            if ($codepoint > 127) {
                if (isset($character_map[$to]['unicode'][$codepoint])) {
                    $codepoint = chr($character_map[$to]['unicode'][$codepoint]);
                } else {
                    $codepoint = '?'; // Unknown character.
                }
            } else {
                $codepoint = chr($codepoint);
            }
        }
        $str = implode($codepoints);
    } else {
        $str = _api_utf8_from_unicode($codepoints);
    }
    return $str;
}

/**
 * This function determines the name of corresponding to a given encoding conversion table.
 * It is able to deal with some aliases of the encoding.
 * @param string $encoding		The given encoding identificator, for example 'WINDOWS-1252'.
 * @return string				Returns the name of the corresponding conversion table, for the same example - 'CP1252'.
 */
function _api_get_character_map_name($encoding) {
    static $character_map_selector;
    if (!isset($character_map_selector)) {
        $file = dirname(__FILE__).'/internationalization_database/conversion/character_map_selector.php';
        if (file_exists($file)) {
            $character_map_selector = include ($file);
        } else {
            $character_map_selector = array();
        }
    }
    return isset($character_map_selector[$encoding]) ? $character_map_selector[$encoding] : '';
}

/**
 * This function parses a given conversion table (a text file) and creates in the memory
 * two tables for conversion - character set from/to Unicode codepoints.
 * @param string $name		The name of the thext file that contains the conversion table, for example 'CP1252' (file CP1252.TXT will be parsed).
 * @return array			Returns an array that contains forward and reverse tables (from/to Unicode).
 */
function &_api_parse_character_map($name) {
    $result = array();
    $file = dirname(__FILE__).'/internationalization_database/conversion/' . $name . '.TXT';
    if (file_exists($file)) {
        $text = @file_get_contents($file);
        if ($text !== false) {
            $text = explode(chr(10), $text);
            foreach ($text as $line) {
                if (empty($line)) {
                    continue;
                }
                if (!empty($line) && trim($line) && $line[0] != '#') {
                    $matches = array();
                    preg_match('/[[:space:]]*0x([[:alnum:]]*)[[:space:]]+0x([[:alnum:]]*)[[:space:]]+/', $line, $matches);
                    $ord = hexdec(trim($matches[1]));
                    if ($ord > 127) {
                        $codepoint =  hexdec(trim($matches[2]));
                        $result['local'][$ord] = $codepoint;
                        $result['unicode'][$codepoint] = $ord;
                    }
                }
            }
        } else {
            return false ;
        }
    } else {
        return false;
    }
    return $result;
}

/**
 * Takes an UTF-8 string and returns an array of integer values representing the Unicode characters.
 * Astral planes are supported ie. the ints in the output can be > 0xFFFF. Occurrances of the BOM are ignored.
 * Surrogates are not allowed.
 * @param string $string				The UTF-8 encoded string.
 * @return array						Returns an array of unicode code points.
 * @author Henri Sivonen, mailto:hsivonen@iki.fi
 * @link http://hsivonen.iki.fi/php-utf8/
 * @author Ivan Tcholakov, August 2009, adaptation for the Dokeos LMS.
 */
function _api_utf8_to_unicode(&$string) {
    $str = (string)$string;
    $state = 0;			// cached expected number of octets after the current octet
                        // until the beginning of the next UTF8 character sequence
    $codepoint  = 0;	// cached Unicode character
    $bytes = 1;			// cached expected number of octets in the current sequence
    $result = array();
    $len = api_byte_count($str);
    for ($i = 0; $i < $len; $i++) {
        $byte = ord($str[$i]);
        if ($state == 0) {
            // When state is zero we expect either a US-ASCII character or a multi-octet sequence.
            if (0 == (0x80 & ($byte))) {
                // US-ASCII, pass straight through.
                $result[] = $byte;
                $bytes = 1;
            } else if (0xC0 == (0xE0 & ($byte))) {
                // First octet of 2 octet sequence
                $codepoint = ($byte);
                $codepoint = ($codepoint & 0x1F) << 6;
                $state = 1;
                $bytes = 2;
            } else if (0xE0 == (0xF0 & ($byte))) {
                // First octet of 3 octet sequence
                $codepoint = ($byte);
                $codepoint = ($codepoint & 0x0F) << 12;
                $state = 2;
                $bytes = 3;
            } else if (0xF0 == (0xF8 & ($byte))) {
                // First octet of 4 octet sequence
                $codepoint = ($byte);
                $codepoint = ($codepoint & 0x07) << 18;
                $state = 3;
                $bytes = 4;
            } else if (0xF8 == (0xFC & ($byte))) {
                // First octet of 5 octet sequence.
                // This is illegal because the encoded codepoint must be either
                // (a) not the shortest form or
                // (b) outside the Unicode range of 0-0x10FFFF.
                // Rather than trying to resynchronize, we will carry on until the end
                // of the sequence and let the later error handling code catch it.
                $codepoint = ($byte);
                $codepoint = ($codepoint & 0x03) << 24;
                $state = 4;
                $bytes = 5;
            } else if (0xFC == (0xFE & ($byte))) {
                // First octet of 6 octet sequence, see comments for 5 octet sequence.
                $codepoint = ($byte);
                $codepoint = ($codepoint & 1) << 30;
                $state = 5;
                $bytes = 6;
            } else {
                // Current octet is neither in the US-ASCII range nor a legal first octet of a multi-octet sequence.
                $state = 0;
                $codepoint = 0;
                $bytes = 1;
                $result[] = 0xFFFD; // U+FFFD REPLACEMENT CHARACTER is the general substitute character in the Unicode Standard.
                continue ;
            }
        } else {
            // When state is non-zero, we expect a continuation of the multi-octet sequence
            if (0x80 == (0xC0 & ($byte))) {
                // Legal continuation.
                $shift = ($state - 1) * 6;
                $tmp = $byte;
                $tmp = ($tmp & 0x0000003F) << $shift;
                $codepoint |= $tmp;
                // End of the multi-octet sequence. $codepoint now contains the final Unicode codepoint to be output
                if (0 == --$state) {
                    // Check for illegal sequences and codepoints.
                    // From Unicode 3.1, non-shortest form is illegal
                    if (((2 == $bytes) && ($codepoint < 0x0080)) ||
                        ((3 == $bytes) && ($codepoint < 0x0800)) ||
                        ((4 == $bytes) && ($codepoint < 0x10000)) ||
                        (4 < $bytes) ||
                        // From Unicode 3.2, surrogate characters are illegal
                        (($codepoint & 0xFFFFF800) == 0xD800) ||
                        // Codepoints outside the Unicode range are illegal
                        ($codepoint > 0x10FFFF)) {
                        $state = 0;
                        $codepoint = 0;
                        $bytes = 1;
                        $result[] = 0xFFFD;
                        continue ;
                    }
                    if (0xFEFF != $codepoint) {
                        // BOM is legal but we don't want to output it
                        $result[] = $codepoint;
                    }
                    // Initialize UTF8 cache
                    $state = 0;
                    $codepoint = 0;
                    $bytes = 1;
                }
            } else {
                // ((0xC0 & (*in) != 0x80) && (state != 0))
                // Incomplete multi-octet sequence.
                $state = 0;
                $codepoint = 0;
                $bytes = 1;
                $result[] = 0xFFFD;
            }
        }
    }
    return $result;
}

/**
 * Takes an array of Unicode codepoints and returns a UTF-8 string.
 * @param array $codepoints				An array of Unicode codepoints representing a string.
 * @return string						Returns a UTF-8 string constructed using the given codepoints.
 */
function _api_utf8_from_unicode($codepoints) {
    return implode(array_map('_api_utf8_chr', $codepoints));
}

/**
 * Takes a codepoint and returns its correspondent UTF-8 encoded character.
 * Astral planes are supported, ie the intger input can be > 0xFFFF. Occurrances of the BOM are ignored.
 * Surrogates are not allowed.
 * @param int $codepoint				The Unicode codepoint.
 * @return string						Returns the corresponding UTF-8 character.
 * @author Henri Sivonen, mailto:hsivonen@iki.fi
 * @link http://hsivonen.iki.fi/php-utf8/
 * @author Ivan Tcholakov, 2009, modifications for the Dokeos LMS.
 * @see _api_utf8_from_unicode()
 * This is a UTF-8 aware version of the function chr().
 * @link http://php.net/manual/en/function.chr.php
 */
function _api_utf8_chr($codepoint) {
    // ASCII range (including control chars)
    if ( ($codepoint >= 0) && ($codepoint <= 0x007f) ) {
        $result = chr($codepoint);
    // 2 byte sequence
    } else if ($codepoint <= 0x07ff) {
        $result = chr(0xc0 | ($codepoint >> 6)) . chr(0x80 | ($codepoint & 0x003f));
    // Byte order mark (skip)
    } else if($codepoint == 0xFEFF) {
        // nop -- zap the BOM
        $result = '';
    // Test for illegal surrogates
    } else if ($codepoint >= 0xD800 && $codepoint <= 0xDFFF) {
        // found a surrogate
        $result = _api_utf8_chr(0xFFFD); // U+FFFD REPLACEMENT CHARACTER is the general substitute character in the Unicode Standard.
    // 3 byte sequence
    } else if ($codepoint <= 0xffff) {
        $result = chr(0xe0 | ($codepoint >> 12)) . chr(0x80 | (($codepoint >> 6) & 0x003f)) . chr(0x80 | ($codepoint & 0x003f));
    // 4 byte sequence
    } else if ($codepoint <= 0x10ffff) {
        $result = chr(0xf0 | ($codepoint >> 18)) . chr(0x80 | (($codepoint >> 12) & 0x3f)) . chr(0x80 | (($codepoint >> 6) & 0x3f)) . chr(0x80 | ($codepoint & 0x3f));
    } else {
         // out of range
        $result = _api_utf8_chr(0xFFFD);
    }
    return $result;
}

/**
 * Takes the first UTF-8 character in a string and returns its Unicode codepoint.
 * @param string $utf8_character	The UTF-8 encoded character.
 * @return int						Returns: the codepoint; or 0xFFFD (unknown character) when the input string is empty.
 * This is a UTF-8 aware version of the function ord().
 * @link http://php.net/manual/en/function.ord.php
 * Note about a difference with the original funtion ord(): ord('') returns 0.
 */
function _api_utf8_ord($utf8_character) {
    if ($utf8_character == '') {
        return 0xFFFD;
    }
    $codepoints = _api_utf8_to_unicode($utf8_character);
    return $codepoints[0];
}

/**
 * Makes a html-entity from Unicode codepoint.
 * @param int $codepoint			The Unicode codepoint.
 * @return string					Returns the corresponding html-entity; or ASCII character if $codepoint < 128.
 */
function _api_html_entity_from_unicode($codepoint) {
    if ($codepoint < 128) {
        return chr($codepoint);
    }
    return '&#'.$codepoint.';';
}


/**
 * Appendix to "Common multibyte string functions"
 */

/**
 * The following function reads case folding properties about a given character from a file-based "database".
 * @param int $codepoint			The Unicode codepoint that represents a caharacter.
 * @param string $type (optional)	The type of initial case to be altered: 'lower' (default) or 'upper'.
 * @return array					Returns an array with properties used to change case of the character.
 */
function &_api_utf8_get_letter_case_properties($codepoint, $type = 'lower') {
    static $config = array();
    static $range = array();
    if (!isset($range[$codepoint])) {
        if ($codepoint > 128 && $codepoint < 256)  {
            $range[$codepoint] = '0080_00ff'; // Latin-1 Supplement
        } elseif ($codepoint < 384) {
            $range[$codepoint] = '0100_017f'; // Latin Extended-A
        } elseif ($codepoint < 592) {
            $range[$codepoint] = '0180_024F'; // Latin Extended-B
        } elseif ($codepoint < 688) {
            $range[$codepoint] = '0250_02af'; // IPA Extensions
        } elseif ($codepoint >= 880 && $codepoint < 1024) {
            $range[$codepoint] = '0370_03ff'; // Greek and Coptic
        } elseif ($codepoint < 1280) {
            $range[$codepoint] = '0400_04ff'; // Cyrillic
        } elseif ($codepoint < 1328) {
            $range[$codepoint] = '0500_052f'; // Cyrillic Supplement
        } elseif ($codepoint < 1424) {
            $range[$codepoint] = '0530_058f'; // Armenian
        } elseif ($codepoint >= 7680 && $codepoint < 7936) {
            $range[$codepoint] = '1e00_1eff'; // Latin Extended Additional
        } elseif ($codepoint < 8192) {
            $range[$codepoint] = '1f00_1fff'; // Greek Extended
        } elseif ($codepoint >= 8448 && $codepoint < 8528) {
            $range[$codepoint] = '2100_214f'; // Letterlike Symbols
        } elseif ($codepoint < 8592) {
            $range[$codepoint] = '2150_218f'; // Number Forms
        } elseif ($codepoint >= 9312 && $codepoint < 9472) {
            $range[$codepoint] = '2460_24ff'; // Enclosed Alphanumerics
        } elseif ($codepoint >= 11264 && $codepoint < 11360) {
            $range[$codepoint] = '2c00_2c5f'; // Glagolitic
        } elseif ($codepoint < 11392) {
            $range[$codepoint] = '2c60_2c7f'; // Latin Extended-C
        } elseif ($codepoint < 11520) {
            $range[$codepoint] = '2c80_2cff'; // Coptic
        } elseif ($codepoint >= 65280 && $codepoint < 65520) {
            $range[$codepoint] = 'ff00_ffef'; // Halfwidth and Fullwidth Forms
        } else {
            $range[$codepoint] = false;
        }
        if ($range[$codepoint] === false) {
            return null;
        }
        if (!isset($config[$range[$codepoint]])) {
            $file = dirname(__FILE__).'/internationalization_database/casefolding/' . $range[$codepoint] . '.php';
            if (file_exists($file)) {
                include $file;
            }
        }
    }
    if ($range[$codepoint] === false || !isset($config[$range[$codepoint]])) {
        return null;
    }
    $result = array();
    $count = count($config[$range[$codepoint]]);
    for ($i = 0; $i < $count; $i++) {
        if ($type === 'lower' && $config[$range[$codepoint]][$i][$type][0] === $codepoint) {
            $result[] = $config[$range[$codepoint]][$i];
        } elseif ($type === 'upper' && $config[$range[$codepoint]][$i][$type] === $codepoint) {
            $result[] = $config[$range[$codepoint]][$i];
        }
    }
    return $result;
}

/**
 * A callback for serving the function api_ucwords().
 * @param array $matches	Input array of matches corresponding to a single word
 * @return string			Returns a with first char of the word in uppercase
 */
function _api_utf8_ucwords_callback($matches) {
    return $matches[2] . api_ucfirst(ltrim($matches[0]), 'UTF-8');
}


/**
 * Appendix to "Common sting operations with arrays"
 */

/**
 * This callback function converts from UTF-8 to other encoding. It works with strings or arrays of strings.
 * @param mixed $variable	The variable to be converted, a string or an array.
 * @return mixed			Returns the converted form UTF-8 $variable with the same type, string or array.
 */
function _api_array_utf8_decode($variable) {
    global $_api_encoding;
    if (is_array($variable)) {
        return array_map('_api_array_utf8_decode', $variable);
    }
    if (is_string($variable)) {
        return api_utf8_decode($variable, $_api_encoding);
    }
    return $variable;
}


/**
 * Appendix to "String comparison"
 */

/**
 * Returns an instance of Collator class (ICU) created for a specified language.
 * @param string $language (optional)	Language indentificator: 'english', 'french' ... If it is omited, the current interface language is assumed.
 * @return object						Returns a instance of Collator class that is suitable for common string comparisons.
 */
function _api_get_collator($language = null) {
    static $collator = array();
    if (empty($language)) {
        $language = api_get_interface_language();
    }
    if (!isset($collator[$language])) {
        $locale = _api_get_locale_from_language($language);
        $collator[$language] = collator_create($locale);
        if (is_object($collator[$language])) {
            collator_set_attribute($collator[$language], Collator::CASE_FIRST, Collator::UPPER_FIRST);
        }
    }
    return $collator[$language];
}

/**
 * Returns an instance of Collator class (ICU) created for a specified language. This collator treats substrings of digits as numbers.
 * @param string $language (optional)	Language indentificator. If it is omited, the current interface language is assumed.
 * @return object						Returns a instance of Collator class that is suitable for alpha-numerical comparisons.
 */
function _api_get_alpha_numerical_collator($language = null) {
    static $collator = array();
    if (empty($language)) {
        $language = api_get_interface_language();
    }
    if (!isset($collator[$language])) {
        $locale = _api_get_locale_from_language($language);
        $collator[$language] = collator_create($locale);
        if (is_object($collator[$language])) {
            collator_set_attribute($collator[$language], Collator::CASE_FIRST, Collator::UPPER_FIRST);
            collator_set_attribute($collator[$language], Collator::NUMERIC_COLLATION, Collator::ON);
        }
    }
    return $collator[$language];
}

/**
 * A string comparison callback function for sorting.
 * @param string $string1		The first string.
 * @param string $string2		The second string.
 * @return int					Returns 0 if $string1 = $string2 or if there is an error; 1 if $string1 > $string2; -1 if $string1 < $string2.
 */
function _api_cmp($string1, $string2) {
    global $_api_collator, $_api_encoding;
    $result = collator_compare($_api_collator, api_utf8_encode($string1, $_api_encoding), api_utf8_encode($string2, $_api_encoding));
    return $result === false ? 0 : $result;
}

/**
 * A reverse string comparison callback function for sorting.
 * @param string $string1		The first string.
 * @param string $string2		The second string.
 * @return int					Returns 0 if $string1 = $string2 or if there is an error; 1 if $string1 < $string2; -1 if $string1 > $string2.
 */
function _api_rcmp($string1, $string2) {
    global $_api_collator, $_api_encoding;
    $result = collator_compare($_api_collator, api_utf8_encode($string2, $_api_encoding), api_utf8_encode($string1, $_api_encoding));
    return $result === false ? 0 : $result;
}

/**
 * A case-insensitive string comparison callback function for sorting.
 * @param string $string1		The first string.
 * @param string $string2		The second string.
 * @return int					Returns 0 if $string1 = $string2 or if there is an error; 1 if $string1 > $string2; -1 if $string1 < $string2.
 */
function _api_casecmp($string1, $string2) {
    global $_api_collator, $_api_encoding;
    $result = collator_compare($_api_collator, api_strtolower(api_utf8_encode($string1, $_api_encoding), 'UTF-8'), api_strtolower(api_utf8_encode($string2, $_api_encoding), 'UTF-8'));
    return $result === false ? 0 : $result;
}

/**
 * A reverse case-insensitive string comparison callback function for sorting.
 * @param string $string1		The first string.
 * @param string $string2		The second string.
 * @return int					Returns 0 if $string1 = $string2 or if there is an error; 1 if $string1 < $string2; -1 if $string1 > $string2.
 */
function _api_casercmp($string1, $string2) {
    global $_api_collator, $_api_encoding;
    $result = collator_compare($_api_collator, api_strtolower(api_utf8_encode($string2, $_api_encoding), 'UTF-8'), api_strtolower(api_utf8_encode($string1, $_api_encoding), 'UTF-8'));
    return $result === false ? 0 : $result;
}

/**
 * A reverse function from php-core function strnatcmp(), performs string comparison in reverse natural (alpha-numerical) order.
 * @param string $string1		The first string.
 * @param string $string2		The second string.
 * @return int					Returns 0 if $string1 = $string2; >0 if $string1 < $string2; <0 if $string1 > $string2.
 */
function _api_strnatrcmp($string1, $string2) {
    return strnatcmp($string2, $string1);
}

/**
 * A reverse function from php-core function strnatcasecmp(), performs string comparison in reverse case-insensitive natural (alpha-numerical) order.
 * @param string $string1		The first string.
 * @param string $string2		The second string.
 * @return int					Returns 0 if $string1 = $string2; >0 if $string1 < $string2; <0 if $string1 > $string2.
 */
function _api_strnatcasercmp($string1, $string2) {
    return strnatcasecmp($string2, $string1);
}

/**
 * A fuction that translates sorting flag constants from php core to correspondent constants from intl extension.
 * @param int $sort_flag (optional)		Sorting modifier flag as it is defined for php core. The default value is SORT_REGULAR.
 * @return int							Retturns the corresponding sorting modifier flag as it is defined in intl php-extension.
 */
function _api_get_collator_sort_flag($sort_flag = SORT_REGULAR) {
    switch ($sort_flag) {
        case SORT_STRING:
        case SORT_SORT_LOCALE_STRING:
            return Collator::SORT_STRING;
        case SORT_NUMERIC:
            return Collator::SORT_NUMERIC;
    }
    return Collator::SORT_REGULAR;
}


/**
 * ICU locales (accessible through intl extension).
 */

/**
 * Returns isocode (see api_get_language_isocode()) which is purified accordingly to
 * be used by the php intl extension (ICU library).
 * @param string $language (optional)	This is the name of the folder containing translations for the corresponding language.
 * If $language is omitted, interface language is assumed then.
 * @return string						The found language locale id or null on error. Examples: bg, en, pt_BR, ...
 */
function _api_get_locale_from_language($language = null) {
    static $locale = array();
    if (empty($language)) {
        $language = api_get_interface_language();
    }
    if (!isset($locale[$language])) {
        $locale[$language] = str_replace('-', '_', api_get_language_isocode($language));
    }
    return $locale[$language];
}

/**
 * Sets/gets the default internal value of the locale id (for the intl extension, ICU).
 * @param string $locale (optional)	The locale id to be set. When it is omitted, the function returns (gets, reads) the default internal value.
 * @return mixed						When the function sets the default value, it returns TRUE on success or FALSE on error. Otherwise the function returns as string the current default value.
 */
function _api_set_default_locale($locale = null) {
    static $default_locale = 'en';
    if (!empty($locale)) {
        $default_locale = $locale;
        if (INTL_INSTALLED) {
            return @locale_set_default($locale);
        }
        return true;
    } else {
        if (INTL_INSTALLED) {
            $default_locale = @locale_get_default();
        }
    }
    return $default_locale;
}

/**
 * Gets the default internal value of the locale id (for the intl extension, ICU).
 * @return string		Returns as string the current default value.
 */
function api_get_default_locale() {
    return _api_set_default_locale();
}


/**
 * Appendix to "Encoding management functions"
 */

/**
 * Returns a table with non-UTF-8 encodings for all system languages.
 * @return array		Returns an array in the form array('language1' => array('encoding1', encoding2', ...), ...)
 * Note: The function api_get_non_utf8_encoding() returns the first encoding from this array that is correspondent to the given language.
 */
function & _api_non_utf8_encodings() {
    static $encodings;
    if (!isset($encodings)) {
        $file = dirname(__FILE__).'/internationalization_database/non_utf8_encodings.php';
        if (file_exists($file)) {
            $encodings = include ($file);
        } else {
            $encodings = array('english' => array('ISO-8859-15'));
        }
    }
    return $encodings;
}

/**
 * Sets/Gets internal character encoding of the common string functions within the PHP mbstring extension.
 * @param string $encoding (optional)	When this parameter is given, the function sets the internal encoding.
 * @return string						When $encoding parameter is not given, the function returns the internal encoding.
 * Note: This function is used in the global initialization script for setting the internal encoding to the platform's character set.
 * @link http://php.net/manual/en/function.mb-internal-encoding
 */
function _api_mb_internal_encoding($encoding = null) {
    static $mb_internal_encoding = null;
    if (empty($encoding)) {
        if (is_null($mb_internal_encoding)) {
            if (MBSTRING_INSTALLED) {
                $mb_internal_encoding = @mb_internal_encoding();
            } else {
                $mb_internal_encoding = 'UTF-8';
            }
        }
        return $mb_internal_encoding;
    }
    $mb_internal_encoding = $encoding;
    if (_api_mb_supports($encoding)) {
        return @mb_internal_encoding($encoding);
    }
    return false;
}

/**
 * Sets/Gets internal character encoding of the regular expression functions (ereg-like) within the PHP mbstring extension.
 * @param string $encoding (optional)	When this parameter is given, the function sets the internal encoding.
 * @return string						When $encoding parameter is not given, the function returns the internal encoding.
 * Note: This function is used in the global initialization script for setting the internal encoding to the platform's character set.
 * @link http://php.net/manual/en/function.mb-regex-encoding
 */
function _api_mb_regex_encoding($encoding = null) {
    static $mb_regex_encoding = null;
    if (empty($encoding)) {
        if (is_null($mb_regex_encoding)) {
            if (MBSTRING_INSTALLED) {
                $mb_regex_encoding = @mb_regex_encoding();
            } else {
                $mb_regex_encoding = 'UTF-8';
            }
        }
        return $mb_regex_encoding;
    }
    $mb_regex_encoding = $encoding;
    if (_api_mb_supports($encoding)) {
        return @mb_regex_encoding($encoding);
    }
    return false;
}

/**
 * Retrieves specified internal encoding configuration variable within the PHP iconv extension.
 * @param string $type	The parameter $type could be: 'iconv_internal_encoding', 'iconv_input_encoding', or 'iconv_output_encoding'.
 * @return mixed		The function returns the requested encoding or FALSE on error.
 * @link http://php.net/manual/en/function.iconv-get-encoding
 */
function _api_iconv_get_encoding($type) {
    return _api_iconv_set_encoding($type);
}

/**
 * Sets specified internal encoding configuration variables within the PHP iconv extension.
 * @param string $type					The parameter $type could be: 'iconv_internal_encoding', 'iconv_input_encoding', or 'iconv_output_encoding'.
 * @param string $encoding (optional)	The desired encoding to be set.
 * @return bool							Returns TRUE on success, FALSE on error.
 * Note: This function is used in the global initialization script for setting these three internal encodings to the platform's character set.
 * @link http://php.net/manual/en/function.iconv-set-encoding
 */
function _api_iconv_set_encoding($type, $encoding = null) {
    static $iconv_internal_encoding = null;
    static $iconv_input_encoding = null;
    static $iconv_output_encoding = null;
    if (!ICONV_INSTALLED) {
        return false;
    }
    switch ($type) {
        case 'iconv_internal_encoding':
            if (empty($encoding)) {
                if (is_null($iconv_internal_encoding)) {
                    $iconv_internal_encoding = @iconv_get_encoding($type);
                }
                return $iconv_internal_encoding;
            }
            if (_api_iconv_supports($encoding)) {
                if(@iconv_set_encoding($type, $encoding)) {
                    $iconv_internal_encoding = $encoding;
                    return true;
                }
                return false;
            }
            return false;
        case 'iconv_input_encoding':
            if (empty($encoding)) {
                if (is_null($iconv_input_encoding)) {
                    $iconv_input_encoding = @iconv_get_encoding($type);
                }
                return $iconv_input_encoding;
            }
            if (_api_iconv_supports($encoding)) {
                if(@iconv_set_encoding($type, $encoding)) {
                    $iconv_input_encoding = $encoding;
                    return true;
                }
                return false;
            }
            return false;
        case 'iconv_output_encoding':
            if (empty($encoding)) {
                if (is_null($iconv_output_encoding)) {
                    $iconv_output_encoding = @iconv_get_encoding($type);
                }
                return $iconv_output_encoding;
            }
            if (_api_iconv_supports($encoding)) {
                if(@iconv_set_encoding($type, $encoding)) {
                    $iconv_output_encoding = $encoding;
                    return true;
                }
                return false;
            }
            return false;
    }
    return false;
}

/**
 * Ckecks whether a given encoding is known to define single-byte characters only.
 * The result might be not accurate for unknown by this library encodings. This is not fatal,
 * then the library picks up conversions plus Unicode related internal algorithms.
 * @param string $encoding		A given encoding identificator.
 * @return bool					TRUE if the encoding is known as single-byte (for ISO-8859-15, WINDOWS-1251, etc.), FALSE otherwise.
 */
function _api_is_single_byte_encoding($encoding) {
    static $checked = array();
    if (!isset($checked[$encoding])) {
        $character_map = _api_get_character_map_name(api_refine_encoding_id($encoding));
        $checked[$encoding] = (!empty($character_map)
            && !in_array($character_map, array('UTF-8', 'HTML-ENTITIES')));
    }
    return $checked[$encoding];
}

/**
 * Checks whether the specified encoding is supported by the PHP mbstring extension.
 * @param string $encoding	The specified encoding.
 * @return bool				Returns TRUE when the specified encoding is supported, FALSE othewise.
 */
function _api_mb_supports($encoding) {
    static $supported = array();
    if (!isset($supported[$encoding])) {
        if (MBSTRING_INSTALLED) {
            $supported[$encoding] = api_equal_encodings($encoding, mb_list_encodings(), true);
        } else {
            $supported[$encoding] = false;
        }
    }
    return $supported[$encoding];
}

/**
 * Checks whether the specified encoding is supported by the PHP iconv extension.
 * @param string $encoding	The specified encoding.
 * @return bool				Returns TRUE when the specified encoding is supported, FALSE othewise.
 */
function _api_iconv_supports($encoding) {
    static $supported = array();
    if (!isset($supported[$encoding])) {
        if (ICONV_INSTALLED) {
            $enc = api_refine_encoding_id($encoding);
            if ($enc != 'HTML-ENTITIES') {
                $test_string = '';
                for ($i = 32; $i < 128; $i++) {
                    $test_string .= chr($i);
                }
                $supported[$encoding] = (@iconv_strlen($test_string, $enc)) ? true : false;
            } else {
                $supported[$encoding] = false;
            }
        } else {
            $supported[$encoding] = false;
        }
    }
    return $supported[$encoding];
}

// This function checks whether the function _api_convert_encoding() (the php-
// implementation) is able to convert from/to a given encoding.
function _api_convert_encoding_supports($encoding) {
    static $supports = array();
    if (!isset($supports[$encoding])) {
        $supports[$encoding] = _api_get_character_map_name(api_refine_encoding_id($encoding)) != '';
    }
    return $supports[$encoding];
}

/**
 * Checks whether the specified encoding is supported by the html-entitiy related functions.
 * @param string $encoding	The specified encoding.
 * @return bool				Returns TRUE when the specified encoding is supported, FALSE othewise.
 */
function _api_html_entity_supports($encoding) {
    static $supports = array();
    if (!isset($supports[$encoding])) {
        // See http://php.net/manual/en/function.htmlentities.php
        $html_entity_encodings = array(
            'ISO-8859-1',
            'ISO-8859-15',
            'UTF-8',
            'CP866',
            'CP1251',
            'CP1252',
            'KOI8-R',
            'BIG5', '950',
            'GB2312', '936',
            'BIG5-HKSCS',
            'Shift_JIS', 'SJIS', '932',
            'EUC-JP', 'EUCJP'
        );
        $supports[$encoding] = api_equal_encodings($encoding, $html_entity_encodings);
    }
    return $supports[$encoding];
}


/**
 * Upgrading the PHP5 mbstring extension
 */

// A multibyte replacement of strchr(). This function exists in PHP 5 >= 5.2.0
// See http://php.net/manual/en/function.mb-strrchr
if (MBSTRING_INSTALLED && !function_exists('mb_strchr')) {
    function mb_strchr($haystack, $needle, $part = false, $encoding = null) {
        if (empty($encoding)) {
            $encoding = mb_internal_encoding();
        }
        return mb_strstr($haystack, $needle, $part, $encoding);
    }
}

// A multibyte replacement of stripos(). This function exists in PHP 5 >= 5.2.0
// See http://php.net/manual/en/function.mb-stripos
if (MBSTRING_INSTALLED && !function_exists('mb_stripos')) {
    function mb_stripos($haystack, $needle, $offset = 0, $encoding = null) {
        if (empty($encoding)) {
            $encoding = mb_internal_encoding();
        }
        return mb_strpos(mb_strtolower($haystack, $encoding), mb_strtolower($needle, $encoding), $offset, $encoding);
    }
}

// A multibyte replacement of stristr(). This function exists in PHP 5 >= 5.2.0
// See http://php.net/manual/en/function.mb-stristr
if (MBSTRING_INSTALLED && !function_exists('mb_stristr')) {
    function mb_stristr($haystack, $needle, $part = false, $encoding = null) {
        if (empty($encoding)) {
            $encoding = mb_internal_encoding();
        }
        $pos = mb_strpos(mb_strtolower($haystack, $encoding), mb_strtolower($needle, $encoding), 0, $encoding);
        if ($pos === false) {
            return false;
        }
        if ($part) {
            return mb_substr($haystack, 0, $pos + 1, $encoding);
        }
        return mb_substr($haystack, $pos, mb_strlen($haystack, $encoding), $encoding);
    }
}

// A multibyte replacement of strrchr(). This function exists in PHP 5 >= 5.2.0
// See http://php.net/manual/en/function.mb-strrchr
if (MBSTRING_INSTALLED && !function_exists('mb_strrchr')) {
    function mb_strrchr($haystack, $needle, $part = false, $encoding = null) {
        if (empty($encoding)) {
            $encoding = mb_internal_encoding();
        }
        $needle = mb_substr($needle, 0, 1, $encoding);
        $pos = mb_strrpos($haystack, $needle, mb_strlen($haystack, $encoding) - 1, $encoding);
        if ($pos === false) {
            return false;
        }
        if ($part) {
            return mb_substr($haystack, 0, $pos + 1, $encoding);
        }
        return mb_substr($haystack, $pos, mb_strlen($haystack, $encoding), $encoding);
    }
}

// A multibyte replacement of strstr(). This function exists in PHP 5 >= 5.2.0
// See http://php.net/manual/en/function.mb-strstr
if (MBSTRING_INSTALLED && !function_exists('mb_strstr')) {
    function mb_strstr($haystack, $needle, $part = false, $encoding = null) {
        if (empty($encoding)) {
            $encoding = mb_internal_encoding();
        }
        $pos = mb_strpos($haystack, $needle, 0, $encoding);
        if ($pos === false) {
            return false;
        }
        if ($part) {
            return mb_substr($haystack, 0, $pos + 1, $encoding);
        }
        return mb_substr($haystack, $pos, mb_strlen($haystack, $encoding), $encoding);
    }
}
