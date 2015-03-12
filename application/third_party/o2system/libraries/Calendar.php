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
class O2_Calendar extends CI_Calendar
{
	var $work_per_weeks = 5;

	/**
	 * Generate the calendar
	 *
	 * @access	public
	 * @param	integer	the year
	 * @param	integer	the month
	 * @param	array	the data to be shown in the calendar cells
	 * @return	string
	 */
	function days($year = '', $month = '')
	{
		// Set and validate the supplied month/year
		if ($year == '')
			$year  = date("Y", $this->local_time);

		if ($month == '')
			$month = date("m", $this->local_time);

		if (strlen($year) == 1)
			$year = '200'.$year;

		if (strlen($year) == 2)
			$year = '20'.$year;

		if (strlen($month) == 1)
			$month = '0'.$month;

		$adjusted_date = $this->adjust_date($month, $year);

		$month	= $adjusted_date['month'];
		$year	= $adjusted_date['year'];

		// Determine the total days in the month
		$total_days = $this->get_total_days($month, $year);

		// Set the starting day of the week
		$start_days	= array('sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6);
		$start_day = ( ! isset($start_days[$this->start_day])) ? 0 : $start_days[$this->start_day];

		// Set the starting day number
		$local_date = mktime(12, 0, 0, $month, 1, $year);
		$date = getdate($local_date);
		$day  = $start_day + 1 - $date["wday"];

		while ($day > 1)
		{
			$day -= 7;
		}

		// Set the current month/year/day
		// We use this to determine the "today" date
		$cur_year	= date("Y", $this->local_time);
		$cur_month	= date("m", $this->local_time);
		$cur_day	= date("j", $this->local_time);

		$is_current_month = ($cur_year == $year AND $cur_month == $month) ? TRUE : FALSE;

		$day_names = $this->get_day_names();

		$CI =& get_instance();
		$CI->db->where(array('MONTH(date)' => $cur_month, 'YEAR(date)' => $cur_year));
		$holidays_query = $CI->db->get('plugin_calendar_holidays');


		$holidays = array();

		if($holidays_query->num_rows() > 0)
		{
			$CI->load->helper('date');

			foreach($holidays_query->result() as $row)
            {
                $title = ( is_serialized($row->title) ? unserialize($row->title) : $row->title);
                $title = (element($CI->active_language, $title) ? $title[$CI->active_language] : (is_array($title) ? reset($title) : $title));

                if($row->days == 0 OR $row->days == 1 OR $row->days == null)
                {
                    $holidays[$row->date] = $title;
                }
                else
                {
                    foreach(date_range($row->date, $row->days) as $range_date)
                    {
                        $range_date = date('Y-m-d', $range_date);
                        $holidays[$range_date] = $title;
                    }
                } 
            }
		}

		//print_out($holidays);

		// Build the main body of the calendar
		$week = 1;
		while ($day <= $total_days)
		{
			for ($i = 0; $i < 7; $i++)
			{
				if ($day > 0 AND $day <= $total_days)
				{
					$day_cal = new stdClass;
					$day_cal->date = (strlen($day) > 1 ? $day : '0'.$day);
					$day_cal->day_name = date( 'D', strtotime("$day-$cur_month-$cur_year") );
					$day_cal->db_date = date( 'Y-m-d', strtotime("$day-$cur_month-$cur_year") );
					$day_cal->full_date = date( 'd-m-Y', strtotime("$day-$cur_month-$cur_year") );

					if(array_key_exists($day_cal->db_date, $holidays))
					{
						$day_cal->icon = 'fa fa-circle-o';
						$day_cal->class = 'warning';
						$day_cal->info = $holidays[$day_cal->db_date];
					}
					elseif( $day_cal->day_name == 'Sat' OR $day_cal->day_name == 'Sun')
					{
						$day_cal->icon = 'fa fa-circle-o';
						$day_cal->class = 'danger';
						$day_cal->info = lang(strtoupper('off_time'));
						$day_cal->info = ($day_cal->info == '' ? 'Time Off' : $day_cal->info);
					}
					else
					{
						$day_cal->icon = 'fa fa-clock-o';
						$day_cal->class = 'default';
						$day_cal->info = lang(strtoupper('office_time'));
						$day_cal->info = ($day_cal->info == '' ? 'Office Time' : $day_cal->info);
					}
					
					$out[$day] = $day_cal;
				}				
				$day++;
			}
			$week++;
		}

		return $out;
	}

	// --------------------------------------------------------------------

	public function working_days($year = '', $month = '')
	{
		// Set and validate the supplied month/year
		if ($year == '')
			$year  = date("Y", $this->local_time);

		if ($month == '')
			$month = date("m", $this->local_time);

		if (strlen($year) == 1)
			$year = '200'.$year;

		if (strlen($year) == 2)
			$year = '20'.$year;

		if (strlen($month) == 1)
			$month = '0'.$month;

		$adjusted_date = $this->adjust_date($month, $year);

		$month	= $adjusted_date['month'];
		$year	= $adjusted_date['year'];

		// Determine the total days in the month
		$total_days = $this->get_total_days($month, $year);

		// Set the starting day of the week
		$start_days	= array('sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6);
		$start_day = ( ! isset($start_days[$this->start_day])) ? 0 : $start_days[$this->start_day];

		// Set the starting day number
		$local_date = mktime(12, 0, 0, $month, 1, $year);
		$date = getdate($local_date);
		$day  = $start_day + 1 - $date["wday"];

		while ($day > 1)
		{
			$day -= 7;
		}

		// Set the current month/year/day
		// We use this to determine the "today" date
		$cur_year	= date("Y", $this->local_time);
		$cur_month	= date("m", $this->local_time);
		$cur_day	= date("j", $this->local_time);

		$is_current_month = ($cur_year == $year AND $cur_month == $month) ? TRUE : FALSE;

		// Determine holidays
		$this->CI->load->model('plugins_model');
		$holidays = $this->CI->plugins_model->holidays($month,$year);

		if(empty($holidays))
		{
			$holidays = array();
		}

		while ($day <= $total_days)
		{
			for ($i = 0; $i < 7; $i++)
			{
				if ($day > 0 AND $day <= $total_days)
				{
					$day_name = date( 'D', strtotime("$day-$cur_month-$cur_year") );
					$day_date = date('Y-m-d', strtotime("$day-$cur_month-$cur_year"));

					if(!in_array($day_date, array_keys($holidays)))
					{
						if($this->work_per_weeks == 5)
						{
							if($day_name != 'Sat' AND $day_name != 'Sun')
							{
								$out[] = $day_date;
							}
						}
						elseif($this->work_per_weeks == 6)
						{
							if($day_name != 'Sun')
							{
								$out[] = $day_date;
							}
						}
						elseif($this->work_per_weeks > 6)
						{
							$out[] = $day_date;
						}
					}
				}				
				$day++;
			}
		}

		return $out;
	}
}