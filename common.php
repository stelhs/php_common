<?php

define("EINVAL", 1); /* Inctorrect input parameters */
define("EBASE", 2); /* Database error */
define("ESQL", 3); /* SQL error */
define("ENOTUNIQUE", 4); /* Element not enique */
define("EBUSY", 5); /* Resource or device is busy */
define("ENODEV", 22);  /* No device or resourse found  */
define("ECONNFAIL", 42); /* Connection fault */
define("EPARSE", 137); /* Parsing error */

function perror()
{
    $argv = func_get_args();
    $format = array_shift($argv);
    $msg = vsprintf($format, $argv);
    fwrite(STDOUT, $msg);
}

function pnotice()
{
    $argv = func_get_args();
    $format = array_shift($argv);
    $msg = vsprintf($format, $argv);
    fwrite(STDOUT, $msg);
}

function dump($msg)
{
    print_r($msg);
    print_r("\n");
}

/**
 * Split string on words
 * @param $str - string
 * @return array of words
 */
function split_string($str)
{
    $cleaned_words = array();
    $words = preg_split("/[ \t\:\,\.\;\-\=\!]/", $str);

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
    $cleaned_rows = [];
    $rows = preg_split("/[\n]/", $str);

    if (!$rows)
        return false;

    foreach ($rows as $row) {
        $cleaned_row = trim($row);
        if ($cleaned_row == '')
            continue;

        $cleaned_rows[] = $cleaned_row;
    }

    return $cleaned_rows;
}



function strings_to_args($str)
{
    $args = array();
    $words = split_string($str);
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
    foreach($arr as $item)
        if (trim($item))
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

