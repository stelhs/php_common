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

