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
require_once BASEPATH . 'core/Loader.php';

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * O2System HMVC Loader
 *
 * This library extends the CodeIgniter CI_Loader class
 * and adds features allowing use of modules and the HMVC design pattern.
 *
 * @package        O2System
 * @subpackage     O2System
 * @category       Core Class
 * @author         Steeven Andrian Salim
 * @link           http://circle-creative.com/products/o2system-plugin/user-guide/loader.html
 */
// ------------------------------------------------------------------------

class Loader extends \CI_Loader
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Load class
     *
     * This function loads the requested class.
     *
     * @param    string    the item that is being loaded
     * @param    mixed    any additional parameters
     * @param    string    an optional object name
     * @return    void
     */
    protected function _ci_load_class($class, $params = NULL, $object_name = NULL)
    {
        $class_name = $this->_fetch_class($class);
        $class_paths = $this->_fetch_request($class);

        // Fetch Class Name
        if (is_null($object_name))
        {
            $object_name = $class_name;
        }
        else
        {
            $object_name = strtolower($object_name);
        }

        // Include Base Class
        $found_base = FALSE;
        foreach ($class_paths['base_class'] as $base_class)
        {
            foreach ($base_class as $load_class => $load_path)
            {
                if (file_exists($load_path))
                {
                    if (!in_array($load_class, $this->_ci_loaded_files))
                    {
                        include_once($load_path);
                        $this->_ci_loaded_files[] = $load_class;
                        $class_name = $load_class;
                        $found_base = TRUE;
                        break;
                    }
                    else
                    {
                        $class_name = $load_class;
                        $found_base = TRUE;
                    }
                }
            }

            if ($found_base === TRUE)
            {
                break;
            }
        }

        if ($found_base === FALSE)
        {
            log_message('error', "Unable to load the requested class: " . $class);
            show_error("Unable to load the requested class: " . $class);
        }

        $sub_class_name = FALSE;
        foreach ($class_paths['sub_class'] as $sub_class)
        {
            foreach ($sub_class as $load_sub_class => $load_sub_path)
            {
                if (file_exists($load_sub_path))
                {
                    // Safety:  Was the class already loaded by a previous call?
                    if (!in_array($load_sub_class, $this->_ci_loaded_files))
                    {
                        include_once($load_sub_path);
                        $this->_ci_loaded_files[] = $load_sub_class;
                        $sub_class_name = $load_sub_class;
                        break;
                    }
                    else
                    {
                        $sub_class_name = $load_sub_class;
                    }
                }
            }
        }

        if ($sub_class_name)
        {
            $class_name = $sub_class_name;
        }

        $is_duplicate = FALSE;
        $CI =& get_instance();
        if (!isset($CI->$object_name))
        {
            return $this->_ci_init_class($class_name, $params, $object_name);
        }
        else
        {
            $is_duplicate = TRUE;
            log_message('debug', $sub_class . " class already loaded. Second attempt ignored.");
        }

        // If we got this far we were unable to find the requested class.
        // We do not issue errors if the load call failed due to a duplicate request
        if ($is_duplicate == FALSE)
        {
            log_message('error', "Unable to load the requested class: " . $class);
            show_error("Unable to load the requested class: " . $class);
        }

    }

    protected function _fetch_class($request)
    {
        if (strpos($request, '/') !== FALSE)
        {
            $x_request = explode('/', $request);
            return strtolower(end($x_request));
        }

        return strtolower($request);
    }

    protected function _fetch_request($request, $sub_path = 'libraries')
    {
        $app_request = Request::app();
        $module_request = Request::module();
        $file_request = $request;

        if(is_array($request)) return;

        if (strpos($request, '/') !== FALSE)
        {
            $x_request = explode('/', $request);

            // App Request
            if (Segments::is_app($x_request[0]))
            {
                $app_request = new Registry\App($x_request[0], APPPATH . $x_request[0]);
                array_shift($x_request);
            }

            // Module Request
            $module_path = $app_request->path_name . '/' . $x_request[0];
            foreach (Core::config('modules_folders') as $type)
            {
                if (Segments::is_module($module_path))
                {
                    $module_request = new Registry\Module($x_request[0], $module_path . '/', $type);
                    array_shift($x_request);
                    break;
                }
            }

            if (!empty($x_request))
            {
                $file_request = implode('/', $x_request);
            }
        }

        switch ($sub_path)
        {
            default:
                $file_request = prepare_class_name($file_request);
                $request_paths = array(
                    // Third Party Libraries
                    'base_class' => array(
                        array(prepare_class_name($file_request) => APPPATH . 'third_party/' . $sub_path . '/' . $file_request . EXT),
                        array(prepare_class_name('CI_' . $file_request) => BASEPATH . $sub_path . '/' . $file_request . EXT),
                        array(prepare_class_name('O2_' . $file_request) => SYSPATH . $sub_path . '/' . $file_request . EXT),
                        array(prepare_class_name($file_request) => APPPATH . $sub_path . '/' . $file_request . EXT),
                        array(prepare_class_name($file_request) => @$app_request->path . $sub_path . '/' . $file_request . EXT),
                        array(prepare_class_name($file_request) => @$module_request->path . $sub_path . '/' . $file_request . EXT),

                        // Driver
                        array(prepare_class_name($file_request) => APPPATH . 'third_party/' . $sub_path . '/' . $file_request . '/' . $file_request . EXT),
                        array(prepare_class_name('CI_' . $file_request) => BASEPATH . $sub_path . '/' . $file_request . '/' . $file_request . EXT),
                        array(prepare_class_name('O2_' . $file_request) => SYSPATH . $sub_path . '/' . $file_request . '/' . $file_request . EXT),
                        array(prepare_class_name($file_request) => APPPATH . $sub_path . '/' . $file_request . '/' . $file_request . EXT),
                        array(prepare_class_name($file_request) => @$app_request->path . $sub_path . '/' . $file_request . '/' . $file_request . EXT),
                        array(prepare_class_name($file_request) => @$module_request->path . $sub_path . '/' . $file_request . '/' . $file_request . EXT),
                    ),
                    'sub_class' => array(
                        array(prepare_class_name('O2_' . $file_request) => SYSPATH . $sub_path . '/' . $file_request . EXT),
                        array(prepare_class_name('App_' . $file_request) => APPPATH . $sub_path . '/' . prepare_class_name('App_' . $file_request) . EXT),
                        array(prepare_class_name(@$app_request->path_name . '_' . $file_request) => @$app_request->path . $sub_path . '/' . prepare_class_name(@$app_request->path_name . '_' . $file_request) . EXT),
                        array(prepare_class_name(@$module_request->path_name . '_' . $file_request) => @$module_request->path . $sub_path . '/' . prepare_class_name(@$module_request->path_name . '_' . $file_request) . EXT),
                    )
                );
                break;
            case 'config':
            case 'controllers':
                $file_request = strtolower(prepare_filename($file_request));
                break;

            case 'views':

                $request_paths = array(
                    BASEPATH . 'views/' => TRUE,
                    APPPATH . 'third_party/views/' => TRUE,
                    SYSPATH . 'views/' => TRUE,
                    APPPATH . 'views/' => TRUE,
                );

                if (Request::app())
                {
                    $request_paths = array_merge($request_paths, array(APPPATH . Request::app()->path_name . '/views/' => TRUE));
                }

                if (Request::module())
                {
                    if (Request::app())
                    {
                        $request_paths = array_merge($request_paths, array(APPPATH . Request::app()->path_name . '/modules/' . Request::module()->path_name . '/views/' => TRUE));
                    }
                    else
                    {
                        $request_paths = array_merge($request_paths, array(APPPATH . 'modules/' . Request::module()->path_name . '/views/' => TRUE));
                    }
                }

                if (Request::template())
                {
                    $request_paths = array_merge($request_paths, array(Request::template()->path.'views/' => TRUE));
                }

                break;
            case 'helpers':
            case 'models':
            $request_paths = array(
                    BASEPATH,
                    APPPATH,
                    APPPATH . 'third_party/',
                    SYSPATH
                );

                if (Request::app())
                {
                    array_push($request_paths, APPPATH . Request::app()->path_name . '/');
                }

                if (Request::module())
                {
                    if (Request::app())
                    {
                        array_push($request_paths, APPPATH . Request::app()->path_name . '/modules/' . Request::module()->path_name . '/');
                    }
                    else
                    {
                        array_push($request_paths, APPPATH . 'modules/' . Request::module()->path_name . '/');
                    }
                }
                break;
        }

        return $request_paths;
    }

    // --------------------------------------------------------------------

    /**
     * Instantiates a class
     *
     * @param    string
     * @param    string
     * @param    bool
     * @param    string    an optional object name
     * @return    null
     */
    protected function _ci_init_class($class, $config = FALSE, $object_name = NULL)
    {
        // Is there an associated config file for this class?  Note: these should always be lowercase
        if ($config === NULL)
        {
            // Fetch the config paths containing any package paths
            $config_component = $this->_ci_get_component('config');
            $config_component->_locate_config_paths();

            if (is_array($config_component->_config_paths))
            {
                // Break on the first found file, thus package files
                // are not overridden by default paths
                foreach ($config_component->_config_paths as $path)
                {
                    // We test for both uppercase and lowercase, for servers that
                    // are case-sensitive with regard to file names. Check for environment
                    // first, global next
                    if (defined('ENVIRONMENT') AND file_exists($path . 'config/' . ENVIRONMENT . '/' . strtolower($class) . '.php'))
                    {
                        include($path . 'config/' . ENVIRONMENT . '/' . strtolower($class) . '.php');
                        break;
                    }
                    elseif (defined('ENVIRONMENT') AND file_exists($path . 'config/' . ENVIRONMENT . '/' . ucfirst(strtolower($class)) . '.php'))
                    {
                        include($path . 'config/' . ENVIRONMENT . '/' . ucfirst(strtolower($class)) . '.php');
                        break;
                    }
                    elseif (file_exists($path . 'config/' . strtolower($class) . '.php'))
                    {
                        include($path . 'config/' . strtolower($class) . '.php');
                        break;
                    }
                    elseif (file_exists($path . 'config/' . ucfirst(strtolower($class)) . '.php'))
                    {
                        include($path . 'config/' . ucfirst(strtolower($class)) . '.php');
                        break;
                    }
                }
            }
        }

        $class = prepare_class_name($class);

        // Is the class name valid?
        if (!class_exists($class))
        {
            log_message('error', "Non-existent class: " . $class);
            show_error("Non-existent class: " . $class);
        }

        $object_name = (!isset($this->_ci_varmap[$object_name])) ? $object_name : $this->_ci_varmap[$object_name];

        // Save the class name and object name
        $this->_ci_classes[$class] = $object_name;

        // Instantiate the class
        $CI =& get_instance();
        if ($config !== NULL)
        {
            $CI->$object_name = new $class($config);
        }
        else
        {
            $CI->$object_name = new $class;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Load View
     *
     * This function is used to load a "view" file.  It has three parameters:
     *
     * 1. The name of the "view" file to be included.
     * 2. An associative array of data to be extracted for use in the view.
     * 3. TRUE/FALSE - whether to return the data or load it.  In
     * some cases it's advantageous to be able to return data so that
     * a developer can process it in some way.
     *
     * @param    string
     * @param    array
     * @param    bool
     * @return    void
     */
    public function view($view, $vars = array(), $return = FALSE)
    {
        $this->_ci_view_paths = $this->_fetch_request($view, 'views');

        return parent::view($view, $vars, $return);
    }

    // --------------------------------------------------------------------

    /**
     * Load Helper
     *
     * This function loads the specified helper file.
     *
     * @param    mixed
     * @return    void
     */
    public function helper($helpers = array())
    {
        if(is_array($helpers))
        {
            foreach($helpers as $helper)
            {
                $this->helper($helper);
            }
        }

        $this->_ci_helper_paths = $this->_fetch_request($helpers, 'helpers');

        parent::helper($helpers);
    }

    /**
     * Model Loader
     *
     * This function lets users load and instantiate models.
     *
     * @param	string	the name of the class
     * @param	string	name for the model
     * @param	bool	database connection
     * @return	void
     */
    public function model($models, $name = '', $db_conn = FALSE)
    {
        if(is_array($models))
        {
            foreach($models as $model)
            {
                $this->model($model);
            }
        }

        $this->_ci_model_paths = $this->_fetch_request($models, 'models');

        parent::model($models, $name, $db_conn);
    }

    /**
     * Autoloader
     *
     * The config/autoload.php file contains an array that permits sub-systems,
     * libraries, and helpers to be loaded automatically.
     *
     * @param    array
     * @return    void
     */
    private function _ci_autoloader()
    {
        if (defined('ENVIRONMENT') AND file_exists(APPPATH . 'config/' . ENVIRONMENT . '/autoload.php'))
        {
            include(APPPATH . 'config/' . ENVIRONMENT . '/autoload.php');
        }
        else
        {
            include(APPPATH . 'config/autoload.php');
        }

        if (!isset($autoload))
        {
            return FALSE;
        }

        // Autoload packages
        if (isset($autoload['packages']))
        {
            foreach ($autoload['packages'] as $package_path)
            {
                $this->add_package_path($package_path);
            }
        }

        // Load any custom config file
        if (count($autoload['config']) > 0)
        {
            $CI =& get_instance();
            foreach ($autoload['config'] as $key => $val)
            {
                $CI->config->load($val);
            }
        }

        // Autoload helpers and languages
        foreach (array('helper', 'language') as $type)
        {
            if (isset($autoload[$type]) AND count($autoload[$type]) > 0)
            {
                $this->$type($autoload[$type]);
            }
        }

        // A little tweak to remain backward compatible
        // The $autoload['core'] item was deprecated
        if (!isset($autoload['libraries']) AND isset($autoload['core']))
        {
            $autoload['libraries'] = $autoload['core'];
        }

        // Load libraries
        if (isset($autoload['libraries']) AND count($autoload['libraries']) > 0)
        {
            // Load the database driver.
            if (in_array('database', $autoload['libraries']))
            {
                $this->database();
                $autoload['libraries'] = array_diff($autoload['libraries'], array('database'));
            }

            // Load all other libraries
            foreach ($autoload['libraries'] as $item)
            {
                $this->library($item);
            }
        }

        // Autoload models
        if (isset($autoload['model']))
        {
            $this->model($autoload['model']);
        }
    }

    // --------------------------------------------------------------------
}