<?php

namespace MyOperator\Metrics;

use \Domnikl\Statsd\Client;

class MetricClient extends Client {

    /**
     *
     * @var array tags
     */
    private $tags = [];

    private $prefix = null;

    const PREFIXES = [
        'ms' => 'timer',
        'c' => 'counter',
        //@TODO statsd exporter doesn't have support for sets yet 
        // (see: https://github.com/prometheus/statsd_exporter/pull/184)
        's' => 'counter',
        'g' => 'gauge',
    ];

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
            $this->tags = array_merge($this->tags, array_filter($key));
        }
        else if($val) $this->tags[$key] = $val;
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
     * Get all tag
     *
     * @return array [$tag[n.]..]
     */
    public function getTags()
    {
        return $this->tags;
    }

    public function setPrefix(string $prefix=null)
    {
        $this->prefix = $prefix;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    private function buildPrefix($type, $key)
    {
        return implode('.', array_filter([
            isset(self::PREFIXES[$type]) ? self::PREFIXES[$type] : 'generic',
            $this->getPrefix(),
            $key
        ]));
    }

    private function buildTags(array $tags)
    {
        return array_merge($tags, $this->tags);
    }

    /**
     * actually sends a message to to the daemon and returns the sent message
     *
     * @param string $key
     * @param int|float|string $value
     * @param string $type
     * @param float $sampleRate
     * @param array $tags
     */
    public function buildSampledData(string $key, $value, string $type, float $sampleRate, array $tags = []): string
    {
        // Prefix the key
        $prefix = $this->buildPrefix($type, $key);

        // Set the tag
        $tags = $this->buildTags($tags);
        return parent::buildSampledData($prefix, $value, $type, $sampleRate, $tags);
    }
}
