<?php
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

namespace O2System;
require_once BASEPATH.'core/Router.php';
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * O2System HMVC Router
 *
 * This library extends the CodeIgniter router class.
 *
 * @package        O2System
 * @subpackage     O2System
 * @category       Core Class
 * @author         Steeven Andrian Salim
 * @link           http://circle-creative.com/products/o2system-plugin/user-guide/router.html
 */
// ------------------------------------------------------------------------

class Router extends \CI_Router
{
    public function __construct()
    {
        $this->config =& \O2System\load_class('Config', 'core');
        $this->uri =& \O2System\load_class('URI', 'core');
        log_message('debug', "Router Class Initialized");
    }

    /**
     * Set the route mapping
     *
     * This function determines what should be served based on the URI request,
     * as well as any "routes" that have been set in the routing config file.
     *
     * @access	private
     * @return	void
     */
    function _set_routing()
    {
        // Are query strings enabled in the config file?  Normally CI doesn't utilize query strings
        // since URI segments are more search-engine friendly, but they can optionally be used.
        // If this feature is enabled, we will gather the directory/class/method a little differently
        $segments = array();
        if ($this->config->item('enable_query_strings') === TRUE AND isset($_GET[$this->config->item('controller_trigger')]))
        {
            if (isset($_GET[$this->config->item('directory_trigger')]))
            {
                $this->set_directory(trim($this->uri->_filter_uri($_GET[$this->config->item('directory_trigger')])));
                $segments[] = $this->fetch_directory();
            }

            if (isset($_GET[$this->config->item('controller_trigger')]))
            {
                $this->set_class(trim($this->uri->_filter_uri($_GET[$this->config->item('controller_trigger')])));
                $segments[] = $this->fetch_class();
            }

            if (isset($_GET[$this->config->item('function_trigger')]))
            {
                $this->set_method(trim($this->uri->_filter_uri($_GET[$this->config->item('function_trigger')])));
                $segments[] = $this->fetch_method();
            }
        }

        $path = APPPATH;
        defined(BASEAPP) OR $path = APPPATH.BASEAPP.'/';

        // Load the routes.php file.
        if (defined('ENVIRONMENT') AND is_file($path.'config/'.ENVIRONMENT.'/routes.php'))
        {
            include($path.'config/'.ENVIRONMENT.'/routes.php');
        }
        elseif (is_file($path.'config/routes.php'))
        {
            include($path.'config/routes.php');
        }

        $this->routes = ( ! isset($route) OR ! is_array($route)) ? array() : $route;

        unset($route);

        // Set the default controller so we can display it in the event
        // the URI doesn't correlated to a valid controller.
        $this->default_controller = ( ! isset($this->routes['default_controller']) OR $this->routes['default_controller'] == '') ? FALSE : strtolower($this->routes['default_controller']);

        // Were there any query string segments?  If so, we'll validate them and bail out since we're done.
        if (count($segments) > 0)
        {
            return $this->_validate_request($segments);
        }

        // Fetch the complete URI string
        $this->uri->_fetch_uri_string();

        // Is there a URI string? If not, the default controller specified in the "routes" file will be shown.
        if ($this->uri->uri_string == '')
        {
            return $this->_set_default_controller();
        }

        // Do we need to remove the URL suffix?
        $this->uri->_remove_url_suffix();

        // Compile the segments into an array
        $this->uri->_explode_segments();

        // Parse any custom routing that may exist
        $this->_parse_routes();

        // Re-index the segment array so that it starts with 1 rather than 0
        $this->uri->_reindex_segments();
    }

    // --------------------------------------------------------------------

    public function _validate_request($segments)
    {
        $path = APPPATH;
        defined(BASEAPP) OR $path = APPPATH . BASEAPP . '/';

        if (Segments::is_app($segments[0]))
        {
            $path = APPPATH . $segments[0] . '/';
        }

        // Load the routes.php file.
        if (defined('ENVIRONMENT') AND is_file($path . 'config/' . ENVIRONMENT . '/routes.php'))
        {
            include($path . 'config/' . ENVIRONMENT . '/routes.php');
        }
        elseif (is_file($path . 'config/routes.php'))
        {
            include($path . 'config/routes.php');
        }

        $this->routes = (!isset($route) OR !is_array($route)) ? array() : $route;
        unset($route);

        // Set the default controller so we can display it in the event
        // the URI doesn't correlated to a valid controller.
        $this->default_controller = (!isset($this->routes['default_controller']) OR $this->routes['default_controller'] == '') ? FALSE : strtolower($this->routes['default_controller']);

        if (strpos($this->default_controller, '/') !== FALSE)
        {
            $default_segments = explode('/', $this->default_controller);
        }
        else
        {
            $default_segments = $this->default_controller;
        }

        if (Segments::is_app($segments[0]) AND count($segments) == 1)
        {
            if(is_string($default_segments))
            {
                array_push($segments, $default_segments);
            }
            else
            {
                $segments = array_merge($segments, $default_segments);
            }
        }

        $request = $this->uri->_validate_segments($segments);

        $controller = Request::controllers();

        //print_out($controller);
        $controller = end($controller);

        Request::register($controller,'controller');
        //print_out($controller);
        $this->directory = $controller->path;

        return Segments::request();
    }
}