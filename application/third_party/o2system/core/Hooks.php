<?php
/**
 * Created by PhpStorm.
 * User: Steeven Andrian
 * Date: 10/01/2015
 * Time: 8:23
 */

namespace O2System;

require_once BASEPATH.'core/Hooks.php';

class Hooks extends \CI_Hooks
{
    /**
     * Initialize the Hooks Preferences
     *
     * @access	private
     * @return	void
     */
    function _initialize()
    {
        $CFG =& \O2System\load_class('Config', 'core');

        // If hooks are not enabled in the config file
        // there is nothing else to do

        if ($CFG->item('enable_hooks') == FALSE)
        {
            return;
        }

        // Grab the "hooks" definition file.
        // If there are no hooks, we're done.

        if (defined('ENVIRONMENT') AND is_file(APPPATH.'config/'.ENVIRONMENT.'/hooks.php'))
        {
            include(APPPATH.'config/'.ENVIRONMENT.'/hooks.php');
        }
        elseif (is_file(APPPATH.'config/hooks.php'))
        {
            include(APPPATH.'config/hooks.php');
        }


        if ( ! isset($hook) OR ! is_array($hook))
        {
            return;
        }

        $this->hooks =& $hook;
        $this->enabled = TRUE;
    }

    // --------------------------------------------------------------------
}