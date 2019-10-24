<?php

namespace MyOperator\Metrics;

class Metrics {

    private static $instance;
    private $tags = [];

    protected $connection;
    protected $statsd;

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
        $this->appname = $appname;
        $this->hostname = $hostname;

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

    /**
     * Set Statsd connection
     *
     * @param string|\Domnikl\Statsd\Connection $host
     * @param int $port. Defaults to UDP 8125 port
     * @return self
     * @throws \Exception if host or port is not provided
     */
    public function connect($host, $port=8125)
    {
        if(!$host || !$port) {
            throw new \Exception('Host and Port are required for metrics');
        }

        if($host instanceof \Domnikl\Statsd\Connection) {
            $connection = $host;
        }
        else $connection = $this->getUdpSocketInstance($host, $port);

        return $this->setClient($connection);
    }

    private function getUdpSocketInstance($host, $port)
    {
        return new \Domnikl\Statsd\Connection\UdpSocket($host, $port);
    }

    /**
     * Set a Statsd Client
     *
     * @param \Domnikl\Statsd\Connection $connection
     * @param string|null $namespace
     * @return \Domnikl\Statsd\Client $client
     * @throws \Exception if invalid connection is provided
     */
    public function setClient($connection, $namespace=null)
    {
        if(!$connection || !($connection instanceof \Domnikl\Statsd\Connection)) {
            throw new \Exception("Invalid Statsd Connection: {$connection}");
        }

        $this->statsd = new \Domnikl\Statsd\Client(
            $connection,
            $this->getBaseNamespace()
        );

        return $this;
    }

    /**
     * Get Statsd client being used
     *
     * @return \Domnikl\Statsd\Client
     */
    public function getClient()
    {
        return $this->statsd;
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
     * Get Metrics instance
     *
     * @param string $appname Application name 
     * @param string|null $hostname Hostname or ip address of server
     * @return self
     */
    public static function getInstance($appname, $hostname=null)
    {
        if($hostname === null) {
            $hostname = gethostname();
        }
        if(!isset(self::$instance)) {
            self::$instance = new static($appname, $hostname);
        }
        return self::$instance;
    }

    protected function getApplication() {
        return $this->appname;
    }

    protected function getHostname() {
        return $this->hostname;
    }

    protected function getBaseNamespace($sep='.') {
        return self::PREFIX . $sep
//                . $this->getHostname() . $sep
                . $this->getApplication();
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

    private function debug_metrics($key, $value) {
        if($this->debug === true) {
            $connection = $this->getConnection();
            echo "\nSending {$key} : {$value}";
        }
    }

    protected function count($name, $tags=[]) {
        $metric_name = $this->get_metric_name($name, 'count');
        $metric_value = 1; //Incr by 1
        $this->debug_metrics($metric_name, $metric_value);
        $this->getClient()->count($metric_name, 1, 1.0, array_merge($this->tags, $tags));
    }

    protected function start_timer($name) {
        $metric_name = $this->get_metric_name($name, 'time');
        $this->getClient()->startTiming($metric_name);
    }

    protected function end_timer($name, $tags=[]) {
        $metric_name = $this->get_metric_name($name, 'time');
        $this->debug_metrics($metric_name, $metric_value);
        $this->getClient()->endTiming($metric_name, 1.0, array_merge($this->tags, $tags));
    }

    protected function get_metric_name($name, $attr) {
        $attr = ($attr && in_array($attr, $this->metric_attrs)) ? $attr : 'generic';
        if(!isset($this->metrics[$name])) return $name . '.' . $attr;
        return $this->metrics[$name][$attr];
    }
}