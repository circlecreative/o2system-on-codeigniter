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
 * Extended Email Class
 *
 * @package     Application
 * @subpackage  Libraries
 * @category    Library
 * @author      Steeven Andrian Salim
 * @copyright   Copyright (c) 2010 - 2013 PT. Lingkar Kreasi (Circle Creative)
 * @link        http://o2system.net/user_guide/library/smarty.html
 */
// ------------------------------------------------------------------------

class O2_Email extends CI_Email
{
	var $useragent = "O2Mail";
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		// Fixed Upper Charset
		$this->charset = strtoupper($this->charset);
	}

	/**
	 * Set Message Boundary
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _set_boundaries()
	{
		$this->_alt_boundary = "O2Mail_ALT_".uniqid(''); // multipart/alternative
		$this->_atc_boundary = "O2Mail_ATC_".uniqid(''); // attachment boundary
	}

	/**
	 * Prep Q Encoding
	 *
	 * Performs "Q Encoding" on a string for use in email headers.  It's related
	 * but not identical to quoted-printable, so it has its own method
	 *
	 * @access	public
	 * @param	str
	 * @param	bool	// set to TRUE for processing From: headers
	 * @return	str
	 */
	protected function _prep_q_encoding($str, $from = FALSE)
	{
		$str = str_replace(array("\r", "\n"), '', $str);

		if ($this->charset === 'UTF-8')
 		{
			if (MB_ENABLED === TRUE)
			{
				return mb_encode_mimeheader($str, $this->charset, 'Q', $this->crlf);
			}
			elseif (extension_loaded('iconv'))
			{
				$output = @iconv_mime_encode('', $str,
					array(
						'scheme' => 'Q',
						'line-length' => 76,
						'input-charset' => $this->charset,
						'output-charset' => $this->charset,
						'line-break-chars' => $this->crlf
					)
				);

				// There are reports that iconv_mime_encode() might fail and return FALSE
				if ($output !== FALSE)
				{
					// iconv_mime_encode() will always put a header field name.
					// We've passed it an empty one, but it still prepends our
					// encoded string with ': ', so we need to strip it.
					return substr($output, 2);
				}

				$chars = iconv_strlen($str, 'UTF-8');
			}
 		}

		// We might already have this set for UTF-8
		isset($chars) OR $chars = strlen($str);

		$output = '=?'.$this->charset.'?Q?';
		for ($i = 0, $length = strlen($output), $iconv = extension_loaded('iconv'); $i < $chars; $i++)
		{
			// convert all UTF-8 string
			$chr = ($this->charset === 'UTF-8' && $iconv === TRUE)
				? '='.implode('=', str_split(strtoupper(bin2hex(iconv_substr($str, $i, 1, $this->charset))), 2))
				: '='.strtoupper(bin2hex($str[$i]));

			// RFC 2045 sets a limit of 76 characters per line.
			// We'll append ?= to the end of each line though.
			if ($length + ($l = strlen($chr)) > 74)
			{
				$output .= '?='.$this->crlf // EOL
					.' =?'.$this->charset.'?Q?'.$chr; // New line
				$length = 6 + strlen($this->charset) + $l; // Reset the length for the new line
 			}
			else
 			{
				$output .= $chr;
				$length += $l;
 			}

			// Add the character to our temporary line
			$temp .= $char;
		}

		// End the header
		return $output.'?=';
	}

	// --------------------------------------------------------------------

	public function draft()
	{
		if ($this->_replyto_flag == FALSE)
		{
			$this->reply_to($this->_headers['From']);
		}

		if (( ! isset($this->_recipients) AND ! isset($this->_headers['To']))  AND
			( ! isset($this->_bcc_array) AND ! isset($this->_headers['Bcc'])) AND
			( ! isset($this->_headers['Cc'])))
		{
			$this->_set_error_message('lang:email_no_recipients');
			return FALSE;
		}

		$this->_build_headers();

		$this->_build_message();
	}

	public function save_output()
	{
		$output = $this->_header_str;
		$output.= $this->_subject;
		$output.= $this->_finalbody;

		return $output;
	}
}