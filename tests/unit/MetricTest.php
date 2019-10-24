<?php declare(strict_types=1);

namespace MyOperator\MetricTest;

use MyOperator\Metrics\Metrics;
use PHPUnit\Framework\TestCase;
use Domnikl\Statsd\Connection\InMemory;

class MetricTest extends TestCase
{
    /**
     * @var Metrics
     */
    private $metrics;
    /**
     * @var ConnectionMock
     */
    private $connection;

    protected function setUp(): void
    {
        $this->connection = new InMemory();
        $this->metrics = Metrics::getInstance('mytest');
    }
    
    public function test_connect_with_valid_connection_returns_connection()
    {
        $metrics = $this->metrics->connect($this->connection);
        $client = $metrics->getClient();
        $this->assertInstanceOf(\Domnikl\Statsd\Client::class, $client);
    }

    public function test_connect_with_invalid_connection_throws_error()
    {
        try {
            $metrics = $this->metrics->connect(null);
        } catch (\Exception $e) {
            $this->assertEquals($e->getMessage(), 'Host and Port are required for metrics');
        }
    }

    public function test_init_set_metrics_and_tags()
    {
        $host_tag = $this->metrics->getTag('host');
        $this->assertNotNull($host_tag);
        // Assert Metrics
    }
}