<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * O2System
 *
 * Application development framework for PHP 5.1.6 or newer
 *
 * @package      O2System
 * @author       Steeven Andrian Salim
 * @copyright    Copyright (c) 2010 - 2013 PT. Lingkar Kreasi (Circle Creative)
 * @license      http://www.o2system.net/license.html
 * @link         http://www.o2system.net | http://www.circle-creative.com
 */
// ------------------------------------------------------------------------
/**
 * Extended Parser for CodeIgniter
 *
 * @package     Application
 * @subpackage  Libraries
 * @category    Library
 * @author      Steeven Andrian Salim
 * @copyright   Copyright (c) 2010 - 2013 PT. Lingkar Kreasi (Circle Creative)
 * @link        http://o2system.net/user_guide/library/smarty.html
 */
// ------------------------------------------------------------------------

class O2_Parser extends CI_Parser
{
    /**
     * Ignored variables
     * @access  public
     */
    var $_ignored = array();
    var $_ignored_tag = 'ignored';

    /**
     * Strip vars setting variables
     * @access  public
     */    
    var $_strip_vars = false;

    /**
     * Function parser variables
     * @access  public
     */
    var $_conditionals;
    var $helper_delim = ':';
    var $func_l_delim = '[';
    var $func_r_delim = ']';
    var $param_delim = '|';    
    var $options = array
    (
        // convert delimiters in data to entities. { = &#123; } = &#125;
        'convert_delimiters' => array( false, '&#123;', '&#125')
    );    

    /**
     * Class constructor
     *
     * @return  void
     */
    public function __construct()
    {
        // Set new delimiters
        $this->set_delimiters('{{','}}');
    }
    // ------------------------------------------------------------------------

    /**
     * Set strip vars
     * @param  bool
     */
    public function set_stip_vars($strip_vars = false)
    {
        $this->_strip_vars = $strip_vars;
    }
    // ------------------------------------------------------------------------  

    /**
     * Set ignored tag
     * @param  string
     */
    public function set_ignored_tag($tag = 'ignored')
    {
        $this->_ignored_tag = $tag;
    }
    // ------------------------------------------------------------------------

    /**
     * Parse a template
     *
     * Parses pseudo-variables contained in the specified template,
     * replacing them with the data in the second param
     *
     * @access  public
     * @param   string
     * @param   array
     * @param   bool
     * @return  string
     */
    function _parse($template, $data, $return = FALSE)
    {
        if ($template == '')
        {
            return FALSE;
        }

        // have a local references to $template and $data in the class
        $this->_template =& $template;
        $this->_data     =& $data;
        $this->_ignore   = array(); // start empty on        

        // Store ignore tags
        $this->_store_ignored();
        
        foreach ($data as $key => $val)
        {
            // Parse Object
            if (is_object($val))
            {
                $template = $this->_parse_object($key, $val, $template);                
            }
            // Parse Array
            else if (is_array($val))
            {
                $template = $this->_parse_pair($key, $val, $template);
            }
            // Parse Single
            else
            {
                $template = $this->_parse_single($key, (string)$val, $template);
            }
        }

        // Parse Function
        $template = $this->_parse_function($template);     

        // Parse Conditional
        $this->_conditionals = $this->_find_nested_conditionals($template);
        if(! empty ($this->_conditionals))
        {
            $template = $this->_parse_conditionals($template);
        }     

        // retrieve all ignored data
        if(!empty($this->_ignore))
        {
            $this->_restore_ignored();
        }

        // Strip empty pseudo-variables
        if ($this->_strip_vars)
        {
            // Todo: Javascript with curly brackets most times generates an error
            if (preg_match_all("(".$this->l_delim."([^".$this->r_delim."/]*)".$this->r_delim.")", $template, $m))
            {
                foreach($m[1] as $value)
                {
                    $template = preg_replace('#'.$this->l_delim.$value.$this->r_delim.'(.+)'.$this->l_delim.'/'.$value.$this->r_delim.'#sU', "", $template);
                    // preg_replace('#'.$this->l_delim.$value.$this->r_delim.'(.+)'.$this->l_delim.'/'.$value.$this->r_delim.'#sU', "", $template);
                    $template = str_replace ("{".$value."}", "", $template);
                }
            }
        }        

        if ($return == FALSE)
        {
            $CI =& get_instance();
            $CI->output->append_output($template);
        }

        return $template;
    }
    // ------------------------------------------------------------------------

    /**
     * Store ignored tags
     *
     * @access  private
     * @param   string (tag name)
     * @return  bool
     */
    private function _store_ignored()
    {
        if (! preg_match_all("|".$this->l_delim . $this->_ignored_tag . $this->r_delim."(.+)".$this->l_delim . '/' . $this->_ignored_tag . $this->r_delim."|sU", $this->_template, $matches, PREG_SET_ORDER))
        {
            return false;
        }

        foreach( $matches as $key => $tagpair)
        {
            // store $tagpair[1] and replace $tagpair[0] in template with unique identifier
            $this->_ignore[$this->_ignored_tag.$key] = array(
                'txt' => $tagpair[1],
                'id'  => '__'.$this->_ignored_tag.$key.'__'
            );
            // strip it and place a temporary string
            $this->_template = str_replace($tagpair[0], $this->_ignore[$this->_ignored_tag.$key]['id'], $this->_template);
        }
        return true;
    }
    // --------------------------------------------------------------------

    /**
     * Restore ignored tags
     *
     * @access  private
     * @return  bool
     */
    private function _restore_ignored()
    {
        foreach($this->_ignore as $key => $item)
        {
            $this->_template = str_replace($item['id'], $item['txt'], $this->_template);
        }
        // data stored in $this->_template
        return true;
    }
    // --------------------------------------------------------------------

    /**
     *  Parse an Object-Method-Call
     *
     * Sample:  {object:method}
     *
     * @access  private
     * @param   string
     * @param   object
     * @param   string
     * @return  string
     */
    function _parse_object($key, $val, $template)
    {
        preg_match_all("|" . preg_quote($this->l_delim) . $key . "->" . "(.+?)" . preg_quote($this->r_delim) . "|", $template, $match);

        $i = -1;
        while( ++$i < count($match[0]) )
        {
            $key    = $match[0][$i];
            $source = $match[1][$i];

            if( method_exists($val, $source) )
            {
                $template = str_replace($key, $val->$source(), $template);  
            }
            else if( property_exists($val, $source) )
            {
                if(is_string($val->$source))
                {
                    $template = str_replace($key, $val->$source, $template); 
                }
                else
                {
                    $key = str_replace($this->l_delim, '', $key);
                    $key = str_replace($this->r_delim, '', $key);
                    
                    $template = $this->_parse_pair($key, $val->$source, $template);
                }                   
            }
        }
        
        return $template;
    }
    // ------------------------------------------------------------------------

    /**
     * Find conditional statements
     *
     * Note: This function will ignore no matched or conditional statements with errors
     * TODO: restore usage of custom left and right delimiter
     *
     * @access    private
     * @param     string (template html)
     * @return    bool
     */
    private function _find_nested_conditionals($template)
    {
        // any conditionals found?
        $f = strpos($template, '{if');
        if ($f === false)
        {
            return false;
        }
        $found_ifs = array();
        $found_open = strpos($template, '{if');
        while ( $found_open !== false)
        {
            $found_ifs[] = $found_open;
            $found_open = strpos($template, '{if', $found_open+3);
        }
        // print_r($conditionals);
        // -----------------------------------------------------------------------------
        // find all nested ifs. Yeah!
        for($key = 0; $key < sizeof($found_ifs); ++$key)
        {
            $open_tag = $found_ifs[$key];
            $found_close = strpos($template, '{/if}', $open_tag);
            if($found_close === false){ echo("\n Error. No matching /if found for opening tag at: $open_tag"); exit(); }
            $new_open  = $open_tag;
            $new_close = $found_close;
            // -------------------------------------------------------------------------
            // find new {if  inside a chunk, if found find next close tag
            $i=0; // fail safe, for now test 100 nested ifs maximum :-)
            $found_blocks=array();
            do
            {
                // does it have an open_tag inside?
                $chunk = substr($template, $new_open+3, $new_close - $new_open - 3);
                $found_open = strpos($chunk, '{if');
                if($found_open !== false)
                {
                    $new_close = $new_close+5;
                    $new_close = strpos($template, '{/if}', $new_close);
                    if($new_close===false) { echo("\n Error. No matching /if found for opening tag at: $found_open"); exit(); }
                    $new_open = $new_open + $found_open + 3;
                    $found_blocks[] = $new_open;
                }
                $i++;
            }
            while( $found_open !== FALSE && ($i < 100) );
            // store it
            $length = $new_close - $open_tag + 5; // + 5 to catch closing tag
            $chunk = substr($template, $open_tag, $length);
            $conditionals[$open_tag]=array
            (
                'start'    => $open_tag,
                'stop'     => $open_tag + $length,
                'raw_code' => $chunk,
                'found_blocks' => $found_blocks
            );
        }// end for all found ifs
        // walk thru conditionals[] and extract condition_string and replace nested
        $regexp = '#{if (.*)}(.*){/if}#sU';
        foreach($conditionals as $key => $conditional)
        {
            $found_blocks = $conditional['found_blocks'];
            $conditional['parse'] = $conditional['raw_code'];
            if(!empty($found_blocks))
            {
                foreach($found_blocks as $num)
                {
                    // it contains another conditional, replace with unique identifier for later
                    $unique = "__pparse{$num}__";
                    $conditional['parse'] = str_replace($conditionals[$num]['raw_code'], $unique, $conditional['parse']);
                }
            }
            $conditionals[$key]['parse'] = $conditional['parse'];
            if(preg_match($regexp, $conditional['parse'], $preg_parts, PREG_OFFSET_CAPTURE))
            {
                // echo "\n"; print_r($preg_parts);
                $raw_code = $preg_parts[0][0];
                $cond_str = $preg_parts[1][0] !=='' ? $preg_parts[1][0] : '';
                $insert   = $preg_parts[2][0] !=='' ? $preg_parts[2][0] : '';
                if($raw_code !== $conditional['parse']){ echo "\n Error. raw_code differs from first run!\n$raw_code\n{$conditional['raw_code']}";exit; }
                if(preg_match('/({|})/', $cond_str, $problematic_conditional))
                {
                    // Problematic conditional, delimiters found or something
                    // if strip_vars, remove whole raw_code, for now bye-bye
                    echo "\n Error. Found delimiters in condition to test\n: $cond_str";
                    exit;
                }
                // store condition string and insert
                $conditionals[$key]['cond_str'] = $cond_str;
                $conditionals[$key]['insert']   = $insert;
            }
            else
            {
                echo "\n Error in conditionals (preg parse) No conditional found or some was not closed properly";
                exit();
                // todo
                $conditionals[$key]['cond_str'] = '';
                $conditionals[$key]['insert']   = '';
            }
        }
        return $conditionals;
    }
    // -------------------------------------------------------------------------
    
    /**
     * Parse conditional statements
     *
     * Note: This function will ignore no matched or conditional statements with errors
     *
     * @access    private
     * @param     string (template html)
     * @return    string
     */
    private function _parse_conditionals($template)
    {
        if(empty ($this->_conditionals))
        {
            return $template;
        }
        $conditionals =& $this->_conditionals;
        foreach($conditionals as $key => $conditional)
        {
            $raw_code = $conditional['raw_code'];
            $cond_str = $conditional['cond_str'];
            $insert   = $conditional['insert'];
            if($cond_str!=='' AND !empty($insert))
            {
                // Get the two values
                $cond = preg_split("/(\!=|==|<=|>=|<>|<|>|AND|XOR|OR|&&)/", $cond_str);
                // Do we have a valid if statement?
                if(count($cond) == 2)
                {
                    // Get condition and compare
                    preg_match("/(\!=|==|<=|>=|<>|<|>|AND|XOR|OR|&&)/", $cond_str, $cond_m);
                    array_push($cond, $cond_m[0]);
                    // Remove quotes - they cause to many problems!
                    // trim first, removes whitespace if there are no quotes
                    $cond[0] = preg_replace("/[^a-zA-Z0-9_\s\.,-]/", '', trim($cond[0]));
                    $cond[1] = preg_replace("/[^a-zA-Z0-9_\s\.,-]/", '', trim($cond[1]));
                    if(is_int($cond[0]) && is_int($cond[1]))
                    {
                        $delim = "";
                    }
                    else
                    {
                        $delim ="'";
                    }
                    // Test condition
                    $to_eval = "\$result = ($delim$cond[0]$delim $cond[2] $delim$cond[1]$delim);";
                    eval($to_eval);
                }
                else // single value
                {
                    // name isset() or number. Boolean data is 0 or 1
                    $result = (isset($this->_data[trim($cond_str)]) OR (intval($cond_str) AND (bool)$cond_str));
                }
            }
            else
            {
                $result = false;
            }
            // split insert text if needed. Can be '' or 'foo', or 'foo{else}bar'
            $insert = explode('{else}', $insert, 2);
            if($result == TRUE)
            {
                $conditionals[$key]['insert'] = $insert[0];
            }
            else // result = false
            {
                $conditionals[$key]['insert'] = (isset($insert['1'])?$insert['1']:'');
            }
            // restore raw_code from nested conditionals in this one
            foreach($conditional['found_blocks'] as $num)
            {
                $unique = "__pparse{$num}__";
                if(strpos($conditional['insert'], $unique))
                {
                    $conditionals[$key]['insert'] = str_replace($unique, $conditionals[$num]['raw_code'], $conditionals[$key]['insert']);
                }
            }
        }
        // end foreach conditionals.
        // replace all rawcodes with inserts in the template
        foreach($conditionals as $conditional) $template = str_replace($conditional['raw_code'], $conditional['insert'], $template);
        return $template; // thank you, have a nice day!
    }
    // --------------------------------------------------------------------
    
    /**
     * Parse function statements
     *
     * @access    private
     * @param     string (template html)
     * @return    string
     */    
    private function _parse_function($template)
    {
        // Now lets find our functions
        $pattern = '/%1$s(([a-z0-9_]+)%2$s)?([a-z0-9_]+)(\%3$s([^%4$s]+)\])?%5$s/';
        $pattern = sprintf($pattern, $this->l_delim, $this->helper_delim, $this->func_l_delim, $this->func_r_delim, $this->r_delim);
        
        if(preg_match_all($pattern, $template, $matches))
        {
            $matches_count = count($matches[0]);
            
            for($i = 0; $i < $matches_count; $i++)
            {
                // If a value is returned for this tag, use it. If not, perhaps it is text?
                // If there is a helper file (not a native PHP function) and it has not yet been loaded
                if(!empty($matches[2][$i]))
                {
                    // This includes the helper, but will only include it once
                    $this->CI->load->helper($matches[2][$i]);
                }

                if(function_exists($matches[3][$i]))
                {
                    if($matches[5][$i] !== '')
                    {
                        $from[] = $matches[0][$i];
                        $to[] = call_user_func_array( $matches[3][$i], $this->_parse_function_parameters($matches[5][$i]) );
                        $template = str_replace($from, $to, $template);
                    }
                    else
                    {
                        $from[] = $matches[0][$i];
                        $to[] = $matches[3][$i]();
                        $template = str_replace($from, $to, $template);
                    }
                }
            }
        }
        
        return $template;
    }
    // --------------------------------------------------------------------

    /**
     * Parse function call parameters
     *  
     * Parse a parameter string into an array of parameters
     *
     * @access  private
     * @param   string
     * @return  string
     */
    private function _parse_function_parameters($parameter_string = '')
    {
        $double_string = '"[^(\\'.$this->func_r_delim .'|\\'. $this->param_delim.')]*"';
        $single_string = '\'[^(\\'.$this->func_r_delim .'|\\'. $this->param_delim.')]*\'';
        $bool = '(true|false|null)';
        $int = '[0-9., ]+';
        $variable = '\$[a-z_]+';
        
        $pattern = sprintf('/(\%s|\%s)?(%s|%s|%s|%s|%s)+/i', $this->func_l_delim, $this->param_delim, $double_string, $single_string, $bool, $int, $variable);

        preg_match_all($pattern, $parameter_string, $matches);

        $matches_count = count($matches[0]);
        $dirty_parameters =& $matches[2];
        
        $parameters = array();
        foreach($dirty_parameters as $param)
        {
            $first_char = substr($param, 0, 1);

            switch( $first_char )
            {
                // Parameter is a string, remove first and last "" or ''
                case "'":
                case '"':
                    if(strpos($param,'{'))
                    {
                        $param = substr($param, 2, -2);
                        $param = preg_split('[,]', $param, - 1, PREG_SPLIT_NO_EMPTY);
                        
                        if(count($param) > 1)
                        {
                            foreach($param as $array)
                            {
                                if(strpos($array,':'))
                                {
                                    $_split = preg_split('[:]', $array, - 1, PREG_SPLIT_NO_EMPTY);
                                    $param[reset($_split)] = end($_split);
                                    array_shift($param);
                                }
                            }
                        }
                        elseif(strpos(reset($param),':'))
                        {
                            $param = explode(':',reset($param));
                            $param = array(reset($param) => end($param));
                        }
                    }
                    else
                    {
                        $param = substr($param, 1, -1);
                    }
                break;

                // Parameter is a CI view variable
                case '$':
                    $param = substr($param, 1);
                    $param = array_key_exists($param, $this->_data) ? $this->_data[$param] : NULL;
                break;
                
                // What else could it be?
                default:
                    // Param is true/TRUE
                    if(strtoupper($param) === 'TRUE')
                    {
                        $param = TRUE;
                    }
                    
                    // Param is false/FALSE
                    elseif(strtoupper($param) === 'FALSE')
                    {
                        $param = FALSE;
                    }
                    
                    // Param is null/NULL
                    elseif(strtoupper($param) === 'NULL')
                    {
                        $param = NULL;
                    }
                break;
            }
            
            $parameters[] = $param;
        }
        
        return $parameters;
    } 
    // --------------------------------------------------------------------   

}