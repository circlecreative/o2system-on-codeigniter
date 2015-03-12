<?php
namespace O2System;

/**
 * Class registry
 *
 * This function acts as a singleton.  If the requested class does not
 * exist it is instantiated and set to a static variable.  If it has
 * previously been instantiated the variable is returned.
 *
 * Borrowed function from CodeIgniter 3.0-dev
 *
 * @author        EllisLab Dev Team
 * @contributor Steeven Andrian Salim
 * @copyright    Copyright (c) 2008 - 2014, EllisLab, Inc. (http://ellislab.com/)
 * @copyright    Copyright (c) 2014, British Columbia Institute of Technology (http://bcit.ca/)
 * @license        http://opensource.org/licenses/MIT	MIT License
 * @link        http://codeigniter.com
 * @since        Version 2.0.0
 *
 * @param    string    the class name being requested
 * @param    string    the directory where the class should be found
 * @param    string    the class name prefix
 * @return    object
 */
if (!function_exists('load_class'))
{
    function &load_class($class, $directory = 'libraries', $prefix = 'CI')
    {
        static $_classes = array();
        // Does the class exist?  If so, we're done...
        if (isset($_classes[$class]))
        {
            return $_classes[$class];
        }
        $name = FALSE;
        // Look for the class first in the local application/libraries folder
        // then in the native system/libraries folder
        $class_names = array(
            prepare_class_name($prefix . '_' . $class) => BASEPATH . $directory . '/' . prepare_class_name($class) . EXT,
            prepare_class_name('App_' . $class) => APPPATH . $directory . '/' . prepare_class_name('App_' . $class) . EXT
        );
        $name = FALSE;
        foreach ($class_names as $class_name => $class_filepath)
        {

            // O2System Overriding Class
            $o2system_filepath = str_replace(BASEPATH, SYSPATH, $class_filepath);

            if (file_exists($o2system_filepath) AND $class_name == $prefix . '_' . $class)
            {
                $class_filepath = $o2system_filepath;
                $class_name = '\\O2System\\' . $class;
            }

            if (file_exists($class_filepath))
            {
                $name = $class_name;
                if (class_exists($name) === FALSE)
                {
                    require $class_filepath;
                }
            }
        }
        // Did we find the class?
        if ($name === FALSE)
        {
            // Note: We use exit() rather then show_error() in order to avoid a
            // self-referencing loop with the Excptions class
            exit('Unable to locate the specified class: ' . $class . '.php');
        }
        // Keep track of what we just loaded
        \O2System\is_loaded($class);
        $_classes[$class] = new $name();

        return $_classes[$class];
    }
}
// --------------------------------------------------------------------

/**
 * Returns Fixed Class Name according to O2System Class Name standards
 *
 * @param    $class    String class name
 * @return    mixed
 */
if (!function_exists('prepare_class_name'))
{
    function prepare_class_name($class)
    {
        $class = trim($class);
        if (!empty($class) or $class != '')
        {
            $patterns = array(
                '/[\s]+/',
                '/[^a-zA-Z0-9_-\s]/',
                '/[_]+/',
                '/[-]+/',
                '/-/',
                '/[_]+/'
            );
            $replace = array(
                '-',
                '-',
                '-',
                '-',
                '_',
                '_'
            );
            $class = preg_replace($patterns, $replace, $class);
        }

        //$class = preg_split('[_]', $class, -1, PREG_SPLIT_NO_EMPTY);
        $class = explode('_', $class);

        $class_name = array_map(
            function ($class_name)
            {
                $class_name = trim($class_name);
                $class_name = str_replace('_','', $class_name);
                return ucfirst($class_name);
            }, $class
        );

        // Remove Duplicates
        $class_name = array_unique($class_name);
        $class_name = array_filter($class_name);

        return implode('_', $class_name);
    }
}
// ------------------------------------------------------------------------

/**
 * Returns Fixed Class Name according to O2System Class Name standards
 *
 * @param    $class    String class name
 * @return    mixed
 */
if (!function_exists('prepare_filename'))
{
    function prepare_filename($filename)
    {
        $filename = preg_split('[/]', $filename, -1, PREG_SPLIT_NO_EMPTY);

        // Remove Duplicates
        $filename = array_unique($filename);

        $filename = array_map(
            function ($filename)
            {
                return prepare_class_name($filename);
            }, $filename
        );

        return implode('/', $filename);
    }
}
// ------------------------------------------------------------------------

/**
 * Keeps track of which libraries have been loaded.  This function is
 * called by the load_class() function above
 *
 * Borrowed function from CodeIgniter 3.0-dev
 *
 * @author        EllisLab Dev Team
 * @copyright    Copyright (c) 2008 - 2014, EllisLab, Inc. (http://ellislab.com/)
 * @copyright    Copyright (c) 2014, British Columbia Institute of Technology (http://bcit.ca/)
 * @license        http://opensource.org/licenses/MIT	MIT License
 * @link        http://codeigniter.com
 * @since        Version 2.0.0
 *
 * @return    array
 */
if (!function_exists('is_loaded'))
{
    function &is_loaded($class = '')
    {
        static $_is_loaded = array();
        if ($class != '')
        {
            $_is_loaded[strtolower($class)] = $class;
        }
        return $_is_loaded;
    }
}
// ------------------------------------------------------------------------