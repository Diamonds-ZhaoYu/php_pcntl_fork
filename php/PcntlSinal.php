<?php
// +----------------------------------------------------------------------
// |
// |  进程调度例子
// +----------------------------------------------------------------------
// | Copyright (c) https://admuch.txbapp.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhaoyu <9641354@qq.cn>
// +----------------------------------------------------------------------
// | Date: 2019/10/11 3:26 下午
// +----------------------------------------------------------------------

class PcntlSinal
{
    public $parentPID        = NULL;
    public $childPID         = [];

    /***
     * @var array 程序结束调用函数
     */
    private $callUserFunc    = NULL;

    /**
     * @var int 开启系统进程数
     */
    private $childMax        = 3;

    /**
     * @var null
     */
    protected static $instatnce  = NULL;


    /**
     * 返回类对象
     * @return PcntlSinal|null
     */
    public static function getInstance()
    {
        if (self::$instatnce == NULL) {
            self::$instatnce = new self();
            return self::$instatnce;
        }
        return self::$instatnce;
    }

    /**
     * 设置调用函数
     * @param $callFunc
     * @return $this
     */
    public function setCallUserFunc($callFunc)
    {
        $this->callUserFunc = $callFunc;
        return $this;
    }

    /**
     * 设置进程数量
     * @param $num
     * @return $this
     */
    public function setChildMax($num)
    {
        $this->childMax = $num;
        return $this;
    }

    /**
     * 初始化信息
     */
    private function init()
    {
        $this->createProccess();
        //初始化脚本结束事件
        register_shutdown_function(array(&$this, "shutdown"));
        $this->initSignal();
    }

    /**
     * 创建进程
     */
    public function createProccess()
    {
        //获得主应用ID
        $this->parentPID = getmypid();
        echo PHP_EOL . "parentID: " .  $this->parentPID . PHP_EOL;
        for ($i = 0; $i < $this->childMax; $i++) {
        //    pcntl_sigprocmask(SIG_BLOCK, array(SIGCHLD));
            $pid = pcntl_fork();

            switch ($pid) {
                case '-1'://创建子进程失败
                    echo PHP_EOL . "couldn't fork" . PHP_EOL;
                    break;
                case  '0'://创建子进程成功 并且代表此进程为子进程
                    echo PHP_EOL . "第" . $i . "子个进程 -> " . posix_getpid() . PHP_EOL;

                    //pcntl_wait($status);
//                    $re = pcntl_waitpid($pid, $status, WNOHANG);
//                    if ($re != $pid) {
//                        continue;
//                    }
                    // sleep(1);
                   // exit(0);//子进程要exit否则会进行递归多进程，父进程不要exit否则终止多进程
                    break;
                default://代表此进程为主进程
                    $this->childPID[$i] = $pid;
                  //  pcntl_sigprocmask(SIG_UNBLOCK, array(SIGCHLD));
                    echo PHP_EOL . "第 {$i}进程 父进程ID: ", posix_getppid(), " 进程ID : ", posix_getpid(), "  ". PHP_EOL;

                    break;
            }
        }
    }

    public function killProcessAndChilds($pid,$signal = 9) {
        exec("ps -ef| awk '\$3 == '$pid' { print  \$2 }'", $output, $ret);
        if($ret) return 'you need ps, grep, and awk';

        while(list(,$t) = each($output)) {
            if ( $t != $pid ) {
                $this->killProcessAndChilds($t,$signal);
            }
        }
        //echo "killing ".$pid."\n";
        posix_kill($pid, 9);
    }

    /**
     * 初始化信号量
     * @param string $func 信号量通知方法
     */
    public function initSignal($func = 'sigHandler')
    {
        //安装信号处理器
        pcntl_signal(SIGTERM, array(&$this, $func));
        pcntl_signal(SIGHUP,  array(&$this, $func));
        pcntl_signal(SIGINT,  array(&$this, $func));
    }

    /**
     * 处理信号量
     */
    public function dispatch()
    {
        $this->init();
        //使用ticks需要PHP 4.3.0以上版本 接收信号
        //或者用 pcntl_signal_dispatch 接收信号  比上面效率高
        while (true) {
            //echo posix_getpid();
            sleep(10);               //也是一次注册操作如果有信号会立刻返回
            // do something

            //信号处理
            pcntl_signal_dispatch(); // 接收到信号时，调用注册的signalHandler()比declare效率更高 php5.3版本以上支持
        }
    }

    /**
     * 处理信号量
     * @param integer $signo 信号量
     */
    public function sigHandler($signo)
    {
        switch ($signo) {
            case SIGTERM:
                // 处理kill
                $this->killProcessAndChilds(posix_getpid());
                echo PHP_EOL . 'kill pid: ' . posix_getpid() . PHP_EOL;
                break;
            case SIGHUP:
                //处理SIGHUP信号
                break;
            case SIGINT:
                //处理ctrl+c
                echo PHP_EOL.'ctrl+c';
                $this->killProcessAndChilds($this->parentPID);

                break;
            default:
                echo PHP_EOL.'other';
                // 处理所有其他信号
                $this->killProcessAndChilds($this->parentPID);
                break;
        }
    }

    /**
     * php生命周期结束
     * 下面执行exit 也会调用shutdown函数
     */
    public function shutdown()
    {
        echo PHP_EOL.'shutdown'.PHP_EOL;

        if (!empty($this->callUserFunc)) {
            call_user_func($this->callUserFunc);
        }
    }

    /**
     * 控制调用函数
     */
    public function callDemo()
    {
        echo PHP_EOL.'callDemo'.PHP_EOL;
    }
}

PcntlSinal::getInstance()
    ->setCallUserFunc(array("PcntlSinal","callDemo"))
    ->dispatch();







