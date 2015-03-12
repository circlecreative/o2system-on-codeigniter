<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
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
 * Encrypt Class Extension
 *
 * @package     Application
 * @subpackage  Libraries
 * @category    Library
 * @author      Steeven Andrian Salim
 * @copyright   Copyright (c) 2010 - 2013 PT. Lingkar Kreasi (Circle Creative)
 * @link        http://o2system.net/user_guide/library/xml.html
 */
// ------------------------------------------------------------------------
class O2_Encrypt extends CI_Encrypt
{
    /**
     * Class Variables
     * @access public
     */ 
    var $CI;
    var $encryption_key = '';
    var $_hash_type = 'sha1';
    var $_mcrypt_exists = FALSE;
    var $_mcrypt_cipher;
    var $_mcrypt_mode;
    var $characters = 'abcdefghijklmnopqrstuvwxyz-_/';

    /**
     * Constructor
     *
     * Simply determines whether the mcrypt library exists.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }
    // --------------------------------------------------------------------

    /**
     * Set the encryption key
     *
     * @access	public
     * @param	string
     * @return	void
     */
    function set_key($key = '')
    {
        $this->encryption_key = $key;
    }
    // --------------------------------------------------------------------

    /**
     * Fetch the encryption key
     *
     * Returns it as MD5 in order to have an exact-length 128 bit key.
     * Mcrypt is sensitive to keys that are not the correct length
     *
     * @access	public
     * @param	string
     * @return	string
     */
    function get_key($key = '')
    {
        if ($key == '')
        {
            if ($this->encryption_key != '')
            {
                return $this->encryption_key;
            }
            $CI = &get_instance();
            $key = $CI->config->item('encryption_key');
            if ($key == false)
            {
                show_error('In order to use the encryption class requires that you set an encryption key in your config file.');
            }
        }
        return md5($key);
    }
    // --------------------------------------------------------------------

    /**
     * Safe base64_encode
     *
     * Encodes the encryption string using base64_encode but safer characters.
     *
     * @access	public
     * @param	string	the string to encode
     * @param	string	the key
     * @return	string
     */
    public function safe_b64encode($string)
    {
        $data = base64_encode($string);
        $data = str_replace(array(
            '+',
            '/',
            '='
                ), array(
            '-',
            '_',
            ''
                ), $data);
        return $data;
    }
    // --------------------------------------------------------------------

    /**
     * Safe base64_decode
     *
     * Decodes the encryption string using base64_decode but return the safer characters into original characters.
     *
     * @access	public
     * @param	string	the string to encode
     * @param	string	the key
     * @return	string
     */
    public function safe_b64decode($string)
    {
        $data = str_replace(array(
            '-',
            '_'
                ), array(
            '+',
            '/'
                ), $string);
        $mod4 = strlen($data) % 4;
        if ($mod4)
        {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }
    // --------------------------------------------------------------------

    /**
     * Encode
     *
     * Encodes the message string using mcrypt.
     *
     * @access	public
     * @param	string	the string to encode
     * @param	string	the key
     * @return	string
     */
    public function encode($value, $secret = '')
    {
        $secret = ($secret == '' ? $this->encryption_key : $secret);

        if (!$value)
        {
            return false;
        }
        $text = $value;
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $secret, $text, MCRYPT_MODE_ECB, $iv);
        return trim($this->safe_b64encode($crypttext));
    }
    // --------------------------------------------------------------------

    /**
     * Decode
     *
     * Decodes the message string using mcrypt.
     *
     * @access	public
     * @param	string	the string to encode
     * @param	string	the key
     * @return	string
     */
    public function decode($value, $secret = '')
    {
        $secret = ($secret == '' ? $this->encryption_key : $secret);

        if (!$value)
        {
            return false;
        }
        $crypttext = $this->safe_b64decode($value);
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $secret, $crypttext, MCRYPT_MODE_ECB, $iv);
        return trim($decrypttext);
    }
    // --------------------------------------------------------------------

    public function numeric_encode($value)
    {
        $chr_array = str_split($this->characters);
        $val_array = str_split($value);

        $crypt_text = '';

        $i = 1;
        foreach($val_array as $chr)
        {
            if(($key = array_search($chr, $chr_array)) !== false) 
            {
                $crypt_text.= $key. ($i++ != count($val_array) ? '.' : '');
            }
        }
        return $crypt_text;
    }

    public function numeric_decode($value)
    {
        $chr_array = str_split($this->characters);
        $val_array = explode('.',$value);

        $crypt_text = '';

        foreach($val_array as $num)
        {
            $crypt_text.= $chr_array[$num];
        }
        
        return $crypt_text;
    }

    public function tripledes_encode($data, $secret)
    {
        //Generate a key from a hash
        $key = md5(utf8_encode($secret), true);

        //Take first 8 bytes of $key and append them to the end of $key.
        $key .= substr($key, 0, 8);

        //Pad for PKCS7
        $blockSize = mcrypt_get_block_size('tripledes', 'ecb');
        $len = strlen($data);
        $pad = $blockSize - ($len % $blockSize);
        $data .= str_repeat(chr($pad), $pad);

        //Encrypt data
        $encData = mcrypt_encrypt('tripledes', $key, $data, 'ecb');

        return base64_encode($encData);
    }

    public function tripledes_decode($data, $secret)
    {
        //Generate a key from a hash
        $key = md5(utf8_encode($secret), true);

        //Take first 8 bytes of $key and append them to the end of $key.
        $key .= substr($key, 0, 8);

        $data = base64_decode($data);

        $data = mcrypt_decrypt('tripledes', $key, $data, 'ecb');

        $block = mcrypt_get_block_size('tripledes', 'ecb');
        $len = strlen($data);
        $pad = ord($data[$len-1]);

        return substr($data, 0, strlen($data) - $pad);
    }    
}
/* End of file App_Encrypt.php */
/* Location: ./application/libraries/App_Encrypt.php */