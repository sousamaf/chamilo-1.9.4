<?php
/**
 * Chamilo metadata/md_phpdig.php
 * 2005/03/24
 * @copyright 2005 rene.haentjens@UGent.be -  see metadata/md_funcs.php
 * @package chamilo.metadata
 *
 */
/**
 *	Chamilo Metadata: PhpDig connection
 *
 *   If PhpDig 1.8.3 is installed in a Chamilo course site, then MD items
 *   can be indexed for search (via PhpDig's search screen search.php).
 *
 *   The functions below inject the words of metadata/indexabletext directly
 *   into PhpDig's tables. Affected tables:
 *
 *   keywords: key_id, twoletters, keyword (lowercase, accents removed)
 *
 *   sites:    site_id, site_url (e.g. http://xx.yy.zz/), upddate, ...
 *
 *   spider:   spider_id, site_id, upddate, num_words, first_words,
 *                   path (e.g. uu/vv/ww/), file (e.g. index.php?sid=xxx), ...
 *
 *   engine:   spider_id, key_id, weight
 *
 *   Most of the function code is a simplified version of real PhpDig code
 *   released under the GNU GPL V2, see www.phpdig.net.
 *
*/


// PHPDIG CONNECTION ---------------------------------------------------------->

$phpDigInc = get_course_path() . $_course['path'] . '/phpdig-1.8.6/includes/';
$phpDigIncCn = $phpDigInc. 'connect.php';  // to connect to PhpDig's database
$phpDigIncCw = $phpDigInc. 'common_words.txt';  // stopwords

//  if (!file_exists($phpDigIncCn)) return(); doesn't seem to work properly...


if (file_exists($phpDigIncCw))
    if (is_array($lines = @file($phpDigIncCw)))
        while (list($id,$word) = each($lines))
            $common_words[trim($word)] = 1;

define('SUMMARY_DISPLAY_LENGTH', 700);
//define('PHPDIG_ENCODING', 'iso-8859-1');
define('PHPDIG_ENCODING', strtolower($charset));
define('SMALL_WORDS_SIZE', 2);
define('MAX_WORDS_SIZE',50);
define('WORDS_CHARS_LATIN1', '[:alnum:]��ߵ');

foreach (array( 'A'=>'������', 'a'=>'������', 'O'=>'������', 'o'=>'������',
                'E'=>'����', 'e'=>'����', 'C'=>'�', 'c'=>'�', 'I'=>'����',
                'i'=>'����', 'U'=>'����', 'u'=>'����', 'Y'=>'�', 'y'=>'��',
                'N'=>'�', 'n'=>'�') as $without => $allwith)
    foreach (explode('!', chunk_split($allwith, 1, '!')) as $with)
    if ($with)  // because last one will be empty!
    {
        $letterswithout .= $without; $letterswith .= $with;
    }
define('LETTERS_WITH_ACCENTS', $letterswith);
define('SAME_WITHOUT_ACCENTS', $letterswithout);

(strlen(LETTERS_WITH_ACCENTS) == strlen(SAME_WITHOUT_ACCENTS))
    or give_up('LETTERS_WITH_ACCENTS problem in md_phpdig.php');


function find_site($url)
{
    $site_url = "site_url = '" . addslashes($url) . "'";

    $result = Database::query("SELECT site_id FROM " . PHPDIG_DB_PREFIX .
        "sites WHERE " . $site_url);  // find site

    if (Database::num_rows($result) == 1)
    {
        $row = Database::fetch_array($result); return (int) $row['site_id'];
    }
    else
    {
        $result = Database::query("INSERT INTO " . PHPDIG_DB_PREFIX .
            "sites SET " . $site_url);  // new site
        $site_id = Database::insert_id();

        $result = Database::query("INSERT INTO " . PHPDIG_DB_PREFIX .
            "site_page (site_id,num_page) VALUES ('$site_id', '0')");

        return $site_id;
    }
}

function remove_engine_entries($url, $path, $file = '')
{
	global $charset;

    $and_path = " AND path = '" . addslashes($path) . "'";
    if ($file) $and_path .= " AND file LIKE '" . addslashes(
        str_replace(array('_', '%'), array('\_', '\%'), $file)) . "%'";

    $result = Database::query("SELECT spider_id FROM " . PHPDIG_DB_PREFIX .
        "spider WHERE site_id=" . ($site_id = find_site($url)) . $and_path);  // find page(s)

    while ($row = Database::fetch_array($result))
    {
        Database::query("DELETE FROM " . PHPDIG_DB_PREFIX .
            "engine WHERE spider_id=" . (int)$row['spider_id']);  // delete all references to keywords
        $aff .= ' +' . Database::affected_rows();
    }

    Database::query("DELETE FROM " . PHPDIG_DB_PREFIX .
        "spider WHERE site_id=" . $site_id . $and_path);  // delete page

    echo htmlspecialchars($url . $path . $file, ENT_QUOTES, $charset), ' (site_id ',
        $site_id, '): ', Database::affected_rows(), $aff,
        ' pages + word references removed from index.<br />';

    return $site_id;
}

function index_words($site_id, $path, $file, $first_words, $keywords)
{
    global $common_words;

    $spider_set_path_etc = "spider SET path='" . addslashes($path) .
        "',file='" . addslashes($file) . "',first_words='" .
        addslashes($first_words) . "',site_id='$site_id'";
        // do not set upddate,md5,num_words,last_modified,filesize

    Database::query("INSERT INTO " . PHPDIG_DB_PREFIX . $spider_set_path_etc);

    $spider_id = Database::insert_id(); $new = 0;

    foreach ($keywords as $key => $w)
    if (strlen($key) > SMALL_WORDS_SIZE and strlen($key) <= MAX_WORDS_SIZE and
            !isset($common_words[$key]) and
            ereg('^['.WORDS_CHARS_LATIN1.'#$]', $key))
    {
        $result = Database::query("SELECT key_id FROM " . PHPDIG_DB_PREFIX .
            "keywords WHERE keyword = '" . addslashes($key) . "'");

        if (Database::num_rows($result) == 0)
        {
            Database::query("INSERT INTO " . PHPDIG_DB_PREFIX .
                "keywords (keyword,twoletters) VALUES ('" . addslashes($key) .
                "','" .addslashes(substr(str_replace('\\','',$key),0,2)) ."')");
            $key_id = Database::insert_id(); $new++;
        }
        else
        {
            $keyid = Database::fetch_row($result); $key_id = $keyid[0];
        }

        Database::query("INSERT INTO " . PHPDIG_DB_PREFIX .
            "engine (spider_id,key_id,weight) VALUES ($spider_id,$key_id,$w)");
    }
    global $charset;
    echo '<tr><td>', htmlspecialchars($file, ENT_QUOTES, $charset), '</td><td>(spider_id ',
        $spider_id, '):</td><td align="right">', count($keywords), ' kwds, ',
        $new , ' new</td></tr>', "\n";
}

function get_first_words($text, $path, $file)
{
    $db_some_text = preg_replace("/([ ]{2}|\n|\r|\r\n)/" ," ", $text);
    if (strlen($db_some_text) > SUMMARY_DISPLAY_LENGTH) {
      $db_some_text = substr($db_some_text, 0, SUMMARY_DISPLAY_LENGTH) . "...";
    }

    $titre_resume = $path . $file;
    if (($psc = strpos($titre_resume, 'scorm/')) !== FALSE)
        $titre_resume = substr($titre_resume, $psc + 6);
    if (($pth = strpos($titre_resume, '&thumb')) !== FALSE)
        $titre_resume = substr($titre_resume, 0, $pth);

    return $titre_resume."\n".$db_some_text;
}

function get_keywords($text)
{
    if (($token = strtok(phpdigEpureText($text), ' '))) $nbre_mots[$token] = 1;

    while (($token = strtok(' ')))
        $nbre_mots[$token] = ($nm = $nbre_mots[$token]) ? $nm + 1 : 1;

    return $nbre_mots;
}

function phpdigEpureText($text)
{
    $text = strtr(phpdigStripAccents(strtolower($text)), '��', '��');

    $text = ereg_replace('[^'.WORDS_CHARS_LATIN1.' \'._~@#$&%/=-]+',' ',$text);  // RH: was ' \'._~@#$:&%/;,=-]+', also below

    $text = ereg_replace('(['.WORDS_CHARS_LATIN1.'])[\'._~@#$&%/=-]+($|[[:space:]]$|[[:space:]]['.WORDS_CHARS_LATIN1.'])','\1\2',$text);

    // the next two repeated lines needed
    if (SMALL_WORDS_SIZE >= 1) {
      $text = ereg_replace('[[:space:]][^ ]{1,'.SMALL_WORDS_SIZE.'}[[:space:]]',' ',' '.$text.' ');
      $text = ereg_replace('[[:space:]][^ ]{1,'.SMALL_WORDS_SIZE.'}[[:space:]]',' ',' '.$text.' ');
    }
    //$text = ereg_replace('\.+[[:space:]]|\.+$|\.{2,}',' ',$text);
    $text = ereg_replace('\.{2,}',' ',$text);
    $text = ereg_replace('^[[:space:]]*\.+',' ',$text);

    return trim(ereg_replace("[[:space:]]+"," ",$text));
}

function phpdigStripAccents($chaine)
{
    $chaine = str_replace('�','ae',str_replace('�','ae',$chaine));
    return strtr($chaine, LETTERS_WITH_ACCENTS, SAME_WITHOUT_ACCENTS);
}
?>
