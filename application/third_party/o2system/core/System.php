<?php
namespace O2System
{
    defined('SYSPATH') OR exit('No direct script access allowed');

    class Core
    {
        public static $config;

        public function __construct()
        {
            if (file_exists(SYSPATH.'config/config.php'))
            {
                include_once(SYSPATH.'config/config.php');

                if (isset($config))
                {
                    self::$config = $config;
                    unset($config);
                }
            }
        }

        public static function __callstatic($name, $arguments)
        {
            if(empty($arguments))
            {
                if(isset(self::$$name))
                {
                    return self::$$name;
                }
            }
            else
            {
                if(isset(self::$$name))
                {
                    $static = self::$$name;

                    if(isset($static[reset($arguments)]))
                    {
                        return $static[reset($arguments)];
                    }
                }
            }

            return FALSE;
        }
    }

    class Segments
    {
        private static $raw;
        private static $request;

        public static function register($registry, $key = 'request')
        {
            self::$$key = $registry;
        }

        public static function __callstatic($name, $arguments)
        {
            if(isset(self::$$name))
            {
                return self::$$name;
            }

            return array();
        }

        public static function is_app($app_name)
        {
            $app_path = APPPATH.$app_name;

            if(is_dir($app_path) AND file_exists($app_path.'/app.json'))
            {
                return TRUE;
            }

            return FALSE;
        }

        public static function is_module($module_name)
        {
            foreach(Core::config('modules_folders') as $type)
            {
                $paths = array(APPPATH . $type.'/');

                if (Request::app())
                {
                    array_push($paths, APPPATH . Request::app()->path_name . '/'.$type.'/');
                }

                if (strpos($module_name, '/') !== FALSE)
                {
                    $x_request = explode('/', $module_name);

                    // App Request
                    if (Segments::is_app($x_request[0]))
                    {
                        array_push($paths, APPPATH.$x_request[0].'/'.$type.'/');
                    }
                }

                foreach ($paths as $path)
                {
                    if (is_dir($path.$module_name) AND file_exists($path.$module_name.'/'.substr($type,0, -1).'.json'))
                    {
                        return TRUE;
                    }
                }
            }

            return FALSE;
        }

        public static function is_lang($lang_name)
        {
            $paths = array(SYSPATH, APPPATH);

            if (Request::app())
            {
                array_push($paths, APPPATH . Request::app()->path_name .'/');
            }

            if (strpos($lang_name, '/') !== FALSE)
            {
                $x_request = explode('/', $lang_name);

                // App Request
                if (Segments::is_app($x_request[0]))
                {
                    array_push($paths, APPPATH.$x_request[0].'/');
                }
            }

            foreach ($paths as $path)
            {
                if (is_dir($path.$lang_name))
                {
                    return TRUE;
                }
            }

            return FALSE;
        }
    }

    class Request
    {
        public static $registry;

        public function __construct()
        {
            self::$registry = new \stdClass;
        }

        public static function __callstatic($name, $arguments)
        {
            if(! empty(self::$registry->{$name}))
            {
                return self::$registry->{$name};
            }

            return array();
        }

        public static function register($registry, $key)
        {
            if (strrpos($key, '->') !== FALSE)
            {
                $x_key = explode('->', $key);

                if(empty(self::$registry->{$x_key[0]}))
                {
                    self::$registry->{$x_key[0]} = new \stdClass();
                }

                if(isset(self::$registry->{$x_key[0]}->{$x_key[1]}))
                {
                    if(is_array(self::$registry->{$x_key[0]}->{$x_key[1]}))
                    {
                        if(is_array($registry))
                        {
                            self::$registry->{$x_key[0]}->{$x_key[1]} = array_merge(self::$registry->{$x_key[0]}->{$x_key[1]}, $registry);
                        }
                        else
                        {
                            array_push(self::$registry->{$x_key[0]}->{$x_key[1]}, $registry);
                        }
                    }
                    else
                    {
                        self::$registry->{$x_key[0]}->{$x_key[1]} = $registry;
                    }
                }
                else
                {
                    self::$registry->{$x_key[0]}->{$x_key[1]} = $registry;
                }
            }
            elseif(empty(self::$registry->{$key}))
            {
                self::$registry->{$key} = $registry;
            }
            else
            {
                if (is_array(self::$registry->{$key}))
                {
                    if (is_array($registry))
                    {
                        self::$registry->{$key} = array_merge(self::$registry->{$key}, $registry);
                    }
                    else
                    {
                        array_push(self::$registry->{$key}, $registry);
                    }
                }
                else
                {
                    self::$registry->{$key} = $registry;
                }
            }
        }
    }
}

namespace O2System\Registry
{
    abstract class Registry
    {
        /**
         * Registry path_name
         *
         * @var string
         */
        public $path_name;

        /**
         * Registry Path
         *
         * @var string
         */
        public $path;

        /**
         * Registry Code
         *
         * @var string
         */
        public $code;

        /**
         * Registry Info
         *
         * @var string
         */
        public $info;

        /**
         * Registry Settings
         *
         * @var string
         */
        public $settings;

        /**
         * Load JSON Information File
         *
         * @var    string
         */
        protected function load_info($filepath = '', $code_length = 8)
        {
            if (file_exists($filepath))
            {
                $package = file_get_contents($filepath);
                $this->info = json_decode($package);

                if(isset($this->info->settings))
                {
                    $this->settings = $this->info->settings;
                    unset($this->info->settings);
                }
            }

            $this->code = $this->generate_code($this->path_name, $code_length);
        }

        protected function generate_code($path_name, $length)
        {
            $code = substr(md5($path_name), $length, $length);
            $code = strtoupper($code);

            return $code;
        }
    }

    /**
     * Application Registry
     * Generate application registry  for O2System
     */
    class App extends Registry
    {
        /**
         * Class constructor
         *
         * @return    void
         */
        public function __construct($path_name, $path = '')
        {
            $this->path_name = $path_name;
            $this->path = str_replace('//', '/', $path.'/');
            $this->path_controllers = $this->path.'controllers/';

            // Register Info
            $this->load_info($this->path . 'app.json');
        }
    }

    /**
     * Application Registry
     * Generate application registry  for O2System
     */
    class Module extends Registry
    {
        public $app_name;
        public $type;
        public $path;
        public $path_name;
        public $path_alias;
        public $path_controllers;
        public $controller_class_name;
        public $controller_filename;
        public $controller_filepath;

        /**
         * Class constructor
         *
         * @return    void
         */
        public function __construct($path_name, $filepath = '', $type = 'modules')
        {
            $this->path = str_replace('//', '/', $filepath.'/');
            $this->path_name = $path_name;

            $this->type = $type;

            $alias = array();
            $x_app_name = str_replace(array(BASEPATH,APPPATH), '', $filepath);
            $x_app_name = explode('/', $x_app_name);

            $this->app_name = reset($x_app_name);

            if(strpos($filepath, 'system') !== FALSE)
            {
                $this->app_name = 'system';
                $this->path_name = 'CI_'.$this->path_name;
            }

            if(strpos($filepath, 'application') !== FALSE AND $this->app_name == '')
            {
                $this->app_name = 'app';
            }

            if($this->app_name == 'core')
            {
                $this->app_name = 'app';
            }

            $alias = $suffix = array(
                $this->app_name,
            );

            $suffix = implode('_', $suffix);
            $alias = implode('/', $alias);

            $this->path_alias = $alias.'/'.$this->path_name;

            $this->path_controllers = $filepath.'controllers/';

            $this->controller_filename = $this->path_name.'.php';
            $this->controller_class_name = \O2System\prepare_class_name($suffix.'_'.$this->path_name);
            $this->controller_filepath = $this->path_controllers.$this->controller_filename;

            // Register Info
            $this->load_info($this->path . 'module.json');
        }
    }

    /**
     * Core Class Registry
     * Generate core class registry for O2System
     */
    class Core extends Registry
    {
        public $app_name;
        public $class_name;
        public $path;
        public $path_name;
        public $path_alias;
        public $filename;
        public $filepath;

        /**
         * Class constructor
         *
         * @return    void
         */
        public function __construct($filepath)
        {
            // Register Filepath
            $this->filepath = str_replace(array('//','CI_'),array('/',''), $filepath);

            // Register Filename
            $this->filename = pathinfo($this->filepath, PATHINFO_BASENAME);

            // Register path_name
            $this->path_name = pathinfo($this->filepath, PATHINFO_FILENAME);

            $alias = array();
            $x_app_name = str_replace(array(BASEPATH,APPPATH), '', $filepath);
            $x_app_name = explode('/', $x_app_name);

            $this->app_name = reset($x_app_name);

            if(strpos($filepath, 'system') !== FALSE)
            {
                $this->app_name = 'system';
                $this->path_name = 'CI_'.$this->path_name;
            }

            if(strpos($filepath, 'application') !== FALSE AND $this->app_name == '')
            {
                $this->app_name = 'app';
            }

            if($this->app_name == 'core')
            {
                $this->app_name = 'app';
            }

            array_push($alias, $this->app_name);

            $sub_path = '';
            if(count($x_app_name) > 3)
            {
                $this->module_type = $x_app_name[1];
                array_push($alias, $this->module_type);

                $this->module_name = $x_app_name[2];
                array_push($alias, $this->module_name);

                if(isset($x_app_name[4]) AND $x_app_name[4] !== $this->filename)
                {
                    array_pop($x_app_name);
                    $x_app_name = array_slice($x_app_name, 4);
                    $alias = array_merge($alias, $x_app_name);
                }
            }

            array_push($alias, str_replace($this->app_name.'_','',strtolower($this->path_name)));
            $this->path_alias = implode('/', array_filter($alias));
            $this->path_alias = str_replace('ci_','', $this->path_alias);

            $this->path = pathinfo($this->filepath, PATHINFO_DIRNAME).'/';
            $this->class_name = \O2System\prepare_class_name($this->path_name);

            if(strpos($filepath, 'o2system') !== FALSE)
            {
                $this->path_alias = str_replace('o2system/','', $this->path_alias);
                $this->class_name = '\\O2System\\'.str_replace('CI_','',$this->path_name);
            }

            unset($this->code, $this->info, $this->settings);
        }
    }

    /**
     * Library Class Registry
     * Generate library class registry for O2System
     */
    class Library extends Registry
    {
        public $app_name;
        public $module_name;
        public $class_name;
        public $path;
        public $path_name;
        public $path_alias;
        public $filename;
        public $filepath;

        /**
         * Class constructor
         *
         * @return    void
         */
        public function __construct($filepath)
        {
            // Register Filepath
            $this->filepath = str_replace('//', '/', $filepath);

            // Register Filename
            $this->filename = pathinfo($this->filepath, PATHINFO_BASENAME);

            // Register path_name
            $this->path = pathinfo($this->filepath, PATHINFO_DIRNAME).'/';
            $this->path_name = strtolower(pathinfo($this->filepath, PATHINFO_FILENAME));
            $this->class_name = \O2System\prepare_class_name($this->path_name);

            $alias = array();
            $x_app_name = str_replace(array(strtolower(BASEPATH),strtolower(APPPATH)), '', $filepath);
            $x_app_name = explode('/', $x_app_name);

            $this->app_name = reset($x_app_name);

            if(strpos($filepath, 'system') !== FALSE)
            {
                $this->app_name = 'system';
                $this->class_name = 'CI_'.$this->class_name;
            }

            if(strpos($filepath, 'application') !== FALSE AND $this->app_name == 'libraries')
            {
                $this->app_name = 'app';
            }

            array_push($alias, $this->app_name);

            $sub_path = '';
            if(count($x_app_name) > 3)
            {
                $this->module_type = $x_app_name[1];
                array_push($alias, $this->module_type);

                $this->module_name = $x_app_name[2];
                array_push($alias, $this->module_name);

                if(isset($x_app_name[4]) AND $x_app_name[4] !== $this->filename)
                {
                    array_pop($x_app_name);
                    $x_app_name = array_slice($x_app_name, 4);
                    $alias = array_merge($alias, $x_app_name);
                }
            }

            array_push($alias, str_replace($this->app_name.'_','',strtolower($this->path_name)));
            $this->path_alias = implode('/', array_filter($alias));

            $this->load_info($this->filepath);
        }

        protected function load_info($filepath = '', $code_length = 5)
        {
            $this->code = $this->generate_code($this->path_name, $code_length);

            $file = @file_get_contents($filepath);

            if(empty($file)) return;

            $this->info = new \stdClass();

            preg_match ('|@name(.*)$|mi', $file, $this->info->name);
            preg_match ('|@description(.*)$|mi', $file, $this->info->description);
            preg_match ('|@version(.*)|i', $file, $this->info->version);
            preg_match_all ('|@author(.*)$|mi', $file, $this->info->author);
            preg_match ('|@contributor(.*)$|mi', $file, $this->info->contributor);
            preg_match_all ('|@license(.*)$|mi', $file, $this->info->license);
            preg_match_all ('|@link(.*)$|mi', $file, $this->info->link);
            preg_match ('|@settings(.*)$|mi', $file, $this->settings);

            foreach($this->info as $key => $value)
            {
                if(in_array($key, array('author','license','link')))
                {
                    $this->info->{$key} = trim(@$value[1][1]);
                }
                else
                {
                    $this->info->{$key} = trim(@$value[1]);
                }
            }

            if(! empty($this->settings))
            {
                $this->settings = json_decode(trim($this->settings[1]));
            }
        }
    }

    /**
     * Driver Class Registry
     * Generate driver class registry for O2System
     */
    class Driver extends Registry
    {
        public $app_name;
        public $module_name;
        public $class_name;
        public $path;
        public $path_name;
        public $path_alias;
        public $filename;
        public $filepath;

        /**
         * Class constructor
         *
         * @return    void
         */
        public function __construct($filepath, $map)
        {
            // Register Filepath
            $this->filepath = str_replace('//', '/', $filepath);

            // Register Filename
            $this->filename = pathinfo($this->filepath, PATHINFO_BASENAME);

            // Register path_name
            $this->path_name = strtolower(pathinfo($this->filepath, PATHINFO_FILENAME));
            $this->class_name = \O2System\prepare_class_name($this->path_name);

            $alias = array();
            $x_app_name = str_replace(array(strtolower(BASEPATH),strtolower(APPPATH)), '', $filepath);
            $x_app_name = explode('/', $x_app_name);

            $this->app_name = reset($x_app_name);

            if(strpos($filepath, 'system') !== FALSE)
            {
                $this->app_name = 'system';
                $this->class_name = 'CI_'.$this->class_name;
            }

            if(strpos($filepath, 'application') !== FALSE AND $this->app_name == '')
            {
                $this->app_name = 'app';
            }

            array_push($alias, $this->app_name);

            $sub_path = '';
            if(count($x_app_name) > 3)
            {
                $this->module_type = $x_app_name[1];
                array_push($alias, $this->module_type);

                $this->module_name = $x_app_name[2];
                array_push($alias, $this->module_name);

                if(isset($x_app_name[4]) AND $x_app_name[4] !== $this->filename)
                {
                    array_pop($x_app_name);
                    $x_app_name = array_slice($x_app_name, 4);
                    $alias = array_merge($alias, $x_app_name);
                }
            }

            // Register path_name
            $this->path_name = pathinfo($this->filepath, PATHINFO_FILENAME);

            array_push($alias, str_replace($this->app_name.'_','',strtolower($this->path_name)));
            $this->path_alias = implode('/', array_filter($alias));

            $this->path = pathinfo($this->filepath, PATHINFO_DIRNAME).'/';
            $this->class_name = \O2System\prepare_class_name($this->path_name);

            if(! empty($map['drivers']))
            {
                foreach($map['drivers'] as $driver)
                {
                    $this->valid_drivers[] = str_replace($this->path_name.'_','', pathinfo($driver, PATHINFO_FILENAME));
                }
            }

            $this->load_info($this->filepath);
        }

        protected function load_info($filepath = '', $code_length = 5)
        {
            $this->code = $this->generate_code($this->path_name, $code_length);

            $file = @file_get_contents($filepath);

            if(empty($file)) return;

            $this->info = new \stdClass();

            preg_match ('|@name(.*)$|mi', $file, $this->info->name);
            preg_match ('|@description(.*)$|mi', $file, $this->info->description);
            preg_match ('|@version(.*)|i', $file, $this->info->version);
            preg_match_all ('|@author(.*)$|mi', $file, $this->info->author);
            preg_match ('|@contributor(.*)$|mi', $file, $this->info->contributor);
            preg_match_all ('|@license(.*)$|mi', $file, $this->info->license);
            preg_match_all ('|@link(.*)$|mi', $file, $this->info->link);
            preg_match ('|@settings(.*)$|mi', $file, $this->settings);

            foreach($this->info as $key => $value)
            {
                if(in_array($key, array('author','license','link')))
                {
                    $this->info->{$key} = trim(@$value[1][1]);
                }
                else
                {
                    $this->info->{$key} = trim(@$value[1]);
                }
            }

            if(! empty($this->settings))
            {
                $this->settings = json_decode(trim($this->settings[1]));
            }
        }
    }

    /**
     * Controller Registry
     * Generate controller registry for O2System
     */
    class Controller extends Registry
    {
        public $app_name;
        public $module_name;
        public $module_type;
        public $class_name;
        public $path;
        public $path_name;
        public $path_alias;
        public $filename;
        public $filepath;

        /**
         * Class constructor
         *
         * @return    void
         */
        public function __construct($filepath = '')
        {
            // Register Filepath
            $this->filepath = str_replace('//', '/', $filepath);

            // Register Filename
            $this->filename = pathinfo($this->filepath, PATHINFO_BASENAME);

            $alias = array();
            $class_names = array();
            $x_app_name = str_replace(APPPATH, '', $filepath);
            $x_app_name = explode('/', $x_app_name);

            $this->app_name = reset($x_app_name);

            if(strpos($filepath, 'system') !== FALSE)
            {
                $this->app_name = 'system';
                $this->class_name = 'CI_'.$this->class_name;
            }

            if(strpos($filepath, 'application') !== FALSE AND $this->app_name == '')
            {
                $this->app_name = 'app';
            }

            if($this->app_name == 'controllers')
            {
                $this->app_name = 'app';
            }

            array_push($alias, $this->app_name);
            array_push($class_names, $this->app_name);

            $sub_path = '';
            if(count($x_app_name) > 3)
            {
                $this->module_type = $x_app_name[1];
                array_push($alias, $this->module_type);

                $this->module_name = $x_app_name[2];
                array_push($alias, $this->module_name);
                array_push($class_names, $this->module_name);

                if(isset($x_app_name[4]) AND $x_app_name[4] !== $this->filename)
                {
                    array_pop($x_app_name);
                    $x_app_name = array_slice($x_app_name, 4);
                    $alias = array_merge($alias, $x_app_name);
                    $class_names = array_merge($class_names, $x_app_name);
                }
            }

            // Register path_name
            $this->path_name = pathinfo($this->filepath, PATHINFO_FILENAME);

            array_push($alias, $this->path_name);
            $alias = array_unique($alias);
            $this->path_alias = strtolower(implode('/', array_filter($alias)));

            array_push($class_names, $this->path_name);
            $this->class_name = \O2System\prepare_class_name(implode('_', $class_names));

            $this->path = pathinfo($this->filepath, PATHINFO_DIRNAME).'/';

            $this->load_info($this->filepath);
        }

        protected function load_info($filepath = '', $code_length = 5)
        {
            $this->code = $this->generate_code($this->path_name, $code_length);

            $file = @file_get_contents($filepath);

            if(empty($file)) return;

            $this->info = new \stdClass();

            preg_match ('|@name(.*)$|mi', $file, $this->info->name);
            preg_match ('|@description(.*)$|mi', $file, $this->info->description);
            preg_match ('|@version(.*)|i', $file, $this->info->version);
            preg_match_all ('|@author(.*)$|mi', $file, $this->info->author);
            preg_match ('|@contributor(.*)$|mi', $file, $this->info->contributor);
            preg_match_all ('|@license(.*)$|mi', $file, $this->info->license);
            preg_match_all ('|@link(.*)$|mi', $file, $this->info->link);
            preg_match ('|@settings(.*)$|mi', $file, $this->settings);

            foreach($this->info as $key => $value)
            {
                if(in_array($key, array('author','license','link')))
                {
                    $this->info->{$key} = trim(@$value[1][1]);
                }
                else
                {
                    $this->info->{$key} = trim(@$value[1]);
                }
            }

            if(! empty($this->settings))
            {
                $this->settings = json_decode(trim($this->settings[1]));
            }
        }
    }

    /**
     * Model Registry
     * Generate model registry for O2System
     */
    class Model extends Registry
    {
        public $app_name;
        public $module_name;
        public $module_type;
        public $class_name;
        public $path;
        public $path_name;
        public $path_alias;
        public $filename;
        public $filepath;

        /**
         * Class constructor
         *
         * @return    void
         */
        public function __construct($filepath = '')
        {
            // Register Filepath
            $this->filepath = str_replace('//', '/', $filepath);

            // Register Filename
            $this->filename = pathinfo($this->filepath, PATHINFO_BASENAME);

            $alias = array();
            $x_app_name = str_replace(APPPATH, '', $filepath);
            $x_app_name = explode('/', $x_app_name);

            $this->app_name = reset($x_app_name);
            $this->app_name = $this->app_name == '' ? 'app' : $this->app_name;

            array_push($alias, $this->app_name);

            $sub_path = '';
            if(count($x_app_name) > 3)
            {
                $this->module_type = $x_app_name[1];
                array_push($alias, $this->module_type);

                $this->module_name = $x_app_name[2];
                array_push($alias, $this->module_name);

                if(isset($x_app_name[4]) AND $x_app_name[4] !== $this->filename)
                {
                    array_pop($x_app_name);
                    $x_app_name = array_slice($x_app_name, 4);
                    $alias = array_merge($alias, $x_app_name);
                }
            }

            // Register path_name
            $this->path_name = pathinfo($this->filepath, PATHINFO_FILENAME);

            array_push($alias, $this->path_name);
            $this->path_alias = implode('/', array_filter($alias));
            $this->path_alias = str_replace('app/','', $this->path_alias);

            $this->path = pathinfo($this->filepath, PATHINFO_DIRNAME).'/';
            $this->class_name = \O2System\prepare_class_name($this->path_name);

            unset($this->code, $this->info, $this->settings);
        }
    }

    class Helper extends Registry
    {
        public $app_name;
        public $module_name;
        public $module_type;
        public $path;
        public $path_name;
        public $path_alias;
        public $filename;
        public $filepath;

        /**
         * Class constructor
         *
         * @return    void
         */
        public function __construct($filepath = '')
        {
            // Register Filepath
            $this->filepath = str_replace('//', '/', $filepath);

            // Register Filename
            $this->filename = pathinfo($this->filepath, PATHINFO_BASENAME);

            $alias = array();
            $x_app_name = str_replace(array(BASEPATH,APPPATH), '', $filepath);
            $x_app_name = explode('/', $x_app_name);

            $this->app_name = reset($x_app_name);

            if(strpos($filepath, 'system') !== FALSE)
            {
                $this->app_name = 'system';
            }

            if(strpos($filepath, 'application') !== FALSE AND $this->app_name == '')
            {
                $this->app_name = 'app';
            }

            array_push($alias, $this->app_name);

            // Register path_name
            $this->path_name = pathinfo($this->filepath, PATHINFO_FILENAME);
            $this->path_name = str_replace('_helper', '', $this->path_name);

            array_push($alias, $this->path_name);
            $this->path_alias = implode('/', array_filter($alias));

            $this->path = pathinfo($this->filepath, PATHINFO_DIRNAME).'/';

            unset($this->code, $this->info, $this->settings);
        }
    }

    class View extends Registry
    {
        public $app_name;
        public $module_name;
        public $path;
        public $path_name;
        public $path_alias;
        public $filename;
        public $filepath;

        /**
         * Class constructor
         *
         * @return    void
         */
        public function __construct($filepath = '')
        {
            // Register Filepath
            $this->filepath = str_replace('//', '/', $filepath);

            // Register Filename
            $this->filename = pathinfo($this->filepath, PATHINFO_BASENAME);

            $x_app_name = str_replace(array(APPPATH, 'views/', $this->filename), '', $filepath);
            $x_app_name = explode('/', $x_app_name);

            $this->app_name = reset($x_app_name);
            $this->app_name = $this->app_name == '' ? 'app' : $this->app_name;

            $alias = array(
                $this->app_name,
                $this->module_name
            );

            // Register path_name
            $this->path_name = pathinfo($this->filepath, PATHINFO_FILENAME);
            $this->path_name = str_replace('_view', '', $this->path_name);

            array_push($alias, $this->path_name);
            $this->path_alias = implode('/', array_filter($alias));
            $this->path_alias = str_replace('app/','', $this->path_alias);

            $this->path = pathinfo($this->filepath, PATHINFO_DIRNAME).'/';

            unset($this->code, $this->info, $this->settings);
        }
    }

    class Language extends Registry
    {
        public $app_name;
        public $module_name;
        public $path;
        public $path_name;
        public $path_alias;
        public $filename;
        public $filepath;

        /**
         * Class constructor
         *
         * @return    void
         */
        public function __construct($filepath = '')
        {
            // Register Filepath
            $this->filepath = str_replace('//', '/', $filepath);

            // Register Filename
            $this->filename = pathinfo($this->filepath, PATHINFO_BASENAME);

            $x_app_name = str_replace(array(APPPATH, 'views/', $this->filename), '', $filepath);
            $x_app_name = explode('/', $x_app_name);

            $this->app_name = reset($x_app_name);
            $this->app_name = $this->app_name == '' ? 'app' : $this->app_name;

            $alias = array(
                $this->app_name,
                $this->module_name
            );

            // Register path_name
            $this->path_name = pathinfo($this->filepath, PATHINFO_FILENAME);
            $this->path_name = str_replace('_view', '', $this->path_name);

            array_push($alias, $this->path_name);
            $this->path_alias = implode('/', array_filter($alias));
            $this->path_alias = str_replace('app/','', $this->path_alias);

            $this->path = pathinfo($this->filepath, PATHINFO_DIRNAME).'/';

            unset($this->code, $this->info, $this->settings);

            print_out($this);
        }
    }

    /**
     * Theme Registry
     * Generate theme registry  for O2System
     */
    class Template extends Registry
    {
        /**
         * Theme URL
         *
         * @var string
         */
        public $URL;

        /**
         * Theme Screenshot
         *
         * @var string
         */
        public $screenshot;

        /**
         * Theme Active Layout
         *
         * @var string
         */
        public $layout;

        /**
         * Theme Blocks
         *
         * @var array
         */
        public $blocks = array();

        /**
         * Theme Pages
         *
         * @var array
         */
        public $pages = array();

        /**
         * Theme Modules Replacement
         *
         * @var array
         */
        public $modules = array();

        /**
         * Theme Layouts Alternative
         *
         * @var array
         */
        public $layouts = array();

        /**
         * Theme Settings
         *
         * @var object
         */
        public $settings;

        /**
         * Class constructor
         *
         * @return    void
         */
        public function __construct($path_name, $path)
        {
            // Register pathname
            $this->path_name = $path_name;

            // Register Path
            $this->path = $path;

            // Register Screenshot
            if(file_exists($this->path.'screenshot.jpg'))
            {
                $this->screenshot = $this->path.'screenshot.jpg';
            }

            // Register default layout
            if(file_exists($this->path.'layout.tpl'))
            {
                $this->layout = $this->path.'layout.tpl';
            }

            // Register Info
            $this->load_info($this->path . 'template.json');

            if(isset($this->info->settings))
            {
                $this->settings = $this->info->settings;
                unset($this->info->settings);
            }
        }
    }
}