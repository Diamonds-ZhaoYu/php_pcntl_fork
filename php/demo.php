<?php
    //declare(ticks=1);
    $bWaitFlag = TRUE;    // 是否等待进程结束
    $intNum    = 3;       // 进程总数
    $pids      = array(); // 进程PID数组

    while (1) {  ///守护进程形式
        for ($i = 0; $i < $intNum; $i++) {
            if ($bWaitFlag) { //是否等待 进程执行完在进行下一次循环

                if (isset($pids[$i])) {
                    //pcntl_waitpid($pids[$i], $status,WUNTRACED);//查看子进程是否执行完如果没执行完 等待
                    //或者检查子进程状态 如果运行中直接跳过
                    $re = pcntl_waitpid($pids[$i], $status, WNOHANG);
                    if ($re != $pids[$i]) {
                        continue;
                    }
                }
            }
            $pids[$i] = pcntl_fork();// 产生子进程，而且从当前行之下开试运行代码，而且不继承父进程的数据信息
            switch ($pids[$i]) {
                case '-1'://创建子进程失败
                    echo "couldn't fork" . "\n";
                    break;
                case  '0'://创建子进程成功 并且代表此进程为子进程
                    echo "\n" . "第" . $i . "子个进程 -> " . time() . "\n";
                    sleep(10);
                    exit(0);//子进程要exit否则会进行递归多进程，父进程不要exit否则终止多进程
                    break;
                default://代表此进程为主进程
                    echo $pids[$i] . "parent" . "$i -> " . time() . "\n";
                    break;
            }

        }
    }
