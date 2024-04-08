<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Redis;
use Predis\Command\Argument\Geospatial\ByRadius;
use Predis\Command\Argument\Geospatial\FromLonLat;
use Tests\TestCase;

class RedisTest extends TestCase
{
    public function testPing()
    {
        $response = Redis::command('ping');
        $this->assertEquals('PONG', $response);

        $response = Redis::ping();
        $this->assertEquals('PONG', $response);
    }

    public function testString()
    {
        Redis::setex('name', 2, 'Otong');
        $response = Redis::get('name');
        $this->assertEquals('Otong', $response);

        sleep(5);
        $response = Redis::get('name');
        $this->assertNull($response);
    }

    public function testList()
    {
        Redis::del("names");

        Redis::rpush("names", "Otong");
        Redis::rpush("names", "Ucup");
        Redis::rpush("names", "Asep");

        $response = Redis::lrange("names", 0, -1);
        $this->assertEquals(["Otong", "Ucup", "Asep"], $response);

        $this->assertEquals("Otong", Redis::lpop("names"));
        $this->assertEquals("Ucup", Redis::lpop("names"));
        $this->assertEquals("Asep", Redis::lpop("names"));
    }

    public function testSet()
    {
        Redis::del("names");

        Redis::sadd("names", "Otong");
        Redis::sadd("names", "Otong");
        Redis::sadd("names", "Ucup");
        Redis::sadd("names", "Ucup");
        Redis::sadd("names", "Asep");
        Redis::sadd("names", "Asep");

        $response = Redis::smembers("names");
        $this->assertEquals(["Otong", "Ucup", "Asep"], $response);
    }

    public function testSortedSet()
    {
        Redis::del("names");

        Redis::zadd("names", 100, "Otong");
        Redis::zadd("names", 75, "Ucup");
        Redis::zadd("names", 80, "Asep");

        $response = Redis::zrange("names", 0, -1);
        $this->assertEquals(["Ucup", "Asep", "Otong"], $response);
    }

    public function testHash()
    {
        Redis::del("user:1");

        Redis::hset("user:1", "name", "Otong");
        Redis::hset("user:1", "email", "otong@example");
        Redis::hset("user:1", "age", 25);

        $response = Redis::hgetall("user:1");
        $this->assertEquals([
            "name" => "Otong",
            "email" => "otong@example",
            "age" => "25"
        ], $response);
    }

    public function testGeoPoint()
    {
        Redis::del("sellers");

        Redis::geoadd("sellers", 131.24722, -0.85863, "Toko A");
        Redis::geoadd("sellers", 131.24719, -0.85926, "Toko B");

        $result = Redis::geodist("sellers", "Toko A", "Toko B", "km");
        $this->assertEquals(0.0700, $result);

        $result = Redis::geosearch("sellers", new FromLonLat(131.24723, -0.85890), new ByRadius(5, "km"));
        $this->assertEquals([], $result);
    }

    public function testHyperLogLog()
    {
        Redis::pfadd("visitors", "otong", "ucup", "asep");
        Redis::pfadd("visitors", "otong", "udin", "yanto");
        Redis::pfadd("visitors", "mante", "udin", "yanto");

        $result = Redis::pfcount("visitors");
        $this->assertEquals(6, $result);
    }
}
