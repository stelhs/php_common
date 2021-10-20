<?php

define("EINVAL", 1); /* Inctorrect input parameters */
define("EBASE", 2); /* Database error */
define("ESQL", 3); /* SQL error */
define("ENOTUNIQUE", 4); /* Element not enique */
define("EBUSY", 5); /* Resource or device is busy */
define("ENODEV", 22);  /* No device or resourse found  */
define("ECONNFAIL", 42); /* Connection fault */
define("EPARSE", 137); /* Parsing error */


/**
 * DEPRICATED: Logging function
 * @param $msg_level LOG_ERR or LOG_WARNING or LOG_NOTICE
 * @param $text - error description
 */
function msg_log($msg_level, $text)
{
    global $_CONFIG, $utility_name;
    $display_log_level = LOG_ERR;

    if (defined("MSG_LOG_LEVEL"))
        $display_log_level = MSG_LOG_LEVEL;

    if ($msg_level > $display_log_level)
        return;

    syslog($msg_level, $utility_name . ': ' . $text);
    switch ($msg_level)
    {
        case LOG_WARNING:
            echo $utility_name . ': Warning: ' . $text . "\n";
            break;

        case LOG_NOTICE:
            echo $utility_name . ': ' . $text . "\n";
            break;

        case LOG_ERR:
            echo $utility_name . ': Error: ' . $text . "\n";
            break;
    }
}

$perror_pnotice_disabled = false;
function p_disable()
{
    global $perror_pnotice_disabled;
    $perror_pnotice_disabled = true;
}

function p_enable()
{
    global $perror_pnotice_disabled;
    $perror_pnotice_disabled = false;
}

function perror()
{
    global $perror_pnotice_disabled;
    if ($perror_pnotice_disabled)
        return;

    $argv = func_get_args();
    $format = array_shift($argv);
    $msg = vsprintf($format, $argv);
    $f = fopen('php://stderr','w');
    fwrite($f, $msg);
}

function pnotice()
{
    global $perror_pnotice_disabled;
    if ($perror_pnotice_disabled)
        return;

    $argv = func_get_args();
    $format = array_shift($argv);
    $msg = vsprintf($format, $argv);
    $f = fopen('php://stdout','w');
    fwrite($f, $msg);
}

// Read logs by: "sudo journalctl -t $subsystem -f"
function plog($log_level, $subsystem, $msg)
{
    global $perror_pnotice_disabled;

    $prio_text = ["LOG_EMERG",
        "LOG_ALERT",
        "LOG_CRIT",
        "LOG_ERR",
        "LOG_WARNING",
        "LOG_NOTICE",
        "LOG_INFO",
        "LOG_DEBUG"];

    openlog($subsystem, LOG_NDELAY, LOG_USER);
    syslog($log_level, $msg);

    $cmd = sprintf("%s: %s: %s\n", $prio_text[$log_level], $subsystem, $msg);
    if ($log_level <= 4)
        perror($cmd);
    else
        pnotice($cmd);
}

function backtrace_to_str($skip = 0)
{
    $bt = debug_backtrace();
    array_shift($bt);
    if ($skip)
        for($i = 0; $i < $skip; $i++)
            array_shift($bt);

    $str = "Backtrace: \n";

    $num = 1;
    foreach ($bt as $row) {
        $args = '';
        if (isset($row['args']) and count($row['args'])) {
            $sep = '';
            foreach ($row['args'] as $arg) {
                $args .= sprintf("%s%s", $sep, print_r($arg, 1));
                $sep = ', ';
            }
        }

        $str .= sprintf("%d: %s:%d %s(%s)\n",
                        $num, $row['file'], $row['line'],
                        $row['function'], $args);
        $num ++;
    }
    return $str;
}

class Plog {
    function __construct($subsystem)
    {
        $this->subsystem = $subsystem;
    }

    private function line($msg)
    {
        $trace = debug_backtrace();
        array_shift($trace);
        $info = array_shift($trace);
        $line = $info['line'];
        $file = $info['file'];
        $info = array_shift($trace);
        $function = $info['function'];
        return sprintf("%s: %s() +%d: %s", $file, $function, $line, $msg);
    }

    function err()
    {
        $argv = func_get_args();
        $format = array_shift($argv);
        $msg = vsprintf($format, $argv);
        $msg .= "\n" . backtrace_to_str();
        plog(LOG_ERR, $this->subsystem, $this->line($msg));
    }

    function warn()
    {
        $argv = func_get_args();
        $format = array_shift($argv);
        $msg = vsprintf($format, $argv);
        plog(LOG_WARNING, $this->subsystem, $msg);
    }

    function info()
    {
        $argv = func_get_args();
        $format = array_shift($argv);
        $msg = vsprintf($format, $argv);
        plog(LOG_INFO, $this->subsystem, $msg);
    }
}

function dump($msg)
{
    global $perror_pnotice_disabled;
    if ($perror_pnotice_disabled)
        return;

    print_r($msg);
    print_r("\n");
}

function string_to_words($str, $sep = " \t:,.;+-=!")
{
    return split_string_by_separators($str, $sep);
}


function split_string_by_separators($str, $separate_symbols = "")
{
    $cleaned_words = array();
    $pattern = "";
    $pattern_len = strlen($separate_symbols);
    if (!$pattern_len)
        return false;

    for ($i = 0; $i < $pattern_len; $i++) {
        $sym = $separate_symbols[$i];
        $pattern .= "\\" . $sym;
    }
    $words = preg_split(sprintf("/[%s]/", $pattern), $str);

    if (!$words)
        return false;

    foreach ($words as $word) {
        $cleaned_word = trim($word);
        if ($cleaned_word == '')
            continue;

        $cleaned_words[] = $cleaned_word;
    }

    return $cleaned_words;
}


function string_to_rows($str)
{
    return split_string_by_separators($str, "\n");
}



function strings_to_args($str)
{
    $args = array();
    $words = string_to_words($str);
    foreach ($words as $word)
        $args[] = strtolower($word);

    return $args;
}

function array_to_string($array, $delimiter = ',') // Записать данные массива в строчку через запятую
{
    $str = '';
    $seporator = '';
    if($array)
        foreach($array as $word)
        {
            $str .= $seporator . addslashes($word);
            $seporator = $delimiter;
        }
    return $str;
}

function string_to_array($array, $delimiter = ',') // Распарсить строку в массива
{
    $result = [];
    $arr = explode($delimiter, $array);
    if (!$arr || !count($arr))
        return [];

    foreach($arr as $item)
        if (trim($item) !== NULL)
            $result[] = trim($item);

    return $result;
}

function parse_json_config($conf_file_name)
{
    $cfg_json = file_get_contents($conf_file_name);
    if (!$cfg_json) {
        msg_log(LOG_ERR, sprintf("Can't open config file %s\n",
                                                 $conf_file_name));
        return null;
    }

    $ret = json_decode($cfg_json);
    if (!$ret) {
        msg_log(LOG_ERR, sprintf("Can't parse config file %s\n",
                                                 $conf_file_name));
        return null;
    }

    return (array)$ret;
}

