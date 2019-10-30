<?php

namespace MyOperator\Metrics;

class Network extends Metrics {

    public function __call($name, $args) 
    {
        $this->getClient()->setPrefix('network');
        return call_user_func_array([$this->getClient(), $name], $args);
    }
}