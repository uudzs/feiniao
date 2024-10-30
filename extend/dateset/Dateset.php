<?php
declare (strict_types = 1);

namespace dateset;

/**
 * 日期时间处理类
 */
class Dateset
{
    const YEAR = 31536000;
    const MONTH = 2592000;
    const WEEK = 604800;
    const DAY = 86400;
    const HOUR = 3600;
    const MINUTE = 60;

	/**
	 * 间隔时间段格式化
	 * @param int $time 时间戳
	 * @param string $format 格式 【d：显示到天 i显示到分钟 s显示到秒】
	 * @return string
	 */
	function time_trans($time, $format = 'd')
	{
		$now = time();
		$diff = $now - $time;
		if ($diff < 60) {
			return '1分钟前';
		} else if ($diff < 3600) {
			return floor($diff / 60) . '分钟前';
		} else if ($diff < 86400) {
			return floor($diff / 3600) . '小时前';
		}
		$yes_start_time = strtotime(date('Y-m-d 00:00:00', strtotime('-1 days'))); //昨天开始时间
		$yes_end_time = strtotime(date('Y-m-d 23:59:59', strtotime('-1 days'))); //昨天结束时间
		$two_end_time = strtotime(date('Y-m-d 23:59:59', strtotime('-2 days'))); //2天前结束时间
		$three_end_time = strtotime(date('Y-m-d 23:59:59', strtotime('-3 days'))); //3天前结束时间
		$four_end_time = strtotime(date('Y-m-d 23:59:59', strtotime('-4 days'))); //4天前结束时间
		$five_end_time = strtotime(date('Y-m-d 23:59:59', strtotime('-5 days'))); //5天前结束时间
		$six_end_time = strtotime(date('Y-m-d 23:59:59', strtotime('-6 days'))); //6天前结束时间
		$seven_end_time = strtotime(date('Y-m-d 23:59:59', strtotime('-7 days'))); //7天前结束时间

		if ($time > $yes_start_time && $time < $yes_end_time) {
			return '昨天';
		}

		if ($time > $yes_start_time && $time < $two_end_time) {
			return '1天前';
		}

		if ($time > $yes_start_time && $time < $three_end_time) {
			return '2天前';
		}

		if ($time > $yes_start_time && $time < $four_end_time) {
			return '3天前';
		}

		if ($time > $yes_start_time && $time < $five_end_time) {
			return '4天前';
		}

		if ($time > $yes_start_time && $time < $six_end_time) {
			return '5天前';
		}

		if ($time > $yes_start_time && $time < $seven_end_time) {
			return '6天前';
		}

		switch ($format) {
			case 'd':
				$show_time = date('Y-m-d', $time);
				break;
			case 'i':
				$show_time = date('Y-m-d H:i', $time);
				break;
			case 's':
				$show_time = date('Y-m-d H:i:s', $time);
				break;
		}
		return $show_time;
	}


    /**
     * 计算两个时间戳之间相差的时间
     *
     * $differ = self::differ(60, 182, 'minutes,seconds'); // array('minutes' => 2, 'seconds' => 2)
     * $differ = self::differ(60, 182, 'minutes'); // 2
     *
     * @param int    $remote timestamp to find the span of
     * @param int    $local  timestamp to use as the baseline
     * @param string $output formatting string
     * @return  string   when only a single output is requested
     * @return  array    associative list of all outputs requested
     * @from https://github.com/kohana/ohanzee-helpers/blob/master/src/Date.php
     */
    public static function differ($remote, $local = null, $output = 'years,months,weeks,days,hours,minutes,seconds')
    {
        // Normalize output
        $output = trim(strtolower((string)$output));
        if (!$output) {
            // Invalid output
            return false;
        }
        // Array with the output formats
        $output = preg_split('/[^a-z]+/', $output);
        // Convert the list of outputs to an associative array
        $output = array_combine($output, array_fill(0, count($output), 0));
        // Make the output values into keys
        extract(array_flip($output), EXTR_SKIP);
        if ($local === null) {
            // Calculate the span from the current time
            $local = time();
        }
        // Calculate timespan (seconds)
        $timespan = abs($remote - $local);
        if (isset($output['years'])) {
            $timespan -= self::YEAR * ($output['years'] = (int)floor($timespan / self::YEAR));
        }
        if (isset($output['months'])) {
            $timespan -= self::MONTH * ($output['months'] = (int)floor($timespan / self::MONTH));
        }
        if (isset($output['weeks'])) {
            $timespan -= self::WEEK * ($output['weeks'] = (int)floor($timespan / self::WEEK));
        }
        if (isset($output['days'])) {
            $timespan -= self::DAY * ($output['days'] = (int)floor($timespan / self::DAY));
        }
        if (isset($output['hours'])) {
            $timespan -= self::HOUR * ($output['hours'] = (int)floor($timespan / self::HOUR));
        }
        if (isset($output['minutes'])) {
            $timespan -= self::MINUTE * ($output['minutes'] = (int)floor($timespan / self::MINUTE));
        }
        // Seconds ago, 1
        if (isset($output['seconds'])) {
            $output['seconds'] = $timespan;
        }
        if (count($output) === 1) {
            // Only a single output was requested, return it
            return array_pop($output);
        }
        // Return array
        return $output;
    }

    /**
     * 获取指定年月拥有的天数
     * @param int $month
     * @param int $year
     * @return false|int|string
     */
    public static function days_in_month($month, $year)
    {
        if (function_exists("cal_days_in_month")) {
            return cal_days_in_month(CAL_GREGORIAN, $month, $year);
        } else {
            return date('t', mktime(0, 0, 0, $month, 1, $year));
        }
    }
}
