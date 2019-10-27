<?php

namespace MyOperator\Metrics;

class Http extends Metrics {

    const METRIC_TYPE = 'http';

    public function start($path='/')
    {
        $this->setTag('path', $path);
        $this->count(self::METRIC_TYPE);
        $this->start_timer(self::METRIC_TYPE);
        return $this;
    }

    public function end($code=200, $data=null)
    {
        $this->end_timer(self::METRIC_TYPE);
    }
}