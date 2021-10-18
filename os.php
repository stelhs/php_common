<?php

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
        return -EINVAL;

    $pid = file_get_contents($pid_file);
    kill_all($pid);
    unlink($pid_file);
    return 0;
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


function get_free_space($dev_path = "")
{
    require_once '/usr/local/lib/php/common.php';

    $ret = run_cmd("df " . $dev_path);
    if ($ret['rc'])
        return;

    $data = [];
    $rows = string_to_rows($ret['log']);
    foreach ($rows as $row) {
        $words = split_string_by_separators($row, ' ');
        if ($words[0] == 'Filesystem')
            continue;

        $dev_data = ['dev_name' => $words[0],
                     'size'     => $words[1] * 1024,
                     'used'     => $words[2] * 1024,
                     'avail'    => $words[3] * 1024,
                     'use'      => str_replace('%', '', $words[4]),
                    ];
        if ($dev_path)
            return $dev_data;
        $data[] = $dev_data;
    }
    return $data;
}



function get_mdstat()
{
    $stat = file("/proc/mdstat");

    if (!isset($stat[2]))
        return ['state' => 'no_exist'];

    if (isset($stat[3])) {
        preg_match('/resync[ ]+=[ ]+([0-9\.]+)\%/', $stat[3], $matches);
        if (isset($matches[1]))
            return ['state' => 'resync',
                    'progress' => $matches[1]];

        preg_match('/recovery[ ]+=[ ]+([0-9\.]+)\%/', $stat[3], $matches);
        if (isset($matches[1]))
            return ['state' => 'recovery',
                    'progress' => $matches[1]];
    }

    preg_match('/\[[U_]+\]/', $stat[2], $matches);
    $mode = $matches[0];

    if ($mode == '[UU]')
        return ['state' => 'normal'];

    if ($mode == '[_U]' || $mode == '[U_]')
        return ['state' => 'damage'];

    return ['state' => 'parse_err'];
}




?>
