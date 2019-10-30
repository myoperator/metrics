<?php

namespace MyOperator\Metrics;

ini_set('html_errors', 'off');

class Metrics {

    protected static $instance;
    protected static $statsd;
    protected static $connection;
    protected static $appname;
    protected static $hostname;

    const PREFIX = 'appmetric';

    private function __construct($appname, $hostname)
    {
        self::$appname = $appname;
        self::$hostname = $hostname;
    }

    public static function setConnection($host, $port=8125) {
        if(!$host || !$port) {
            throw new \Exception('Host and Port are required for metrics');
        }

        if($host instanceof \Domnikl\Statsd\Connection) {
            $connection = $host;
        }
        else $connection = self::getUdpSocketInstance($host, $port);
        self::$connection = $connection;
    }

    public static function getConnection() {
        return self::$connection;
    }

    private static function getUdpSocketInstance($host, $port)
    {
        return new \Domnikl\Statsd\Connection\UdpSocket($host, $port);
    }

    /**
     * Set a Statsd Client
     *
     * @param MetricClient $connection
     * @param string|null $namespace
     * @return self
     */
    public function setClient(MetricClient $client, $appname=null)
    {
        if(!$appname) $appname = self::$appname;
        if(!self::$statsd) self::$statsd = [];
        self::$statsd[$appname] = $client;
        return $this;
    }

    /**
     * Get Statsd client being used
     *
     * @return MetricClient
     */
    public function getClient($appname=null)
    {
        if(!$appname) $appname = self::$appname;
        if(!self::$statsd || !isset(self::$statsd[$appname])) {
            self::$statsd[$appname] = new MetricClient(
                self::$connection,
                $this->getBaseNamespace()
            );
        }
        return self::$statsd[$appname];
    }


    /**
     * Set a tag
     *
     * @param [string|array] $key
     * @param [string] $val
     * @return self
     */
    public function tag($key, $val=null)
    {
        $this->getClient()->setTag($key, $val);
        return $this;
    }

    /**
     * Get a tag
     *
     * @param string $key
     * @return string Tag
     */
    public function getTag($key)
    {
        return $this->getClient()->getTag($key);
    }

    /**
     * Get all tag
     *
     * @return array [$tag[n.]..]
     */
    public function getTags()
    {
        return $this->getClient()->getTags();
    }

    /**
     * Set Metrics application and hostname instance
     *
     * @param string $appname Application name 
     * @param string|null $hostname Hostname or ip address of server
     * @return self
     */
    public static function setApplication($appname, $hostname=null)
    {
        self::$hostname = $hostname ?: gethostname();
        self::$appname = $appname ?: uniqid(6);
    }

    private static function get_class_name($class=null) {
        $path = explode('\\', ($class ?: __CLASS__));
        return array_pop($path);
    }

    public static function getInstance($appname=null)
    {
        $class = self::get_class_name(get_called_class());
        $self = self::get_class_name(__CLASS__);
        if($appname === null) $appname = self::$appname;

        $hostname = self::$hostname;
        if( ($class === $self)  || 
            (($class !== $self) && 
                class_exists($classname = __NAMESPACE__ . '\\' . ucwords($class)))
        ) {
            if(!isset(self::$instance[$appname]) || (self::$instance[$appname]::$appname !== $appname)) {
                self::$instance[$appname] = new static($appname, $hostname);
            }
        }
        return self::$instance[$appname];
    }

    public static function getApplication() {
        return self::$appname;
    }

    protected function getHostname() {
        return self::$hostname;
    }

    protected function getBaseNamespace($sep='.') {
        return self::PREFIX . $sep . $this->getApplication();
    }

    public function __call($name, $args) {
        call_user_func_array([$this->getClient(), $name], $args);
    }
}