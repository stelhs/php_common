<?php

class Database {
    private $link;

    function connect($conf = [])
    {
        $this->link = mysqli_connect($conf['host'],
                                     $conf['user'],
                                     $conf['pass'],
                                     $conf['database'],
                                     $conf['port']);
        if(!$this->link) {
            msg_log(LOG_ERR, mysqli_connect_error());
            return -ESQL;
        }

    	mysqli_query($this->link, 'set character set utf8');
    	mysqli_query($this->link, 'set names utf8');
    	mysqli_query($this->link, "SET AUTOCOMMIT=1");
        return 0;
    }

    function query($query)
    {
        $data = array();
        $row = array();
        $result = mysqli_query($this->link, $query);

        if($result === TRUE)
            return 0;

        if($result === FALSE) {
            printf("MySQL query error: %s\n", mysqli_error($this->link));
            return -ESQL;
        }

        $row = mysqli_fetch_assoc($result);
        if (!is_array($row))
            return null;

        return $row;
    }

    function query_list($query)
    {
        $data = array();
        $row = array();

        $result = mysqli_query($this->link, $query);
        if($result === TRUE)
            return 0;

        if($result === FALSE)
            return -ESQL;

        $id = 0;
        while($row = mysqli_fetch_assoc($result)) {
            $id++;
            if (isset($row['id']))
                $id = $row['id'];

            $data[$id] = $row;
        }

        return $data;
    }


    function insert($table_name, $array)
    {
        $query = "INSERT INTO " . $table_name . " SET ";
        $separator = '';
        foreach ($array as $field => $value) {
            if($field == 'id')
                continue;
            $query .= $separator . '`' .  $field . '` = "' . $value . '"';
            $separator = ',';
        }
        $result = mysqli_query($this->link, $query);
        if($result === FALSE)
            return -ESQL;

        return mysqli_insert_id($this->link);
    }


    function update($table, $id, $array)
    {
        $separator = '';
        $query = "UPDATE " . $table . " SET ";
        foreach($array as $field     => $value) {
            $query .= $separator . '`' .  $field . '` = "' . $value . '"';
            $separator = ',';
        }
        $query .= " WHERE id = " . $id;

        $update = mysqli_query($this->link, $query);
        if (!$update)
           return -ESQL;

        return 0;

    }

    function commit()
    {
    	mysqli_commit($this->link);
    }


    function close()
    {
        if(!mysqli_close($this->link))
           return -EBASE;

        return 0;
    }

}

function db()
{
    static $db = NULL;

    if ($db)
        return $db;

    $db = new Database();
    $rc = $db->connect(conf_db());
    if ($rc)
        throw new Exception("can't connect to database");
    return $db;
}


?>
