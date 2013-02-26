<?php
/* For licensing terms, see /license.txt */

/**
 * This is a display library for Chamilo.
 *
 * Include/require it in your code to use its public functionality.
 * There are also several display public functions in the main api library.
 *
 * All public functions static public functions inside a class called Display,
 * so you use them like this: e.g.
 * Display::display_normal_message($message)
 *
 * @package chamilo.library
 */
/**
 * Code
 */
define('MAX_LENGTH_BREADCRUMB', 100);

define('ICON_SIZE_TINY',    16);
define('ICON_SIZE_SMALL',   22);
define('ICON_SIZE_MEDIUM',  32);
define('ICON_SIZE_LARGE',   48);
define('ICON_SIZE_BIG',     64);
define('ICON_SIZE_HUGE',    128);

define('SHOW_TEXT_NEAR_ICONS', false);


/**
 * Display class
 * contains several public functions dealing with the display of
 * table data, messages, help topics, ...
 *
 * @package chamilo.library
 */

class Display {

    /* The main template*/
    static $global_template;
    static $preview_style = null;

    public function __construct() {
    }

     /**
     * Displays the page header
     * @param string The name of the page (will be showed in the page title)
     * @param string Optional help file name
     */
    public static function display_header($tool_name ='', $help = null, $page_header = null) {
        self::$global_template = new Template($tool_name);
        self::$global_template->set_help($help);
        if (!empty(self::$preview_style)) {
            self::$global_template->preview_theme = self::$preview_style;
            self::$global_template->set_css_files();
            self::$global_template->set_js_files();
        }
        if (!empty($page_header)) {
            self::$global_template->assign('header', $page_header);
        }
        echo self::$global_template->show_header_template();
    }

    /**
     * Displays the reduced page header (without banner)
     */
    public static function display_reduced_header() {
        global $show_learnpath, $tool_name;
        self::$global_template = new Template($tool_name, false, false, $show_learnpath);
        echo self::$global_template ->show_header_template();
    }

    public static function display_no_header() {
        global $tool_name;
        $disable_js_and_css_files = true;
        self::$global_template = new Template($tool_name, false, false, $show_learnpath);
        //echo self::$global_template->show_header_template();
    }

    /**
     * Displays the reduced page header (without banner)
     */
    public static function set_header() {
        global $show_learnpath;
        self::$global_template = new Template($tool_name, false, false, $show_learnpath);
    }

    /**
     * Display the page footer
     */
    public static function display_footer() {
        echo self::$global_template ->show_footer_template();
    }

    public static function page()
    {
        return new Page();
    }

    /**
     * Displays the tool introduction of a tool.
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @param string $tool These are the constants that are used for indicating the tools.
     * @param array $editor_config Optional configuration settings for the online editor.
     * return: $tool return a string array list with the "define" in main_api.lib
     * @return html code for adding an introduction
     */
    public static function display_introduction_section($tool, $editor_config = null) {
        echo self::return_introduction_section($tool, $editor_config);
    }

    public static function return_introduction_section($tool, $editor_config = null) {
        $is_allowed_to_edit = api_is_allowed_to_edit();
        $moduleId = $tool;
        if (api_get_setting('enable_tool_introduction') == 'true' || $tool == TOOL_COURSE_HOMEPAGE) {
            $introduction_section = null;
            require api_get_path(INCLUDE_PATH).'introductionSection.inc.php';
            return $introduction_section;
        }
    }

    /**
     *	Displays a localised html file
     *	tries to show the file "$full_file_name"."_".$language_interface.".html"
     *	and if this does not exist, shows the file "$full_file_name".".html"
     *	warning this public function defines a global
     *	@param $full_file_name, the (path) name of the file, without .html
     *	@return return a string with the path
     */
    public static function display_localised_html_file($full_file_name) {
        global $language_interface;
        $localised_file_name = $full_file_name.'_'.$language_interface.'.html';
        $default_file_name = $full_file_name.'.html';
        if (file_exists($localised_file_name)) {
            include $localised_file_name;
        } else {
            include ($default_file_name);
        }
    }

    /**
     * Displays a table
     * @param array $header Titles for the table header
     * 						each item in this array can contain 3 values
     * 						- 1st element: the column title
     * 						- 2nd element: true or false (column sortable?)
     * 						- 3th element: additional attributes for
     *  						th-tag (eg for column-width)
     * 						- 4the element: additional attributes for the td-tags
     * @param array $content 2D-array with the tables content
     * @param array $sorting_options Keys are:
     * 					'column' = The column to use as sort-key
     * 					'direction' = SORT_ASC or SORT_DESC
     * @param array $paging_options Keys are:
     * 					'per_page_default' = items per page when switching from
     * 										 full-	list to per-page-view
     * 					'per_page' = number of items to show per page
     * 					'page_nr' = The page to display
     * @param array $query_vars Additional variables to add in the query-string
     * @param string The style that the table will show. You can set 'table' or 'grid'
     * @author bart.mollet@hogent.be
     */
    public static function display_sortable_table($header, $content, $sorting_options = array(), $paging_options = array(), $query_vars = null, $form_actions = array(), $style = 'table') {
        global $origin;
        $column = isset($sorting_options['column']) ? $sorting_options['column'] : 0;
        $default_items_per_page = isset($paging_options['per_page']) ? $paging_options['per_page'] : 20;

        $table = new SortableTableFromArray($content, $column, $default_items_per_page);

        if (is_array($query_vars)) {
            $table->set_additional_parameters($query_vars);
        }
        if ($style == 'table') {
            if (is_array($header) && count($header) > 0) {
                foreach ($header as $index => $header_item) {
                    $table->set_header($index, $header_item[0], $header_item[1], $header_item[2], $header_item[3]);
                }
            }
            $table->set_form_actions($form_actions);
            $table->display();
        } else {
            $table->display_grid();
        }
    }

    /**
     * Shows a nice grid
     * @param string grid name (important to create css)
     * @param array header content
     * @param array array with the information to show
     * @param array $paging_options Keys are:
     * 					'per_page_default' = items per page when switching from
     * 										 full-	list to per-page-view
     * 					'per_page' = number of items to show per page
     * 					'page_nr' = The page to display
     * 					'hide_navigation' =  true to hide the navigation
     * @param array $query_vars Additional variables to add in the query-string
     * @param array $form actions Additional variables to add in the query-string
     * @param mixed An array with bool values to know which columns show. i.e: $visibility_options= array(true, false) we will only show the first column
     * 				Can be also only a bool value. TRUE: show all columns, FALSE: show nothing
     */
    public static function display_sortable_grid($name, $header, $content, $paging_options = array(), $query_vars = null, $form_actions = array(), $visibility_options = true, $sort_data = true, $grid_class = array()) {
        echo self::return_sortable_grid($name, $header, $content, $paging_options, $query_vars, $form_actions, $visibility_options, $sort_data, $grid_class);
    }

    /**
     * Gets a nice grid in html string
     * @param string grid name (important to create css)
     * @param array header content
     * @param array array with the information to show
     * @param array $paging_options Keys are:
     * 					'per_page_default' = items per page when switching from
     * 										 full-	list to per-page-view
     * 					'per_page' = number of items to show per page
     * 					'page_nr' = The page to display
     * 					'hide_navigation' =  true to hide the navigation
     * @param array $query_vars Additional variables to add in the query-string
     * @param array $form actions Additional variables to add in the query-string
     * @param mixed An array with bool values to know which columns show. i.e: $visibility_options= array(true, false) we will only show the first column
     * 				Can be also only a bool value. TRUE: show all columns, FALSE: show nothing
     * @param bool  true for sorting data or false otherwise
     * @param array grid classes
     * @return 	string   html grid
     */
    public static function return_sortable_grid($name, $header, $content, $paging_options = array(), $query_vars = null, $form_actions = array(), $visibility_options = true, $sort_data = true, $grid_class = array()) {
        global $origin;
        $column =  0;
        $default_items_per_page = isset($paging_options['per_page']) ? $paging_options['per_page'] : 20;
        $table = new SortableTableFromArray($content, $column, $default_items_per_page, $name);
        if (is_array($query_vars)) {
            $table->set_additional_parameters($query_vars);
        }
        return $table->display_simple_grid($visibility_options, $paging_options['hide_navigation'], $default_items_per_page, $sort_data, $grid_class);
    }

    /**
     * Displays a table with a special configuration
     * @param array $header Titles for the table header
     * 						each item in this array can contain 3 values
     * 						- 1st element: the column title
     * 						- 2nd element: true or false (column sortable?)
     * 						- 3th element: additional attributes for
     *  						th-tag (eg for column-width)
     * 						- 4the element: additional attributes for the td-tags
     * @param array $content 2D-array with the tables content
     * @param array $sorting_options Keys are:
     * 					'column' = The column to use as sort-key
     * 					'direction' = SORT_ASC or SORT_DESC
     * @param array $paging_options Keys are:
     * 					'per_page_default' = items per page when switching from
     * 										 full-	list to per-page-view
     * 					'per_page' = number of items to show per page
     * 					'page_nr' = The page to display
     * @param array $query_vars Additional variables to add in the query-string
     * @param array $column_show Array of binaries 1= show columns 0. hide a column
     * @param array $column_order An array of integers that let us decide how the columns are going to be sort.
     * 						      i.e:  $column_order=array('1''4','3','4'); The 2nd column will be order like the 4th column
     * @param array $form_actions Set optional forms actions
     *
     * @author Julio Montoya
     */
    public static function display_sortable_config_table($table_name, $header, $content, $sorting_options = array(), $paging_options = array(), $query_vars = null, $column_show = array(), $column_order = array(), $form_actions = array()) {
        global $origin;
        $column = isset($sorting_options['column']) ? $sorting_options['column'] : 0;
        $default_items_per_page = isset($paging_options['per_page']) ? $paging_options['per_page'] : 20;

        $table = new SortableTableFromArrayConfig($content, $column, $default_items_per_page, $table_name, $column_show, $column_order);

        if (is_array($query_vars)) {
            $table->set_additional_parameters($query_vars);
        }
        // Show or hide the columns header
        if (is_array($column_show)) {
            for ($i = 0; $i < count($column_show); $i++) {
                if (!empty($column_show[$i])) {
                    $val0 = isset($header[$i][0]) ? $header[$i][0] : null;
                    $val1 = isset($header[$i][1]) ? $header[$i][1] : null;
                    $val2 = isset($header[$i][2]) ? $header[$i][2] : null;
                    $val3 = isset($header[$i][3]) ? $header[$i][3] : null;
                    $table->set_header($i, $val0, $val1, $val2, $val3);
                }
            }
        }
        $table->set_form_actions($form_actions);
        $table->display();
    }

    /**
     * Displays a normal message. It is recommended to use this public function
     * to display any normal information messages.
     *
     * @param bool	Filter (true) or not (false)
     * @return void
     */
    public static function display_normal_message($message, $filter = true) {
    	echo self::return_message($message, 'normal', $filter);
    }

    /**
     * Displays an warning message. Use this if you want to draw attention to something
     * This can also be used for instance with the hint in the exercises
     *
     */
    public static function display_warning_message($message, $filter = true) {
    	echo self::return_message($message, 'warning', $filter);
    }

    /**
     * Displays an confirmation message. Use this if something has been done successfully
     * @param bool	Filter (true) or not (false)
     * @return void
     */
    public static function display_confirmation_message ($message, $filter = true) {
        echo self::return_message($message, 'confirm', $filter);
    }

    /**
     * Displays an error message. It is recommended to use this public function if an error occurs
     * @param string $message - include any additional html
     *                          tags if you need them
     * @param bool	Filter (true) or not (false)
     * @return void
     */
    public static function display_error_message ($message, $filter = true) {
        echo self::return_message($message, 'error', $filter);
    }

    public static function return_message_and_translate($message, $type='normal', $filter = true) {
        $message = get_lang($message);
        echo self::return_message($message, $type, $filter);
    }

    /**
     * Returns a div html string with
     * @param   string  The message
     * @param   string  The message type (confirm,normal,warning,error)
     * @param   bool    Whether to XSS-filter or not
     * @return  string  Message wrapped into an HTML div
     */
    public static function return_message($message, $type='normal', $filter = true) {
        if ($filter) {
        	$message = api_htmlentities($message, ENT_QUOTES, api_is_xml_http_request() ? 'UTF-8' : api_get_system_encoding());
            //$message = Security::remove_XSS($message);
        }
        $class = "";
        switch($type) {
            case 'warning':
               $class .= 'warning-message';
               break;
            case 'error':
               $class .= 'error-message';
               break;
            case 'confirmation':
            case 'confirm':
            case 'success':
                $class .= 'confirmation-message';
               break;
            case 'normal':
            default:
                $class .= 'normal-message';
        }
        return self::div($message, array('class'=> $class));
    }

    /**
     * Returns an encrypted mailto hyperlink
     *
     * @param string  e-mail
     * @param string  clickable text
     * @param string  optional, class from stylesheet
     * @return string encrypted mailto hyperlink
     */
    public static function encrypted_mailto_link ($email, $clickable_text = null, $style_class = '') {
        if (is_null($clickable_text)) {
            $clickable_text = $email;
        }
        // "mailto:" already present?
        if (substr($email, 0, 7) != 'mailto:') {
            $email = 'mailto:'.$email;
        }
        // Class (stylesheet) defined?
        if ($style_class != '') {
            $style_class = ' class="'.$style_class.'"';
        }
        // Encrypt email
        $hmail = '';
        for ($i = 0; $i < strlen($email); $i ++) {
            $hmail .= '&#'.ord($email {
            $i }).';';
        }
        $hclickable_text = null;
        // Encrypt clickable text if @ is present
        if (strpos($clickable_text, '@')) {
            for ($i = 0; $i < strlen($clickable_text); $i ++) {
                $hclickable_text .= '&#'.ord($clickable_text {
                $i }).';';
            }
        } else {
            $hclickable_text = @htmlspecialchars($clickable_text, ENT_QUOTES, api_get_system_encoding());
        }
        // Return encrypted mailto hyperlink
        return '<a href="'.$hmail.'"'.$style_class.' class="clickable_email_link">'.$hclickable_text.'</a>';
    }

    /**
     * Returns an mailto icon hyperlink
     *
     * @param string  e-mail
     * @param string  icon source file from the icon lib
     * @param integer  icon size from icon lib
     * @param string  optional, class from stylesheet
     * @return string encrypted mailto hyperlink
     */
    public static function icon_mailto_link ($email, $icon_file = "mail.png", $icon_size = 22, $style_class = '') {
        // "mailto:" already present?
        if (substr($email, 0, 7) != 'mailto:') {
            $email = 'mailto:'.$email;
        }
        // Class (stylesheet) defined?
        if ($style_class != '') {
            $style_class = ' class="'.$style_class.'"';
        }
        // Encrypt email
        $hmail = '';
        for ($i = 0; $i < strlen($email); $i ++) {
            $hmail .= '&#'.ord($email {
            $i }).';';
        }
        // icon html code
        $icon_html_source = self::return_icon($icon_file, $hmail, '', $icon_size);
        // Return encrypted mailto hyperlink
        return '<a href="'.$hmail.'"'.$style_class.' class="clickable_email_link">'.$icon_html_source.'</a>';
    }

    /**
     *	Creates a hyperlink to the platform homepage.
     *	@param string $name, the visible name of the hyperlink, default is sitename
     *	@return string with html code for hyperlink
     */
    public static function get_platform_home_link_html($name = '') {
        if ($name == '') {
            $name = api_get_setting('siteName');
        }
        return '<a href="'.api_get_path(WEB_PATH).'index.php">'.$name.'</a>';
    }



    /**
     * Prints an <option>-list with all letters (A-Z).
     * @param char $selected_letter The letter that should be selected
     * @todo This is English language specific implementation. It should be adapted for the other languages.
     */
    public static function get_alphabet_options($selected_letter = '') {
        $result = '';
        for ($i = 65; $i <= 90; $i ++) {
            $letter = chr($i);
            $result .= '<option value="'.$letter.'"';
            if ($selected_letter == $letter) {
                $result .= ' selected="selected"';
            }
            $result .= '>'.$letter.'</option>';
        }
        return $result;
    }

    /**
     * Get the options withing a select box within the given values
     * @param int   Min value
     * @param int   Max value
     * @param int   Default value
     * @return string HTML select options
     */
    public static function get_numeric_options($min, $max, $selected_num = 0) {
        $result = '';
        for ($i = $min; $i <= $max; $i ++) {
            $result .= '<option value="'.$i.'"';
            if (is_int($selected_num))
                if ($selected_num == $i) {
                    $result .= ' selected="selected"';
                }
            $result .= '>'.$i.'</option>';
        }
        return $result;
    }

    /**
     * Shows the so-called "left" menu for navigating
     */
    public static function show_course_navigation_menu($isHidden = false) {
        global $output_string_menu;
        global $_setting;

        // Check if the $_SERVER['REQUEST_URI'] contains already url parameters (thus a questionmark)
        if (strpos($_SERVER['REQUEST_URI'], '?') === false) {
            $sourceurl = api_get_self().'?';
        } else {
            $sourceurl = $_SERVER['REQUEST_URI'];
        }
        $output_string_menu = '';
        if ($isHidden == 'true' and $_SESSION['hideMenu']) {

            $_SESSION['hideMenu'] = 'hidden';

            $sourceurl = str_replace('&isHidden=true', '', $sourceurl);
            $sourceurl = str_replace('&isHidden=false', '', $sourceurl);

            $output_string_menu .= ' <a href="'.$sourceurl.'&isHidden=false"><img src="../../main/img/expand.gif" alt="'.'Show menu1'.'" padding:"2px"/></a>';
        } elseif ($isHidden == 'false' && $_SESSION['hideMenu']) {
            $sourceurl = str_replace('&isHidden=true', '', $sourceurl);
            $sourceurl = str_replace('&isHidden=false', '', $sourceurl);

            $_SESSION['hideMenu'] = 'shown';
            $output_string_menu .= '<div id="leftimg"><a href="'.$sourceurl.'&isHidden=true"><img src="../../main/img/collapse.gif" alt="'.'Hide menu2'.'" padding:"2px"/></a></div>';
        } elseif ($_SESSION['hideMenu']) {
            if ($_SESSION['hideMenu'] == 'shown') {
                $output_string_menu .= '<div id="leftimg"><a href="'.$sourceurl.'&isHidden=true"><img src="../../main/img/collapse.gif" alt="'.'Hide menu3'.' padding:"2px"/></a></div>';
            }
            if ($_SESSION['hideMenu'] == 'hidden') {
                $sourceurl = str_replace('&isHidden=true', '', $sourceurl);
                $output_string_menu .= '<a href="'.$sourceurl.'&isHidden=false"><img src="../../main/img/expand.gif" alt="'.'Hide menu4'.' padding:"2px"/></a>';
            }
        } elseif (!$_SESSION['hideMenu']) {
            $_SESSION['hideMenu'] = 'shown';
            if (isset($_cid)) {
                $output_string_menu .= '<div id="leftimg"><a href="'.$sourceurl.'&isHidden=true"><img src="main/img/collapse.gif" alt="'.'Hide menu5'.' padding:"2px"/></a></div>';
            }
        }
    }

    /**
     * This public function displays an icon
     * @param string   The filename of the file (in the main/img/ folder
     * @param string   The alt text (probably a language variable)
     * @param array    additional attributes (for instance height, width, onclick, ...)
     * @param integer  The wanted width of the icon (to be looked for in the corresponding img/icons/ folder)
     * @return void
    */
    public static function display_icon($image, $alt_text = '', $additional_attributes = array(), $size=null) {
        echo self::return_icon($image, $alt_text, $additional_attributes, $size);
    }

    /**
     * This public function returns the htmlcode for an icon
     *
     * @param string   The filename of the file (in the main/img/ folder
     * @param string   The alt text (probably a language variable)
     * @param array    Additional attributes (for instance height, width, onclick, ...)
     * @param integer  The wanted width of the icon (to be looked for in the corresponding img/icons/ folder)
     * @return string  An HTML string of the right <img> tag
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University 2006
     * @author Julio Montoya 2010 Function improved, adding image constants
     * @author Yannick Warnier 2011 Added size handler
     * @version Feb 2011
    */
    public static function return_icon($image, $alt_text = '', $additional_attributes = array(), $size = ICON_SIZE_SMALL, $show_text = true, $return_only_path = false) {

        $code_path   = api_get_path(SYS_CODE_PATH);
        $w_code_path = api_get_path(WEB_CODE_PATH);

        $image = trim($image);
        $theme = 'css/'.api_get_visual_theme().'/icons/';
        $icon = '';
        $size_extra = '';

        if (isset($size)) {
            $size = intval($size);
            $size_extra = $size.'/';
        } else {
            $size = ICON_SIZE_SMALL;
        }

        //Checking the theme icons folder example: main/css/chamilo/icons/XXX
        if (is_file($code_path.$theme.$size_extra.$image)) {
            $icon = $w_code_path.$theme.$size_extra.$image;
        } elseif (is_file($code_path.'img/icons/'.$size_extra.$image)) {
            //Checking the main/img/icons/XXX/ folder
            $icon = $w_code_path.'img/icons/'.$size_extra.$image;
        } else {
            //Checking the img/ folder
            $icon = $w_code_path.'img/'.$image;
        }
        $icon = api_get_cdn_path($icon);
        if ($return_only_path) {
            return $icon;
        }

        $img = self::img($icon, $alt_text, $additional_attributes);
        if (SHOW_TEXT_NEAR_ICONS == true and !empty($alt_text)) {
            if ($show_text) {
                $img = "$img $alt_text";
            }
        }
        return $img;
    }

    /**
     * Returns the htmlcode for an image
     *
     * @param string $image the filename of the file (in the main/img/ folder
     * @param string $alt_text the alt text (probably a language variable)
     * @param array additional attributes (for instance height, width, onclick, ...)
     * @author Julio Montoya 2010
     */
    public static function img($image_path, $alt_text = '', $additional_attributes = array()) {

        // Sanitizing the parameter $image_path
        $image_path = Security::filter_img_path($image_path);

        // alt text = the image name if there is none provided (for XHTML compliance)
        if ($alt_text == '') {
            $alt_text = basename($image_path);
        }

        $additional_attributes['src'] = $image_path;

        if (empty($additional_attributes['alt'])) {
            $additional_attributes['alt'] = $alt_text;
        }
        if (empty($additional_attributes['title'])) {
            $additional_attributes['title'] = $alt_text;
        }
        //return '<img src="'.$image_path.'" alt="'.$alt_text.'"  title="'.$alt_text.'" '.$attribute_list.' />';
        return self::tag('img', '', $additional_attributes);
    }

    /**
     * Returns the htmlcode for a tag (h3, h1, div, a, button), etc
     *
     * @param string $image the filename of the file (in the main/img/ folder
     * @param string $alt_text the alt text (probably a language variable)
     * @param array additional attributes (for instance height, width, onclick, ...)
     * @author Julio Montoya 2010
     */
    public static function tag($tag, $content, $additional_attributes = array()) {
        $attribute_list = '';
        // Managing the additional attributes
        if (!empty($additional_attributes) && is_array($additional_attributes)) {
            $attribute_list = '';
            foreach ($additional_attributes as $key => & $value) {
                $attribute_list .= $key.'="'.$value.'" ';
            }
        }
        //some tags don't have this </XXX>
        if (in_array($tag, array('img','input','br'))) {
            $return_value = '<'.$tag.' '.$attribute_list.' />';
        } else {
            $return_value = '<'.$tag.' '.$attribute_list.' > '.$content.'</'.$tag.'>';
        }
        return $return_value;
    }

    /**
     * Creates a URL anchor
     */
    public static function url($name, $url, $extra_attributes = array()) {
        if (!empty($url)) {
            $extra_attributes['href']= $url;
        }
        return self::tag('a', $name, $extra_attributes);
    }

    /**
     * Creates a div tag
     */
    public static function div($content, $extra_attributes = array()) {
        return self::tag('div', $content, $extra_attributes);
    }

    /**
     * Creates a span tag
     */
    public static function span($content, $extra_attributes = array()) {
        return self::tag('span', $content, $extra_attributes);
    }

    /**
     * Displays an HTML input tag
     *
     */
    public static function input($type, $name, $value, $extra_attributes = array()) {
         if (isset($type)) {
            $extra_attributes['type']= $type;
         }
         if (isset($name)) {
            $extra_attributes['name']= $name;
         }
         if (isset($value)) {
            $extra_attributes['value']= $value;
        }
        return self::tag('input', '', $extra_attributes);
    }

    public static function button($name, $value, $extra_attributes = array()) {
    	if (!empty($name)) {
    		$extra_attributes['name']= $name;
    	}
    	return self::tag('button', $value, $extra_attributes);
    }

    /**
     * Displays an HTML select tag
     *
     */
    public static function select($name, $values, $default = -1, $extra_attributes = array(), $show_blank_item = true, $blank_item_text = null) {
        $html = '';
        $extra = '';
        $default_id =  'id="'.$name.'" ';
        foreach($extra_attributes as $key=>$parameter) {
            if ($key == 'id') {
                $default_id = '';
            }
            $extra .= $key.'="'.$parameter.'"';
        }
        $html .= '<select name="'.$name.'" '.$default_id.' '.$extra.'>';

        if ($show_blank_item) {
            if (empty($blank_item_text)) {
                $blank_item_text = get_lang('Select');
            } else {
                $blank_item_text = Security::remove_XSS($blank_item_text);
            }
            $html .= self::tag('option', '-- '.$blank_item_text.' --', array('value'=>'-1'));
        }
        if ($values) {
            foreach ($values as $key => $value) {
                if(is_array($value) && isset($value['name'])) {
                    $value = $value['name'];
                }
                $html .= '<option value="'.$key.'"';

                if (is_array($default)) {
                    foreach($default as $item) {
                        if ($item == $key) {
                            $html .= 'selected="selected"';
                            break;
                        }
                    }
                } else {
                    if ($default == $key) {
                        $html .= 'selected="selected"';
                    }
                }

                $html .= '>'.$value.'</option>';
            }
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * Creates a tab menu
     * Requirements: declare the jquery, jquery-ui libraries + the jquery-ui.css  in the $htmlHeadXtra variable before the display_header
     * Add this script
     * @example
             * <script>
                    $(function() {
                        $( "#tabs" ).tabs();
                    });
                </script>
     * @param   array   list of the tab titles
     * @param   array   content that will be showed
     * @param   string  the id of the container of the tab in the example "tabs"
     * @param   array   attributes for the ul
     *
     */
    public static function tabs($header_list, $content_list, $id = 'tabs', $attributes = array(), $ul_attributes = array()) {

        if (empty($header_list) || count($header_list) == 0 ) {
            return '';
        }

        $lis = '';
        $i = 1;
        foreach ($header_list as $item) {
            $item =self::tag('a', $item, array('href'=>'#'.$id.'-'.$i));
            $lis .=self::tag('li', $item, $ul_attributes);
            $i++;
        }
        $ul = self::tag('ul',$lis);

        $i = 1;
        $divs = '';
        foreach ($content_list as $content) {
            $content = self::tag('p',$content);
            $divs .=self::tag('div', $content, array('id'=>$id.'-'.$i));
            $i++;
        }
        $attributes['id'] = $id;
        $main_div = self::tag('div',$ul.$divs, $attributes);
        return $main_div ;
    }

    public static function tabs_only_link($header_list, $selected = null) {
         $id = uniqid();
         $i = 1;
         $lis = null;
         foreach ($header_list as $item) {
            $class = null;
            if ($i == $selected) {
                $class = 'active';
            }
            $item =self::tag('a', $item['content'], array('id'=>$id.'-'.$i, 'href' => $item['url']));
            $lis .=self::tag('li', $item, array('class' => $class));
            $i++;
        }
        return self::tag('ul',$lis, array('class' => 'nav nav-tabs'));
    }

    /**
     * In order to display a grid using jqgrid you have to:
     * @example
     * After your Display::display_header function you have to add the nex javascript code:     *
     * <script>
     *      echo Display::grid_js('my_grid_name',  $url,$columns, $column_model, $extra_params,array()); // for more information of this function check the grid_js() function
     * </script>
     * //Then you have to call the grid_html
     * echo Display::grid_html('my_grid_name');
     * As you can see both function use the same "my_grid_name" this is very important otherwise nothing will work
     *
     * @param   string  the div id, this value must be the same with the first parameter of Display::grid_js()
     * @return  string  html
     *
     */
    public static function grid_html($div_id){
        $table  = self::tag('table','',array('id'=>$div_id));
        $table .= self::tag('div','',array('id'=>$div_id.'_pager'));
        return $table;
    }

    public static function form_row($label, $form_item) {
        $label = self::span($label, array('class' =>'control-label'));
        $form_item = self::div($form_item, array('class' =>'controls'));
        return self::div($label.$form_item, array('class'=>'control-group'));
    }

    /**
     * This is a wrapper to use the jqgrid in Chamilo. For the other jqgrid options visit http://www.trirand.com/jqgridwiki/doku.php?id=wiki:options
     * This function need to be in the ready jquery function example --> $(function() { <?php echo Display::grid_js('grid' ...); ?> }
     * In order to work this function needs the Display::grid_html function with the same div id
     *
     * @param   string  div id
     * @param   string  url where the jqgrid will ask for data (if datatype = json)
     * @param   array   Visible columns (you should use get_lang). An array in which we place the names of the columns.
     * 					This is the text that appears in the head of the grid (Header layer).
     * 					Example: colname   {name:'date',     index:'date',   width:120, align:'right'},
     * @param   array   the column model :  Array which describes the parameters of the columns.This is the most important part of the grid.
     * 					For a full description of all valid values see colModel API. See the url above.
     * @param   array   extra parameters
     * @param   array   data that will be loaded
     * @return  string  the js code
     *
     */
    public static function grid_js($div_id, $url, $column_names, $column_model, $extra_params, $data = array(), $formatter = '', $width_fix = false) {
        $obj = new stdClass();

        if (!empty($url))
            $obj->url       = $url;

        $obj->colNames      = $column_names;
        $obj->colModel      = $column_model;
        $obj->pager         = '#'.$div_id.'_pager';

        $obj->datatype  = 'json';

        $all_value = 10000000;

        //Default row quantity
        if (!isset($extra_params['rowList'])) {
            $extra_params['rowList'] = array(20, 50, 100, 500, 1000, $all_value);
            //$extra_params['rowList'] = array(20, 50, 100, 500, 1000, 2000, 5000, 10000);
        }

        $json = '';
        if (!empty($extra_params['datatype'])) {
            $obj->datatype  = $extra_params['datatype'];
        }

        //Row even odd style
        $obj->altRows = true;
        if (!empty($extra_params['altRows'])) {
            $obj->altRows      = $extra_params['altRows'];
        }

        if (!empty($extra_params['sortname'])) {
            $obj->sortname      = $extra_params['sortname'];
        }
        //$obj->sortorder     = 'desc';
        if (!empty($extra_params['sortorder'])) {
            $obj->sortorder     = $extra_params['sortorder'];
        }

        if (!empty($extra_params['rowList'])) {
            $obj->rowList     = $extra_params['rowList'];
        }
        //Sets how many records we want to view in the grid
        $obj->rowNum = 20;
        if (!empty($extra_params['rowNum'])) {
            $obj->rowNum     = $extra_params['rowNum'];
        }

        //height: 'auto',

        $obj->viewrecords = 'true';

        if (!empty($extra_params['viewrecords']))
            $obj->viewrecords   = $extra_params['viewrecords'];

        if (!empty($extra_params)) {
            foreach ($extra_params as $key=>$element){
                $obj->$key = $element;
            }
        }

        //Adding static data
        if (!empty($data)) {
            $data_var = $div_id.'_data';
            $json.=' var '.$data_var.' = '.json_encode($data).';';
          /*  $json.='for(var i=0;i<='.$data_var.'.length;i++)
                    jQuery("#'.$div_id.'").jqGrid(\'addRowData\',i+1,'.$data_var.'[i]);';*/
            $obj->data = $data_var;
            $obj->datatype = 'local';
            $json.="\n";
        }

        $json_encode = json_encode($obj);

        if (!empty($data)) {
            //Converts the "data":"js_variable" to "data":js_variable othersiwe it will not work
            $json_encode = str_replace('"data":"'.$data_var.'"','"data":'.$data_var.'',$json_encode);
        }

        //Fixing true/false js values that doesn't need the ""
        $json_encode = str_replace(':"true"',':true',$json_encode);
        //wrap_cell is not a valid jqgrid attributes is a hack to wrap a text
        $json_encode = str_replace('"wrap_cell":true','cellattr:function(rowId, tv, rawObject, cm, rdata) { return \'style ="white-space: normal;"\'}',$json_encode);
        $json_encode = str_replace(':"false"',':false',$json_encode);
        $json_encode = str_replace('"formatter":"action_formatter"','formatter:action_formatter',$json_encode);

        if ($width_fix) {
            if (is_numeric($width_fix)) {
                $width_fix = intval($width_fix);
            } else {
                $width_fix = '150';
            }
            //see BT#2020
            /*$json .= "$(window).bind('resize', function() {
                $('#".$div_id."').setGridWidth($(window).width() - ".$width_fix.");
            }).trigger('resize');";*/
        }

        //Creating the jqgrid element
        $json .= '$("#'.$div_id.'").jqGrid(';
        $json .= $json_encode;
        $json .= ');';

        $all_text = addslashes(get_lang('All'));
        $json .= '$("'.$obj->pager.' option[value='.$all_value.']").text("'.$all_text.'");';

        $json.="\n";

        //Adding edit/delete icons
        $json.=$formatter;

        return $json;

        /*
        Real Example
        $("#list_week").jqGrid({
            url:'<?php echo api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_by_week&session_id='.$session_id; ?>',
            datatype: 'json',
            colNames:['Week','Date','Course', 'LP'],
            colModel :[
              {name:'week',     index:'week',   width:120, align:'right'},
              {name:'date',     index:'date',   width:120, align:'right'},
              {name:'course',   index:'course', width:150},
              {name:'lp',       index:'lp',     width:250}
            ],
            pager: '#pager3',
            rowNum:100,
             rowList:[10,20,30],
            sortname: 'date',
            sortorder: 'desc',
            viewrecords: true,
            grouping:true,
            groupingView : {
                groupField : ['week'],
                groupColumnShow : [false],
                groupText : ['<b>Week {0} - {1} Item(s)</b>']
            }
        });  */
    }

    public static function table($headers, $rows, $attributes = array()) {

    	if (empty($attributes)) {
    		$attributes['class'] = 'data_table';
    	}
        //require_once api_get_path(LIBRARY_PATH).'pear/HTML/Table.php';
        $table = new HTML_Table($attributes);
        $row = 0;
        $column = 0;

        //Course headers
        if (!empty($headers)) {
	        foreach ($headers as $item) {
	            $table->setHeaderContents($row, $column, $item);
	            $column++;
	        }
	        $row = 1;
	        $column = 0;
        }

        if (!empty($rows)) {
	        foreach($rows as $content) {
	            $table->setCellContents($row, $column, $content);
                $row++;
                    //$column++;
                }
        }
        return $table->toHtml();
    }

    /**
     * Returns the "what's new" icon notifications
     *
     * The general logic of this function is to track the last time the user
     * entered the course and compare to what has changed inside this course
     * since then, based on the item_property table inside this course. Note that,
     * if the user never entered the course before, he will not see notification
     * icons. This function takes session ID into account (if any) and only shows
     * the corresponding notifications.
     * @param array     Course information array, containing at least elements 'db' and 'k'
     * @return string   The HTML link to be shown next to the course
     */
    public static function show_notification($course_info) {
        $t_track_e_access 	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
        $user_id = api_get_user_id();

        $course_tool_table	= Database::get_course_table(TABLE_TOOL_LIST);
        $tool_edit_table 	= Database::get_course_table(TABLE_ITEM_PROPERTY);

        $course_code        = Database::escape_string($course_info['code']);
        $course_id          = $course_info['real_id'];

        $course_info['id_session'] = intval($course_info['id_session']);
        // Get the user's last access dates to all tools of this course
        $sqlLastTrackInCourse = "SELECT * FROM $t_track_e_access USE INDEX (access_cours_code, access_user_id)
                                 WHERE  access_cours_code = '".$course_code."' AND
                                        access_user_id = '$user_id' AND
                                        access_session_id ='".$course_info['id_session']."'";
        $resLastTrackInCourse = Database::query($sqlLastTrackInCourse);

        $oldestTrackDate = $oldestTrackDateOrig = '3000-01-01 00:00:00';
        while ($lastTrackInCourse = Database::fetch_array($resLastTrackInCourse)) {
            $lastTrackInCourseDate[$lastTrackInCourse['access_tool']] = $lastTrackInCourse['access_date'];
            if ($oldestTrackDate > $lastTrackInCourse['access_date']) {
                $oldestTrackDate = $lastTrackInCourse['access_date'];
            }
        }
        if ($oldestTrackDate == $oldestTrackDateOrig) {
            //if there was no connexion to the course ever, then take the
            // course creation date as a reference
            $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
            $sql = "SELECT course.creation_date ".
                 "FROM $course_table course ".
                 "WHERE course.code = '".$course_code."'";
            $res = Database::query($sql);
            if ($res && Database::num_rows($res)>0) {
                $row = Database::fetch_array($res);
            }
            $oldestTrackDate = $row['creation_date'];
        }

        // Get the last edits of all tools of this course.
        $sql = "SELECT tet.*, tet.lastedit_date last_date, tet.tool tool, tet.ref ref, ".
                            " tet.lastedit_type type, tet.to_group_id group_id, ".
                            " ctt.image image, ctt.link link ".
                        " FROM $tool_edit_table tet, $course_tool_table ctt ".
                        " WHERE tet.c_id = $course_id AND
                        		ctt.c_id = $course_id AND
                        		tet.lastedit_date > '$oldestTrackDate' ".
                        " AND ctt.name = tet.tool ".
                        " AND ctt.visibility = '1' ".
                        " AND tet.lastedit_user_id != $user_id AND tet.id_session = '".$course_info['id_session']."' ".
                        " ORDER BY tet.lastedit_date";
        $res = Database::query($sql);
        // Get the group_id's with user membership.
        $group_ids = GroupManager :: get_group_ids($course_info['real_id'], $user_id);
        $group_ids[] = 0; //add group 'everyone'
        $notifications = array();
        // Filter all last edits of all tools of the course
        while ($res && ($item_property = Database::fetch_array($res))) {
            // First thing to check is if the user never entered the tool
            // or if his last visit was earlier than the last modification.
            if ((!isset ($lastTrackInCourseDate[$item_property['tool']])
                 || $lastTrackInCourseDate[$item_property['tool']] < $item_property['lastedit_date'])
                // Drop the tool elements that are part of a group that the
                // user is not part of.
                && ((in_array($item_property['to_group_id'], $group_ids)
                // Drop the dropbox, notebook and chat tools (we don't care)
                && ($item_property['tool'] != TOOL_DROPBOX
                      && $item_property['tool'] != TOOL_NOTEBOOK
                      && $item_property['tool'] != TOOL_CHAT)
                   )
                  )
                // Take only what's visible or invisible but where the user is a teacher or where the visibility is unset.
                && ($item_property['visibility'] == '1'
                    || ($course_info['status'] == '1' && $item_property['visibility'] == '0')
                    || !isset($item_property['visibility'])))
            {
                // Also drop announcements and events that are not for the user or his group.
                if (($item_property['tool'] == TOOL_ANNOUNCEMENT
                         || $item_property['tool'] == TOOL_CALENDAR_EVENT)
                   && (($item_property['to_user_id'] != $user_id )
                         && (!isset($item_property['to_group_id'])
                             || !in_array($item_property['to_group_id'], $group_ids)))) {
                   continue;
                }
                // If it's a survey, make sure the user's invited. Otherwise drop it.
                if ($item_property['tool'] == TOOL_SURVEY) {
                    $survey_info = survey_manager::get_survey($item_property['ref'], 0, $course_code);
                    $invited_users = SurveyUtil::get_invited_users($survey_info['code'], $course_code);
                    if (!in_array($user_id, $invited_users['course_users'])) continue;
                }
                // If it's a learning path, ensure it is currently visible to the user
                if ($item_property['tool'] == TOOL_LEARNPATH) {
                    require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
                    if (!learnpath::is_lp_visible_for_student($item_property['ref'],$user_id, $course_code)) {
                        continue;
                    }

                }
                $notifications[$item_property['tool']] = $item_property;
            }
        }
        // Show all tool icons where there is something new.
        $retvalue = '&nbsp;';
        while (list($key, $notification) = each($notifications)) {
            $lastDate = date('d/m/Y H:i', convert_sql_date($notification['lastedit_date']));
            $type = $notification['lastedit_type'];
            if (empty($course_info['id_session'])) {
                $my_course['id_session'] = 0;
            } else {
                $my_course['id_session'] = $course_info['id_session'];
            }
            $label = get_lang('TitleNotification').": ".get_lang($type)." ($lastDate)";
            $retvalue .= '<a href="'.api_get_path(WEB_CODE_PATH).$notification['link'].'?cidReq='.$course_code.'&amp;ref='.$notification['ref'].'&amp;gidReq='.$notification['to_group_id'].'&amp;id_session='.$my_course['id_session'].'">'.
                            Display::return_icon($notification['image'], $label).'</a>&nbsp;';
        }
        return $retvalue;
    }

    /**
     * Displays a digest e.g. short summary of new agenda and announcements items.
     * This used to be displayed in the right hand menu, but is now
     * disabled by default (see config settings in this file) because most people like
     * the what's new icons better.
     *
     * @version 1.0
     */
    public static function display_digest($toolsList, $digest, $orderKey, $courses) {
        $html = '';
        if (is_array($digest) && (CONFVAL_showExtractInfo == SCRIPTVAL_UnderCourseList || CONFVAL_showExtractInfo == SCRIPTVAL_Both)) {
            // // // LEVEL 1 // // //
            reset($digest);
            $html .= "<br /><br />\n";
            while (list($key1) = each($digest)) {
                if (is_array($digest[$key1])) {
                    // // // Title of LEVEL 1 // // //
                    $html .= "<strong>\n";
                    if ($orderKey[0] == 'keyTools') {
                        $tools = $key1;
                        $html .= $toolsList[$key1]['name'];
                    } elseif ($orderKey[0] == 'keyCourse') {
                        $courseSysCode = $key1;
                        $html .= "<a href=\"".api_get_path(WEB_COURSE_PATH). $courses[$key1]['coursePath']. "\">".$courses[$key1]['courseCode']. "</a>\n";
                    } elseif ($orderKey[0] == 'keyTime') {
                        $html .= api_convert_and_format_date($digest[$key1], DATE_FORMAT_LONG, date_default_timezone_get());
                    }
                    $html .= "</strong>\n";
                    // // // End Of Title of LEVEL 1 // // //
                    // // // LEVEL 2 // // //
                    reset($digest[$key1]);
                    while (list ($key2) = each($digest[$key1])) {
                        // // // Title of LEVEL 2 // // //
                        $html .= "<p>\n". "\n";
                        if ($orderKey[1] == 'keyTools') {
                            $tools = $key2;
                            $html .= $toolsList[$key2][name];
                        } elseif ($orderKey[1] == 'keyCourse') {
                            $courseSysCode = $key2;
                            $html .= "<a href=\"". api_get_path(WEB_COURSE_PATH). $courses[$key2]['coursePath']. "\">". $courses[$key2]['courseCode']. "</a>\n";
                        } elseif ($orderKey[1] == 'keyTime') {
                            $html .= api_convert_and_format_date($key2, DATE_FORMAT_LONG, date_default_timezone_get());
                        }
                        $html .= "\n";
                        $html .= "</p>";
                        // // // End Of Title of LEVEL 2 // // //
                        // // // LEVEL 3 // // //
                        reset($digest[$key1][$key2]);
                        while (list ($key3, $dataFromCourse) = each($digest[$key1][$key2])) {
                            // // // Title of LEVEL 3 // // //
                            if ($orderKey[2] == 'keyTools') {
                                $level3title = "<a href=\"".$toolsList[$key3]["path"].$courseSysCode."\">".$toolsList[$key3]['name']."</a>";
                            } elseif ($orderKey[2] == 'keyCourse') {
                                $level3title = "&#8226; <a href=\"".$toolsList[$tools]["path"].$key3."\">".$courses[$key3]['courseCode']."</a>\n";
                            } elseif ($orderKey[2] == 'keyTime') {
                                $level3title = "&#8226; <a href=\"".$toolsList[$tools]["path"].$courseSysCode."\">".api_convert_and_format_date($key3, DATE_FORMAT_LONG, date_default_timezone_get())."</a>";
                            }
                            // // // End Of Title of LEVEL 3 // // //
                            // // // LEVEL 4 (data) // // //
                            reset($digest[$key1][$key2][$key3]);
                            while (list ($key4, $dataFromCourse) = each($digest[$key1][$key2][$key3])) {
                                $html .= $level3title. ' &ndash; '. api_substr(strip_tags($dataFromCourse), 0, CONFVAL_NB_CHAR_FROM_CONTENT);
                                //adding ... (three dots) if the texts are too large and they are shortened
                                if (api_strlen($dataFromCourse) >= CONFVAL_NB_CHAR_FROM_CONTENT) {
                                    $html .= '...';
                                }
                            }
                            $html .= "<br />\n";
                        }
                    }
                }
            }
            return $html;
        }
    } // End function display_digest

    /**
     * Get the session box details as an array
     * @param int       Session ID
     * @return array    Empty array or session array ['title'=>'...','category'=>'','dates'=>'...','coach'=>'...','active'=>true/false,'session_category_id'=>int]
     */
    public static function get_session_title_box($session_id) {
        global $nosession;

        if (!$nosession) {
            global $now, $date_start, $date_end;
        }

        $output = array();
        if (!$nosession) {
            $main_user_table        = Database :: get_main_table(TABLE_MAIN_USER);
            $tbl_session            = Database :: get_main_table(TABLE_MAIN_SESSION);
            $active = false;
            // Request for the name of the general coach
            $sql ='SELECT tu.lastname, tu.firstname, ts.name, ts.date_start, ts.date_end, ts.session_category_id
                    FROM '.$tbl_session.' ts  LEFT JOIN '.$main_user_table .' tu ON ts.id_coach = tu.user_id
                    WHERE ts.id='.intval($session_id);
            $rs = Database::query($sql);
            $session_info = Database::store_result($rs);
            $session_info = $session_info[0];
            $session = array();
            $session['title'] = $session_info[2];
            $session['coach'] = '';
            $session['dates'] =  '';

            if ($session_info['date_end'] == '0000-00-00' && $session_info['date_start'] == '0000-00-00') {
                if (api_get_setting('show_session_coach') === 'true') {
                    $session['coach'] = get_lang('GeneralCoach').': '.api_get_person_name($session_info[1], $session_info[0]);
                }
                $active = true;
            } else {
                $start = $stop = false;
                $start_buffer = $stop_buffer = '';
                if ($session_info['date_start'] == '0000-00-00') {
                    $session_info['date_start'] = '';
                } else {
                    $start = true;
                    $start_buffer = $session_info['date_start'];
                    $session_info['date_start'] = get_lang('From').' '.$session_info['date_start'];
                }
                if ($session_info['date_end'] == '0000-00-00') {
                    $session_info['date_end'] = '';
                } else {
                    $stop = true;
                    $stop_buffer = $session_info['date_end'];
                    $session_info['date_end'] = get_lang('Until').' '.$session_info['date_end'];
                }
                if ($start && $stop) {
                    $session['dates'] = Display::tag('i', sprintf(get_lang('FromDateXToDateY'), $start_buffer, $stop_buffer));
                } else {
                    $session['dates'] = Display::tag('i', $session_info['date_start'].' '.$session_info['date_end']);
                }

                if ( api_get_setting('show_session_coach') === 'true' ) {
                    $session['coach'] = get_lang('GeneralCoach').': '.api_get_person_name($session_info[1], $session_info[0]);
                }
                $active = ($date_start <= $now && $date_end >= $now);
            }
            $session['active'] = $active;
            $session['session_category_id'] = $session_info[5];
            $output = $session;
        }
        return $output;
    }

    /**
     * Return the five star HTML
     *
     * @param  string  id of the rating ul element
     * @param  string  url that will be added (for jquery see hot_courses.tpl)
	 * @param	string	point info array see function CourseManager::get_course_ranking()
	 * @param	bool	add a div wrapper
	 * @todo	use     templates
     **/
    public static function return_rating_system($id, $url, $point_info = array(), $add_div_wrapper = true) {
		$number_of_users_who_voted =  isset($point_info['users_who_voted']) ? $point_info['users_who_voted'] : null;

		$percentage =  isset($point_info['point_average']) ? $point_info['point_average'] : 0;

		if (!empty($percentage)) {
            $percentage = $percentage*125/100;
        }
		$accesses =  isset($point_info['accesses']) ? $point_info['accesses'] : 0;

		$star_label = sprintf(get_lang('XStarsOutOf5'), $point_info['point_average_star']);

        $html = '<ul id="'.$id.'" class="star-rating">
					<li class="current-rating" style="width:'.$percentage.'px;"></li>
					<li><a href="javascript:void(0);" data-link="'.$url.'&amp;star=1" title="'.$star_label.'" class="one-star">1</a></li>
					<li><a href="javascript:void(0);" data-link="'.$url.'&amp;star=2" title="'.$star_label.'" class="two-stars">2</a></li>
					<li><a href="javascript:void(0);" data-link="'.$url.'&amp;star=3" title="'.$star_label.'" class="three-stars">3</a></li>
					<li><a href="javascript:void(0);" data-link="'.$url.'&amp;star=4" title="'.$star_label.'" class="four-stars">4</a></li>
					<li><a href="javascript:void(0);" data-link="'.$url.'&amp;star=5" title="'.$star_label.'" class="five-stars">5</a></li>
				</ul>';

		$labels = array();

		$labels[]= $number_of_users_who_voted == 1 ? $number_of_users_who_voted.' '.get_lang('Vote') : $number_of_users_who_voted.' '.get_lang('Votes');
		$labels[]= $accesses == 1 ? $accesses.' '.get_lang('Visit') : $accesses.' '.get_lang('Visits');
		if (!empty($number_of_users_who_voted)) {
			$labels[]= get_lang('Average').' '.$point_info['point_average_star'].'/5';
		}

		$labels[]= $point_info['user_vote']  ? get_lang('YourVote').' ['.$point_info['user_vote'].']' : get_lang('YourVote'). ' [?] ';

		if (!$add_div_wrapper && api_is_anonymous()) {
			$labels[]= Display::tag('span', get_lang('LoginToVote'), array('class' => 'error'));
		}

        $html .= Display::span(implode(' | ', $labels) , array('id' =>  'vote_label_'.$id, 'class' => 'vote_label_info'));
        $html .= ' '.Display::span(' ', array('id' =>  'vote_label2_'.$id));

        if ($add_div_wrapper) {
			$html = Display::div($html, array('id' => 'rating_wrapper_'.$id));
		}
        return $html;
    }

    public static function return_default_table_class() {
        return 'data_table';
    }

    public static function page_header($title, $second_title = null, $size = 'h1') {
        $title = Security::remove_XSS($title);
        if (!empty($second_title)) {
            $second_title = Security::remove_XSS($second_title);
            $title .= "<small> $second_title<small>";
        }
        return '<div class="page-header"><'.$size.'>'.$title.'</'.$size.'></div>';
    }

    public static function page_header_and_translate($title, $second_title = null) {
        $title = get_lang($title);
        return self::page_header($title, $second_title);
    }

     public static function page_subheader_and_translate($title, $second_title = null) {
        $title = get_lang($title);
        return self::page_subheader($title, $second_title);
    }

    public static function page_subheader($title, $second_title = null) {
        if (!empty($second_title)) {
            $second_title = Security::remove_XSS($second_title);
            $title .= "<small> $second_title<small>";
        }
        return '<div class="page-header"><h2>'.Security::remove_XSS($title).'</h2></div>';
    }

    public static function page_subheader2($title, $second_title = null) {
        return self::page_header($title, $second_title, 'h3');
    }

    public static function page_subheader3($title, $second_title = null) {
        return self::page_header($title, $second_title, 'h4');
    }

    public static function description($list) {
        $html = null;
        if (!empty($list)) {
            $html = '<dl class="dl-horizontal">';
            foreach ($list as $item) {
                $html .= '<dt>'.$item['title'].'</dd>';
                $html .= '<dd>'.$item['content'].'</dt>';
            }
            $html .= '</dl>';
        }
        return $html;
    }

    public static function bar_progress($percentage, $show_percentage = true, $extra_info = null) {
        $percentage = intval($percentage);
        $div = '<div class="progress progress-striped">
                    <div class="bar" style="width: '.$percentage.'%;"></div>
                </div>';
        if ($show_percentage) {
            $div .= '<div class="progresstext">'.$percentage.'%</div>';
        } else {
            if (!empty($extra_info)) {
                $div .= '<div class="progresstext">'.$extra_info.'</div>';
            }
        }
        return $div;
    }

    public static function badge($count, $type ="warning") {
        $class = '';

        switch ($type) {
            case 'success':
                $class = 'badge-success';
                break;
            case 'warning':
                $class = 'badge-warning';
                break;
            case 'important':
                $class = 'badge-important';
                break;
            case 'info':
                $class = 'badge-info';
                break;
            case 'inverse':
                $class = 'badge-inverse';
                break;
        }

        if (!empty($count)) {
            return ' <span class="badge '.$class.'">'.$count.'</span>';
        }
        return null;
    }

    public static function badge_group($badge_list) {
        $html = '<div class="badge-group">';
        foreach ($badge_list as $badge) {
            $html .= $badge;
        }
        $html .= '</div>';
        return $html;
    }

    public static function label($content, $type = null) {
        $class = '';
        switch ($type) {
            case 'success':
                $class = 'label-success';
                break;
            case 'warning':
                $class = 'label-warning';
                break;
            case 'important':
                $class = 'label-important';
                break;
            case 'info':
                $class = 'label-info';
                break;
            case 'inverse':
                $class = 'label-inverse';
                break;
        }

        $html = '';
        if (!empty($content)) {
            $html = '<span class="label '.$class.'">';
            $html .= $content;
            $html .='</span>';
        }
        return $html;
    }

    public static function actions($items) {
        $html = null;
        if (!empty($items)) {
            $html = '<div class="new_actions"><ul class="nav nav-pills">';
            foreach ($items as $value) {
                $class = null;
                if (isset($value['active']) && $value['active']) {
                    $class = 'class ="active"';
                }
                $html .= "<li $class >";
                $html .= self::url($value['content'], $value['url']);
                $html .= '</li>';
            }
            $html .= '</ul></div>';
        }
        return $html;
    }

    /**
     * Prints a tooltip
     */
    public static function tip($text, $tip)  {
        if (empty($tip)) {
            return $text;
        }
        return self::span($text, array('class' => 'boot-tooltip', 'title' => strip_tags($tip)));
    }

    public static function generate_accordion($items, $type = 'jquery', $id = null) {
        $html = null;
        if (!empty($items)) {
            if (empty($id)) {
                $id = api_get_unique_id();
            }
            if ($type == 'jquery') {
                $html = '<div class="accordion_jquery" id="'.$id.'">'; //using jquery
            } else {
                $html = '<div class="accordion" id="'.$id.'">'; //using bootstrap
            }

            $count = 1;
            foreach ($items as $item) {
                $html .= '<div class="accordion-my-group">';
                $html .= '<div class="accordion-heading">
                                <a class="accordion-toggle" data-toggle="collapse" data-parent="#'.$id.'" href="#collapse'.$count.'">
                                '.$item['title'].'
                                </a>
                          </div>';
                //$html .= '<div id="collapse'.$count.'" class="accordion-body collapse in">
                $html .= '<div id="collapse'.$count.'" class="accordion-body">';

                //$html .= '<div class="accordion-inner">
                $html .= '<div class="accordion-my-inner">
                            '.$item['content'].'
                            </div>
                          </div>';
            }
            $html .= '</div>';
        }
        return $html;
    }

    /**
     * @todo use twig
     */
    static function group_button($title, $elements) {
        $html = '<div class="btn-toolbar">
            <div class="btn-group">
            <button class="btn dropdown-toggle" data-toggle="dropdown">'.$title.' <span class="caret"></span></button>
            <ul class="dropdown-menu">';
        foreach ($elements as $item) {
            $html .= Display::tag('li', Display::url($item['title'], $item['href']));
        }
        $html .= '</ul>
            </div> </div>';
        return $html;
    }
} //end class Display
