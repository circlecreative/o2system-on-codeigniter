<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * O2System
 *
 * An open source HMVC Third Party Plugin for CodeIgniter v2.0
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014, PT. Lingkar Kreasi (Circle Creative).
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package        O2System
 * @author         Steeven Andrian Salim
 * @copyright      Copyright (c) 2005 - 2014, PT. Lingkar Kreasi (Circle Creative).
 * @license        http://circle-creative.com/products/o2system/license.html
 * @license        http://opensource.org/licenses/MIT	MIT License
 * @link           http://circle-creative.com
 * @since          Version 2.0
 * @filesource
 */
// ------------------------------------------------------------------------

/**
 * System Initialization
 *
 * Loads the base classes and executes the request.
 * The system Initialization sequence concept process is based on CodeIgniter 3.0-dev
 *
 * @package        O2System
 * @subpackage    system/core
 * @category    Bootstrap
 * @author        Steeven Andrian Salim
 * @link        http://circle-creative.com/products/o2system/user-guide/core/system.html
 */

/**
 * System Version
 * @var string
 */
    define('SYSTEM_VERSION', '2.0');

/**
 * System Path
 * @var string
 */
    define('SYSPATH', APPPATH . 'third_party/o2system/');

/*
 * ------------------------------------------------------
 *  Load the O2System global functions
 * ------------------------------------------------------
 */
    require(SYSPATH . 'core/Common.php');

/*
 * ------------------------------------------------------
 *  Load the System Class
 * ------------------------------------------------------
 */
    require_once SYSPATH . 'core/System.php';

/*
 * ------------------------------------------------------
 *  Load the Developer functions
 * ------------------------------------------------------
 */
    require_once SYSPATH . 'core/Developer.php';

/*
 * ------------------------------------------------------
 *  Instantiate the System Class
 * ------------------------------------------------------
 */
    $O2 = new O2System\Core();

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * System Initialization File
 *
 * Loads the base classes and executes the request.
 *
 * @package		CodeIgniter
 * @subpackage	codeigniter
 * @category	Front-controller
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/
 */

/**
 * CodeIgniter Version
 *
 * @var string
 *
 */
    define('CI_VERSION', '2.2.0');

/**
 * CodeIgniter Branch (Core = TRUE, Reactor = FALSE)
 *
 * @var boolean
 *
 */
    define('CI_CORE', FALSE);

/*
 * ------------------------------------------------------
 *  Load the CodeIgniter global functions
 * ------------------------------------------------------
 */
    require(BASEPATH.'core/Common.php');

/*
 * ------------------------------------------------------
 *  Load the framework constants
 * ------------------------------------------------------
 */
    if (defined('ENVIRONMENT') AND file_exists(APPPATH.'config/'.ENVIRONMENT.'/constants.php'))
    {
        require(APPPATH.'config/'.ENVIRONMENT.'/constants.php');
    }
    else
    {
        require(APPPATH.'config/constants.php');
    }

/*
 * ------------------------------------------------------
 *  Define a custom error handler so we can log PHP errors
 * ------------------------------------------------------
 */
    set_error_handler('_exception_handler');

    if ( ! is_php('5.3'))
    {
        @set_magic_quotes_runtime(0); // Kill magic quotes
    }

/*
 * ------------------------------------------------------
 *  Set the subclass_prefix
 * ------------------------------------------------------
 *
 * Normally the "subclass_prefix" is set in the config file.
 * The subclass prefix allows CI to know if a core class is
 * being extended via a library in the local application
 * "libraries" folder. Since CI allows config items to be
 * overriden via data set in the main index. php file,
 * before proceeding we need to know if a subclass_prefix
 * override exists.  If so, we will set this value now,
 * before any classes are loaded
 * Note: Since the config file data is cached it doesn't
 * hurt to load it here.
 */
    if (isset($assign_to_config['subclass_prefix']) AND $assign_to_config['subclass_prefix'] != '')
    {
        get_config(array('subclass_prefix' => $assign_to_config['subclass_prefix']));
    }

/*
 * ------------------------------------------------------
 *  Set a liberal script execution time limit
 * ------------------------------------------------------
 */
    if (function_exists("set_time_limit") == TRUE AND @ini_get("safe_mode") == 0)
    {
        @set_time_limit(300);
    }

/*
 * ------------------------------------------------------
 *  Start the timer... tick tock tick tock...
 * ------------------------------------------------------
 */
    $BM =& \O2System\load_class('Benchmark', 'core');
    $BM->mark('total_execution_time_start');
    $BM->mark('loading_time:_base_classes_start');
    //print_out($BM);

/*
 * ------------------------------------------------------
 *  Instantiate the hooks class
 * ------------------------------------------------------
 */
    $EXT =& \O2System\load_class('Hooks', 'core');

/*
 * ------------------------------------------------------
 *  Is there a "pre_system" hook?
 * ------------------------------------------------------
 */
    $EXT->_call_hook('pre_system');
    //print_out($EXT);

/*
 * ------------------------------------------------------
 *  Instantiate the config class
 * ------------------------------------------------------
 */
    $CFG =& \O2System\load_class('Config', 'core');
    //print_out($CFG);

    // Do we have any manually set config items in the index.php file?
    if (isset($assign_to_config))
    {
        $CFG->_assign_to_config($assign_to_config);
    }

/*
 * ------------------------------------------------------
 *  Instantiate the UTF-8 class
 * ------------------------------------------------------
 *
 * Note: Order here is rather important as the UTF-8
 * class needs to be used very early on, but it cannot
 * properly determine if UTf-8 can be supported until
 * after the Config class is instantiated.
 *
 */

    $UNI =& \O2System\load_class('Utf8', 'core');
    //print_out($UNI);

/*
 * ------------------------------------------------------
 *  Instantiate the URI class
 * ------------------------------------------------------
 */
    $URI =& \O2System\load_class('URI', 'core');
    //print_out($URI);

/*
 * ------------------------------------------------------
 *  Instantiate the routing class and set the routing
 * ------------------------------------------------------
 */
    $RTR =& \O2System\load_class('Router', 'core');
    $RTR->_set_routing();

    // Set any routing overrides that may exist in the main index file
    if (isset($routing))
    {
        $RTR->_set_overrides($routing);
    }

    //print_out($RTR);

/*
 * ------------------------------------------------------
 *  Instantiate the output class
 * ------------------------------------------------------
 */
    $OUT =& \O2System\load_class('Output', 'core');
    //print_out($OUT);

/*
 * ------------------------------------------------------
 *	Is there a valid cache file?  If so, we're done...
 * ------------------------------------------------------
 */
    if ($EXT->_call_hook('cache_override') === FALSE)
    {
        if ($OUT->_display_cache($CFG, $URI) == TRUE)
        {
            exit;
        }
    }
    //print_out($EXT);

/*
 * -----------------------------------------------------
 * Load the security class for xss and csrf support
 * -----------------------------------------------------
 */
    $SEC =& \O2System\load_class('Security', 'core');
    //print_out($SEC);

/*
 * ------------------------------------------------------
 *  Load the Input class and sanitize globals
 * ------------------------------------------------------
 */
    $IN	=& \O2System\load_class('Input', 'core');
    //print_out($IN);
/*
 * ------------------------------------------------------
 *  Load the Language class
 * ------------------------------------------------------
 */
    $LANG =& \O2System\load_class('Lang', 'core');

/*
 * Borrowed concept from CodeIgniter 3.0-dev
 * ------------------------------------------------------
 *  Load the app controller and local controller
 * ------------------------------------------------------
 *
 */
    function &get_instance()
    {
        return \O2System\Controller::get_instance();
    }

    $controller = \O2System\Request::controller()->class_name;
    $method = \O2System\Request::method();

/*
 * ------------------------------------------------------
 *  Is there a "pre_controller" hook?
 * ------------------------------------------------------
 */
    $EXT->_call_hook('pre_controller');

/*
 * ------------------------------------------------------
 *  Instantiate the requested controller
 * ------------------------------------------------------
 */
    // Mark a start point so we can benchmark the controller
    $BM->mark('controller_execution_time_( '.$controller.' / '.$method.' )_start');
    $CI = new $controller();

/*
 * ------------------------------------------------------
 *  Is there a "post_controller_constructor" hook?
 * ------------------------------------------------------
 */
    $EXT->_call_hook('post_controller_constructor');
/*
 * ------------------------------------------------------
 *  Call the requested method
 * ------------------------------------------------------
 */
    $params = \O2System\Request::params();

    call_user_func_array(array(&$CI, $method), $params);

    // Mark a benchmark end point
    $BM->mark('controller_execution_time_( '.$controller.' / '.$method.' )_end');

/*
 * ------------------------------------------------------
 *  Is there a "post_controller" hook?
 * ------------------------------------------------------
 */
    $EXT->_call_hook('post_controller');

/*
 * ------------------------------------------------------
 *  Send the final rendered output to the browser
 * ------------------------------------------------------
 */
    if ($EXT->_call_hook('display_override') === FALSE)
    {
        $OUT->_display();
    }

/*
 * ------------------------------------------------------
 *  Is there a "post_system" hook?
 * ------------------------------------------------------
 */
    $EXT->_call_hook('post_system');

/*
 * ------------------------------------------------------
 *  Close the DB connection if one exists
 * ------------------------------------------------------
 */
    if (class_exists('CI_DB') AND isset($CI->db))
    {
        $CI->db->close();
    }

/* End of file O2System.php */
/* Location: ./o2system/core/O2System.php */

