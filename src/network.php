<?php

namespace MyOperator\Metrics;

class Network extends Metrics {

    const METRIC_TYPE = 'network';

    public function start($name='curl', $path='/')
    {
        $this->setTag(['path' => $path, 'name' => $name]);
        $this->count(self::METRIC_TYPE);
        $this->start_timer(self::METRIC_TYPE);
        return $this;
    }

    public function end($code=200, $data=null)
    {
        $this->end_timer(self::METRIC_TYPE);
        return $this->test;
    }
}