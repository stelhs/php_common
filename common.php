<?php 
    
define("EINVAL", 1); /* Inctorrect input parameters */
define("EBASE", 2); /* Database error */
define("ESQL", 3); /* SQL error */
define("ENOTUNIQUE", 4); /* Element not enique */
define("EBUSY", 5); /* Resource or device is busy */
define("ENODEV", 22);  /* No device or resourse found  */
define("ECONNFAIL", 42); /* Connection fault */
define("EPARSE", 137); /* Parsing error */


function dump($msg)
{
    print_r($msg);
}

/**
 * Split string on words
 * @param $str - string
 * @return array of words
 */
function split_string($str)
{
    $cleaned_words = array();
    $words = split("[ \t,]", $str);
    if (!$words)
        return false;

    foreach ($words as $word) {
        $cleaned_word = trim($word);
        if ($cleaned_word == '')
            continue;
        
        $cleaned_words[] = trim($word);
    }

    return $cleaned_words;
}

function array_to_string($array) // Записать данные массива в строчку через запятую
{
    $str = '';
    $seporator = '';
    if($array)
        foreach($array as $word)
        {
            $str .= $seporator . addslashes($word);
            $seporator = ',';
        }
    return $str;
}

function string_to_array($array) // Распарсить строку в массива
{
    $arr = explode(',', $array);
    foreach($arr as $item)
        $result[$item] = $item;
        
    return $result;
}

