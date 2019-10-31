# Metrics


This library serves as a base for application metrics for myoperator PHP based applications.

## Dependencies

- codeasashu/statsd-php (Composer package)
- php >= 7.2
- UDP statsd connection

## Quick Start

Install this library as composer dependecy in any project you want to add metrics to.

```sh
composer require myoperator/metrics
```
or simple add this to your `composer.json`

```json
{
    "require": {
        "myoperator/metrics": "^1"
    }
}
```

Then initialise `Metrics` instance in your bootstrap script or any init'able script

```php
use MyOperator\Metrics\Metrics;

Metrics::setApplication('your-app-name'); // Your metrics base name
Metrics::setConnection('localhost', 8125); // UDP connection host and port
```

Then you can get `Metrics` instance anywhere in your application and can send metrics.
For instance, to send `timing` metrics for a time taking function:

```php
Metrics::getInstance()->startTiming('fn.time');
$this->consumeTime();
Metrics::getInstance()->endTiming('fn.time'); //Same name that you started logging time with
```

**NOTE** This repo is extension of source code at https://github.com/codeasashu/statsd-php

To see all documentation, please go through https://github.com/codeasashu/statsd-php 
documentation and see the availble methods and logging mechanism

## Metric Types

Following metric types are supported, and their method names are same as well

### Counter
Counter sends a arbitary count of anything, *which can only increase by time*

for example:
```php
$user->login(); //some method to login user
metrics::getinstance()->count('user.login', 1); //increase user login by 1
```

counters can be used to log:

- Number of requests served
- Tasks completed (user login, invoices generated)
- Errors or exceptions

### Gauge

Gauges are used to metric any arbitary random number which can increase or decrease by time


for example:
```php
$items = $queue->getItems(); //some method to get items in queue
metrics::getinstance()->count('queue.item.count', count($items)); //Send number of items in queue
```

Gauges can be used to log:

- Number of items in queues
- Memory size of cache
- Number of active process/threads/containers

### Timers

Timers forms a very cruitial component for any metrics, add it forms the basis of SLOs. Also, timing
can be used to calculate averages, sum etc. 

Timing can be recorded in following ways:

#### Start and Stop based timers
Here, we start the timer and stop when the task is done. This is best suited for cases where we are
doing the task in same script.

```php
Metric::getInstance()->startTiming('task.time'); //String for reference
$task->takeTime(); //Time taking task
Metric::getInstance()->endTiming('task.time'); //End the time
```

#### Self time calculation
If you want to time your metric yourself, you can do so by using `timing` method:

```php
$starttime = microtime(true);
$task->takeTime(); //Some time taking task
$timeconsmed = (microtime(true) - $starttime) * 1000;
Metric::getInstance()->timing('task.time', $timeconsmed);
```

#### Callback based approach

```php
Metric::getInstance()->time("task.time", function() {
    $task->takeTime(); //Some time taking task
});
```

