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
require_once BASEPATH.'core/URI.php';

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * O2System HMVC URI
 *
 * This library extends the CodeIgniter URI class.
 *
 * @package        O2System
 * @subpackage     O2System
 * @category       Core Class
 * @author         Steeven Andrian Salim
 * @link           http://circle-creative.com/products/o2system-plugin/user-guide/uri.html
 */
// ------------------------------------------------------------------------

class URI extends \CI_URI
{
    public $controllers = array();

    public function __construct()
    {
        parent::__construct();

        // Set Core Controller
        $registry = new Registry\Core(SYSPATH.'core/Controller.php');
        $this->controllers['system'] = $registry;

        if(file_exists(APPPATH . 'core/App_Controller.php'))
        {
            $registry = new Registry\Core(APPPATH . 'core/App_Controller.php');
            $this->controllers['application'] = $registry;
        }

        // Create New Request
        new Request();
    }

    /**
     * Validate requested URI Segments
     *
     * @access	public
     * @param   $segments Array of URI segments
     * @return	boolean
     */
    public function _validate_segments($segments)
    {
        Segments::register($segments, 'raw');

        // Validate with removal
        foreach(array('app','lang','attribute') as $method)
        {
            $validity_method = 'is_'.$method.'_request';
            if(method_exists($this, $validity_method))
            {
                if($this->$validity_method($segments))
                {
                    array_shift($segments);
                }
            }
        }

        if(! empty($segments))
        {
            // Validate Login Request
            if ($this->is_login_request($segments))
            {
                array_unshift($segments, 'login');
            }

            // Validate Page Request
            if (method_exists($this, 'is_page_request'))
            {
                $this->is_page_request($segments);
            }

            // Validate without removal
            foreach (array('module', 'controller', 'method') as $method)
            {
                $validity_method = 'is_' . $method . '_request';
                if (method_exists($this, $validity_method))
                {
                    if ($method == 'controller')
                    {
                        $slice = $this->$validity_method($segments);
                        $segments = array_slice($segments, $slice-1);
                    }
                    elseif ($this->$validity_method($segments))
                    {
                        array_shift($segments);
                    }
                }
            }

            // Validate ID Request
            if ($this->is_id_request($segments))
            {
                array_pop($segments);
            }

            // Validate Form Request
            if ($this->is_form_request($segments))
            {
                array_pop($segments);
                array_push($segments, 'form');
            }

            Segments::register($segments, 'request');
        }
    }

    // --------------------------------------------------------------------

    /**
     * Determine is the requested segments is application request
     *
     * @access	private
     * @param   $segments Array of URI segments
     * @return	boolean
     */
    protected function is_app_request($segments = array())
    {
        // Check Segments
        $segments = ( empty($segments) ? Segments::raw() : $segments );
        $segments = (is_array($segments) ? reset($segments) : $segments);

        $is_app = FALSE;
        $app_path = APPPATH.$segments.'/';

        if(is_dir($app_path) AND file_exists($app_path.'/app.json'))
        {
            $is_app = TRUE;
        }
        elseif(defined('BASEAPP'))
        {
            $segments = BASEAPP;
            $app_path = APPPATH.BASEAPP.'/';
        }
        else
        {
            return $is_app;
        }

        $registry = new Registry\App($segments, $app_path);
        Request::register($registry, 'app');

        $core_controller = prepare_filename($segments.'_controller');

        if(file_exists($app_path.'core/'.$core_controller.EXT))
        {
            $registry = new Registry\Core($app_path.'core/'.$core_controller.EXT);
            $this->controllers['app'] = $registry;
        }

        return $is_app;
    }

    // --------------------------------------------------------------------

    /**
     * Determine is the requested segments is language request
     *
     * @access	private
     * @param   $segments Array of URI segments
     * @return	boolean
     */
    protected function is_lang_request($segments = array())
    {
        // Check Segments
        $segments = ( empty($segments) ? Segments::raw() : $segments );
        $segments = (is_array($segments) ? reset($segments) : $segments);

        if(Segments::is_language($segments))
        {
            Request::register($segments, 'lang');
            return TRUE;
        }

        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Determine is the requested segments is an attribute request
     *
     * @access	public
     * @param   $segments Array of URI segments
     * @return	boolean
     */
    protected function is_attribute_request($segments = array())
    {
        // Check Segments
        $segments = ( empty($segments) ? Segments::raw() : $segments );
        $segments = (is_array($segments) ? reset($segments) : $segments);

        if (strpos($segments, '@') !== FALSE)
        {
            Request::register(str_replace('@','',$segments), 'attribute');
            return TRUE;
        }

        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Determine is the requested segments is module request
     *
     * @access	private
     * @param   $segments Array of URI segments
     * @return	boolean
     */
    protected function is_module_request($segments = array())
    {
        // Check Segments
        $segments = ( empty($segments) ? Segments::raw() : $segments );
        $segments = (is_array($segments) ? reset($segments) : $segments);

        $is_module = FALSE;

        foreach(Core::config('modules_folders') as $type)
        {
            $paths = array(APPPATH . $type.'/');

            if (Request::app())
            {
                array_push($paths, APPPATH . Request::app()->path_name . '/'.$type.'/');
            }

            foreach ($paths as $path)
            {
                if (is_dir($path.$segments) AND file_exists($path.$segments.'/'.substr($type,0, -1).'.json'))
                {
                    $registry = new Registry\Module($segments, $path.$segments.'/', $type);
                    $is_module = TRUE;
                }
            }
        }

        if($is_module === TRUE)
        {
            Request::register($registry, 'module');

            if(file_exists($registry->controller_filepath))
            {
                $registry = new Registry\Controller($registry->controller_filepath);
                $this->controllers['module'] = $registry;
            }
        }

        return $is_module;
    }

    // --------------------------------------------------------------------

    /**
     * Determine is the requested segments is controller request
     *
     * @access	private
     * @param   $segments Array of URI segments
     * @return	boolean
     */
    protected function is_controller_request($segments = array())
    {
        // Check Segments
        $segments = ( empty($segments) ? Segments::raw() : $segments );

        $is_controller = FALSE;
        foreach ($segments as $key => $segment)
        {
            if(Request::module())
            {
                $controller_path = Request::module()->path_controllers;

                $filepaths = array(
                    $controller_path.prepare_filename($segment).EXT,
                    $controller_path.$segment.'/'.prepare_filename($segment).EXT,
                );

                current($segments);
                $previous_segment = @$segments[$key-1];
                if(! empty($previous_segment))
                {
                    array_push($filepaths, $controller_path.$previous_segment.'/'.prepare_filename($segment).EXT);
                }

                foreach($filepaths as $filepath)
                {
                    if(file_exists($filepath))
                    {
                        $registry = new Registry\Controller($filepath);
                        $this->controllers[$registry->class_name] = $registry;

                        $is_controller = TRUE;
                    }
                }
            }
            elseif(Request::app())
            {

                // Check Application Controllers
                if(file_exists(APPPATH.'controllers/'.$segment.EXT))
                {
                    $registry = new Registry\Controller(APPPATH.'controllers/'.$segment.EXT);
                    $this->controllers[$registry->class_name] = $registry;

                    $is_controller = TRUE;
                }

                // Check App Controllers
                $controller_path = Request::app()->path_controllers;

                if(file_exists($controller_path.$segment.EXT))
                {
                    $registry = new Registry\Controller($controller_path.$segment.EXT);
                    $this->controllers[$registry->class_name] = $registry;

                    $is_controller = TRUE;
                }
            }
            else
            {
                if(file_exists(APPPATH.'controllers/'.$segment.EXT))
                {
                    $registry = new Registry\Controller(APPPATH.'controllers/'.$segment.EXT);
                    $this->controllers[$registry->path_name] = $registry;

                    $is_controller = TRUE;
                }
            }
        }

        return $is_controller;
    }

    // --------------------------------------------------------------------

    /**
     * Determine is the requested segments is active controller method request
     *
     * @access	private
     * @param   $segments Array of URI segments
     * @return	boolean
     */
    protected function is_method_request($segments = array())
    {
        // Check Segments
        $segments = ( empty($segments) ? Segments::raw() : $segments );

        if (! empty($this->controllers))
        {
            Request::register($this->controllers, 'controllers');

            // Load All Controllers
            foreach($this->controllers as $path_name => $registry)
            {
                require_once($registry->filepath);
            }

            $controller = new \ReflectionClass($registry->class_name);

            $methods = $controller->getMethods(\ReflectionMethod::IS_PUBLIC);
            $properties = $controller->getStaticProperties();

            if(! empty($properties))
            {
                Request::register($properties, 'property_exists');
            }

            if(! empty($methods))
            {
                foreach ($methods as $registry)
                {
                    if (!preg_match_all('(^_)', $registry->name, $match) AND !in_array($registry->name, array('get_instance')))
                    {
                        $public_methods[$registry->name] = $registry;
                    }
                }

                if(!empty($public_methods))
                {
                    Request::register($public_methods, 'methods_exists');

                    foreach($segments as $segment)
                    {
                        if(isset($public_methods[$segment]))
                        {
                            Request::register($public_methods[$segment]->name, 'method');
                            break;
                            return TRUE;
                        }
                    }
                }
            }
        }

        // Set default request method
        Request::register('index', 'method');

        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Determine is the requested segments is login controller request
     *
     * @access	public
     * @param   $segments Array of URI segments
     * @return	boolean
     */
    protected function is_login_request($segments = array())
    {
        // Check Segments
        $segments = ( empty($segments) ? Segments::raw() : $segments );
        $segments = (is_array($segments) ? reset($segments) : $segments);

        // Define Identifier
        $identifier = array('login','qrlogin','logoff','logout','activation','forgot_password','reset_password');

        if(in_array($segments, $identifier))
        {
            return TRUE;
        }
        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Determine is the requested segments is form request
     *
     * @access	public
     * @param   $segments Array of URI segments
     * @return	boolean
     */
    protected function is_form_request($segments = array())
    {
        // Check Segments
        $segments = ( empty($segments) ? Segments::raw() : $segments );
        $segments = (is_array($segments) ? end($segments) : $segments);

        // Define Identifier
        $identifier = array('form','create','add_new','edit','update');

        if(in_array($segments, $identifier))
        {
            return TRUE;
        }

        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Determine is the requested segments is have ID request
     *
     * @access	public
     * @param   $segments Array of URI segments
     * @return	boolean
     */
    protected function is_id_request($segments = array())
    {
        // Check Segments
        $segments = (empty($segments) ? Segments::raw() : $segments);
        $segments = (is_array($segments) ? end($segments) : $segments);

        $x_string = explode('-',$segments);

        $ID = reset($x_string);
        $title = array_slice($x_string, 1);

        if(is_numeric($ID))
        {
            Request::register($ID, 'id');
            Request::register(array($ID), 'params');

            // Normalize Title
            if(! empty($title) AND is_string($title))
            {
                $title = array_map('ucfirst', $title);
                $title = implode(' ', $title);

                Request::register($title, 'title');
            }

            return TRUE;
        }

        return FALSE;
    }
}