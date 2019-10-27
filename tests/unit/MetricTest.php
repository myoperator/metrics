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
        Metrics::setApplication('mytest');
        Metrics::setConnection($this->connection);
        $this->metrics = Metrics::getInstance('mytest');
        //$this->metrics->nsetConnection($this->connection);
    }

    public function test_multiple_app_instance()
    {
        $this->assertInstanceOf(Metrics::class, $this->metrics);
        $metrics = Metrics::getInstance('something');
        $this->assertInstanceOf(Metrics::class, $metrics);
        $this->assertNotSame($metrics, $this->metrics);
        $this->metrics->count('abc');
        $statsd_messages = $this->metrics->getConnection()->getMessages();
        $this->assertStringContainsString('something', $statsd_messages[0]);
        //$this->assertStringContainsString('mytest', $statsd_messages[0]);
        $this->assertStringNotContainsString('mytest', $statsd_messages[0]);
        $this->metrics->getConnection()->clear();
    }

    public function test_instance_returns_current_class()
    {
        $this->assertInstanceOf(Metrics::class, $this->metrics);
    }

    public function test_connect_with_valid_connection_returns_connection()
    {
        $client = $this->metrics->getClient();
        $this->assertInstanceOf(\Domnikl\Statsd\Client::class, $client);
    }

    public function test_set_client_replace_old_client()
    {
        $oldclient = $this->metrics->getClient();
        $newclient = new \Domnikl\Statsd\Client($this->connection, 'newtest');
        $this->metrics->setClient($newclient);
        $this->assertEquals($this->metrics->getClient(), $newclient);
        $this->metrics->setClient($oldclient);
        $this->assertEquals($this->metrics->getClient(), $oldclient);
    }
    
    public function test_has_valid_namespace()
    {
        $client = $this->metrics->getClient();
        $this->assertEquals(Metrics::PREFIX . '.mytest', $client->getNamespace());
    }

    public function test_connect_with_invalid_connection_throws_error()
    {
        try {
            Metrics::setConnection(null);
        } catch (\Exception $e) {
            $this->assertEquals($e->getMessage(), 'Host and Port are required for metrics');
        }
    }

    public function test_init_set_tags()
    {
        $host_tag = $this->metrics->getTag('host');
        $this->assertNotNull($host_tag);
        $metrics = Metrics::getInstance('mytest2');
        $uniquetag = microtime();
        $metrics->setTag('uniquetag', $uniquetag);
        $metrics->count('abc');
        $statsd_messages = $metrics->getConnection()->getMessages();
        $this->assertStringContainsString($uniquetag, $statsd_messages[0]);
    }
}