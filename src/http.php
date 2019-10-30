<?php

namespace MyOperator\Metrics;

class Http extends Metrics {

    public function __call($name, $args) 
    {
        $path = isset($args[0]) && is_string($args[0]) ? $args[0] : null;
        switch($name) {
            case 'timing': $this->tag(['path' => $path]); break;
            case 'count': $this->tag(['path' => $path]); break;
            case 'gauge': $this->tag(['path' => $path]); break;
            case 'set': $this->tag(['path' => $path]); break;
        }
        $this->getClient()->setPrefix('http');
        return call_user_func_array([$this->getClient(), $name], $args);
    }
}