<?php

namespace MyOperator\Metrics;

class Metrics {

    protected static $instance;
    protected static $statsd;
    protected static $connection;
    protected static $appname;
    protected static $hostname;

    protected $tags = [];
    private $debug;

    const PREFIX = 'appmetrics';

    private $metric_types = [
        'http' => 'http_requests',
        'database' => 'database',
        'network' => 'network_requests',
        'exception' => 'exceptions'
    ];

    private $metric_attrs = ['count', 'sum', 'time'];

    private function __construct($appname, $hostname)
    {
        self::$appname = $appname;
        self::$hostname = $hostname;

        $this->init();
    }

    protected function init()
    {
        // Build metrics
        $this->metrics = $this->init_metrics($this->metric_types);

        // Init tag
        $this->setTag('host', $this->getHostname());
    }

    /**
     * Set Debug level of metrics
     * 
     * This will output the metrics being sent in text format
     *
     * @param boolean $debug
     * @return self
     */
    public function setDebug(bool $debug)
    {
        $this->debug = $debug;
        return $this;
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
     * @param \Domnikl\Statsd\Client $connection
     * @param string|null $namespace
     * @return self
     */
    public function setClient(\Domnikl\Statsd\Client $client, $appname=null)
    {
        if(!$appname) $appname = self::$appname;
        if(!self::$statsd) self::$statsd = [];
        self::$statsd[$appname] = $client;
        return $this;
    }

    /**
     * Get Statsd client being used
     *
     * @return \Domnikl\Statsd\Client
     */
    public function getClient($appname=null)
    {
        if(!$appname) $appname = self::$appname;
        if(!self::$statsd || !isset(self::$statsd[$appname])) {
            self::$statsd[$appname] = new \Domnikl\Statsd\Client(
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
    public function setTag($key, $val=null)
    {
        if(is_array($key)) {
            $this->tags = array_merge($this->tags, $key);
        }
        else $this->tags[$key] = $val;
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
        return isset($this->tags[$key]) ? $this->tags[$key] : null;
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
        return self::PREFIX . $sep . self::$appname;
    }

    private function init_metrics($metrics_namespaces=[]) {
        $metrics = [];

        foreach($metrics_namespaces as $k => $namespace) {
            foreach($this->metric_attrs as $attr) {
                $metrics[$k][$attr] = $namespace. '.' . $attr;
            }
            
        }

        return $metrics;
    }

    public function count($name, $count=1, $tags=[]) {
        $metric_name = $this->get_metric_name($name, 'count');
        $metric_value = $count; //Incr by $count
        $this->getClient()->count($metric_name, 1, 1.0, array_merge($this->tags, $tags));
    }

    public function start_timer($name) {
        $metric_name = $this->get_metric_name($name, 'time');
        $this->getClient()->startTiming($metric_name);
    }

    public function end_timer($name, $tags=[]) {
        $metric_name = $this->get_metric_name($name, 'time');
        $this->getClient()->endTiming($metric_name, 1.0, array_merge($this->tags, $tags));
    }

    protected function get_metric_name($name, $attr) {
        $attr = ($attr && in_array($attr, $this->metric_attrs)) ? $attr : 'generic';
        if(!isset($this->metrics[$name])) return $name . '.' . $attr;
        return $this->metrics[$name][$attr];
    }
}