<?php



/**
 * Logging function
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



/**
 * Run command in console and return output
 * @param $cmd - command
 * @param bool $fork - true - run in new thread (not receive results),
 * 			           false - run in current thread
 * @param $stdin_data - optional data direct to stdin
 * @param $print_stdout - optional flag indicates that all output from the process should be printed
 * @return array with keys: rc and log
 */
function run_cmd($cmd, $fork = false, $stdin_data = '', $print_stdout = false, $pid_file = "")
{
    if ($fork == true)
    {
        $pid = pcntl_fork();
        if ($pid == -1)
            throw new Exception("can't fork() in run_cmd()");

        if ($pid) // Current process return
            return $pid;

        register_shutdown_function(function(){
                posix_kill(getmypid(), SIGKILL);
        });

        // new process continue
        fclose(STDERR);
        fclose(STDIN);
        fclose(STDOUT);

        if ($pid_file)
            file_put_contents($pid_file, posix_getpid());
    }

    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
    );

    $fd = proc_open('/bin/bash', $descriptorspec, $pipes);
    if ($fd == false)
        throw new Exception("proc_open() error in run_cmd()");

    $fd_write = $pipes[0];
    $fd_read = $pipes[1];

    fwrite($fd_write, $cmd . " 2>&1;\n");

    if ($stdin_data)
        fwrite($fd_write, $stdin_data);

    fclose($fd_write);

    $log = '';
    while($str = fgets($fd_read))
    {
        $log .= $str;
        if ($print_stdout)
            echo $str;
    }

    fclose($fd_read);
    $rc = proc_close($fd);
    if ($rc == -1)
        throw new Exception("proc_close() error in run_cmd()");

    if ($fork == true) {
        if ($pid_file)
            unlink($pid_file);
        exit;
    }

    msg_log(LOG_NOTICE, sprintf("run cmd: %s, returned: %s", $cmd, $log));
    return array('log' => trim($log), 'rc' => $rc);
}

function run_daemon($cmd, $pid_file)
{
    return run_cmd($cmd, true, '', false, $pid_file);
}

function stop_daemon($pid_file)
{
    if (!file_exists($pid_file))
        return;

    kill_all(file_get_contents($pid_file));
}

/**
 * get list of children PID
 * @param $parent_pid
 * @return array of children PID or false
 */
function get_child_pids($parent_pid)
{
    $ret = run_cmd("ps -ax --format '%P %p'");
    $rows = explode("\n", $ret['log']);
    if (!$rows)
        throw new Exception("incorrect output from command: ps -ax --format '%P %p'");

    $pid_list = array();

    foreach ($rows as $row)
    {
        preg_match('/([0-9]+)[ ]+([0-9]+)/s', $row, $matched);
        if (!$matched)
            continue;

        $ppid = $matched[1];
        $pid = $matched[2];
        $pid_list[$ppid][] = $pid;
    }

    if (!isset($pid_list[$parent_pid]))
        return false;

    return $pid_list[$parent_pid];
}


/**
 * Kill all proceses
 * @param $kill_pid
 */
function kill_all($kill_pid)
{
    $child_pids = get_child_pids($kill_pid);
    if ($child_pids)
        foreach ($child_pids as $child_pid)
            kill_all($child_pid);

    run_cmd('kill -9 ' . $kill_pid);
    msg_log(LOG_NOTICE, "killed PID: " . $kill_pid);
}


function get_pid_list_by_command($command)
{
    $pid_list = [];
    $rc = run_cmd("ps -aux");
    $content = $rc['log'];
    $lines = string_to_rows($content);
    foreach ($lines as $line) {
        $cols = split_string_by_separators($line, " \t");
        if ($cols[10] != $command)
            continue;
        $pid_list[] = $cols[1];
    }
    return $pid_list;
}

function get_list_subdirs($dir)
{
    $dirs = [];
    @$rows = scandir($dir);
    if (!is_array($rows))
        return false;
    foreach ($rows as $row) {
        if (!is_dir($dir . $row))
            continue;
        if ($row == '.' || $row == '..')
            continue;
        $dirs[] = $row;
    }
    return $dirs;
}

function get_list_files($dir)
{
    $dirs = [];
    @$rows = scandir($dir);
    if (!is_array($rows))
        return false;
    foreach ($rows as $row) {
        if (is_dir($dir . $row))
            continue;
        $dirs[] = $row;
    }
    return $dirs;
}



?>