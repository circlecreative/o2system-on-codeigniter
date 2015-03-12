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
require_once BASEPATH.'core/Lang.php';

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * O2System HMVC Lang
 *
 * This library extends the CodeIgniter Lang class.
 *
 * @package        O2System
 * @subpackage     O2System
 * @category       Core Class
 * @author         Steeven Andrian Salim
 * @link           http://circle-creative.com/products/o2system-plugin/user-guide/lang.html
 */
// ------------------------------------------------------------------------

class Lang extends \CI_Lang
{
    public $active;
    protected $_config;

    /**
     * Constructor
     *
     * @access	public
     */
    function __construct()
    {
        parent::__construct();

        if(Request::lang())
        {
            $this->active = Request::lang();
        }

        $config =& get_config();

        if (file_exists(SYSPATH.'config/languages.php'))
        {
            include_once(SYSPATH.'config/languages.php');

            if (isset($languages))
            {
                $this->_config = new \stdClass();

                $this->_config->codes = array_keys($languages);
                $this->_config->by_codes = $languages;

                foreach($languages as $lang_code => $lang_registry)
                {
                    $this->_config->idioms[] = $lang_registry->idiom;
                    $this->_config->by_idioms[$lang_registry->idiom] = new \stdClass();
                    $this->_config->by_idioms[$lang_registry->idiom]->name = $lang_registry->name;
                    $this->_config->by_idioms[$lang_registry->idiom]->code = $lang_code;
                }

                unset($languages);
            }
        }

        if ($this->active == '')
        {
            $deft_lang = ( ! isset($config['language'])) ? 'english' : $config['language'];
            $this->active = ($deft_lang == '') ? 'english' : $deft_lang;
            $idiom = $this->_config->by_idioms[$this->active];
            $this->active = $idiom->code;
        }
    }

    // --------------------------------------------------------------------

    protected function _fetch_request($request)
    {
        $app_request = Request::app();
        $module_request = Request::module();
        $file_request = $request;

        if (strpos($request, '/') !== FALSE)
        {
            $x_request = explode('/', $request);

            // App Request
            if (Segments::is_app($x_request[0]))
            {
                $app_request = new Registry\App($x_request[0], APPPATH.$x_request[0]);
                array_shift($x_request);
            }

            // Module Request
            $module_path = $app_request->path_name.'/'.$x_request[0];
            if (Segments::is_module($module_path))
            {
                $module_request = new Registry\Module($x_request[0], $app_request->path.$x_request[0].'/');
                array_shift($x_request);
            }

            if (!empty($x_request))
            {
                $file_request = implode('/', $x_request);
            }
        }

        $request_paths = array(
            'system' => BASEPATH.'language/',
            'app' => APPPATH.'language/',
        );

        if(! empty($app_request))
        {
            $request_paths[$app_request->path_name] = $app_request->path.'language/';
        }

        if(! empty($module_request))
        {
            $request_paths[$module_request->path_name] = $module_request->path.'language/';
        }

        $paths = array();
        foreach($request_paths as $request_app => $request_path)
        {
            $_file_request = $file_request == '' || $file_request == 'AUTO' ? $request_app : $file_request;

            if(is_dir($request_path.$this->active))
            {
                foreach(array('.php','_lang.php','.ini','_lang.ini') as $ext)
                {
                    if(is_file($request_path.$this->active.'/'.$_file_request.$ext))
                    {
                        $paths[] = $request_path.$this->active.'/'.$_file_request.$ext;
                    }
                }
            }

            foreach(array('.php','.ini') as $ext)
            {
                if(is_file($request_path.$_file_request.'_'.$this->active.$ext))
                {
                    $paths[] = $request_path.$_file_request.'_'.$this->active.$ext;
                }
            }

            // By Idioms
            $code = $this->_config->by_codes[$this->active];
            if(is_dir($request_path.$code->idiom))
            {
                foreach(array('.php','_lang.php','.ini','_lang.ini') as $ext)
                {
                    if(is_file($request_path.$code->idiom.'/'.$_file_request.$ext))
                    {
                        $paths[] = $request_path.$code->idiom.'/'.$_file_request.$ext;
                    }
                }
            }

            foreach(array('.php','.ini') as $ext)
            {
                if(is_file($request_path.$_file_request.'_'.$code->idiom.$ext))
                {
                    $paths[] = $request_path.$_file_request.'_'.$code->idiom.$ext;
                }
            }
        }

        return $paths;
    }

    // --------------------------------------------------------------------

    /**
     * Load a language file
     *
     * @access	public
     * @param	mixed	the name of the language file to be loaded. Can be an array
     * @param	string	the language (english, etc.)
     * @param	bool	return loaded array of translations
     * @param 	bool	add suffix to $lang_file
     * @param 	string	alternative path to look for language file
     * @return	mixed
     */
    function load($lang_file = '', $idiom = '', $return = FALSE, $add_suffix = FALSE, $alt_path = '')
    {
        $lang_file = str_replace(array('.php','.ini'), '', $lang_file);

        if ($add_suffix == TRUE)
        {
            $lang_file = str_replace('_lang.', '', $lang_file).'_lang';
        }

        $lang_paths = $this->_fetch_request($lang_file);

        $found = FALSE;
        foreach($lang_paths as $lang_path)
        {
            $type = pathinfo($lang_path, PATHINFO_EXTENSION);

            if($type == 'php')
            {
                include($lang_path);

                if ( ! isset($lang))
                {
                    log_message('error', 'Language file contains no data: language/'.$idiom.'/'.$lang_file);
                }
                else
                {
                    $this->is_loaded[] = $lang_path;
                    $this->language = array_merge($this->language, $lang);
                    log_message('debug', 'Language file loaded: '.$lang_path);
                    $found = TRUE;
                    unset($lang);
                }
            }
            elseif($type == 'ini')
            {
                $lang = parse_ini_file($lang_path);
                $this->language = array_merge($this->language, $lang);
                log_message('debug', 'Language file loaded: '.$lang_path);
                $found = TRUE;
                unset($lang);
            }
        }

        return $found;
    }

    // --------------------------------------------------------------------
}