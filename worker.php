<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Worker;

// stdout занят протоколом обмена с RoadRunner, любой вывод — только в stderr
ini_set('display_errors', 'stderr');

require __DIR__ . '/vendor/autoload.php';

// Бутстрап: выполняется один раз на процесс, живёт между запросами
$psr_factory = new Psr17Factory();
$worker      = Worker::create();
$psr7_worker = new PSR7Worker($worker, $psr_factory, $psr_factory, $psr_factory);

// waitRequest() вернёт null, когда RoadRunner попросит воркер завершиться
while ($request = $psr7_worker->waitRequest()) {
    try {
        $body = json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR);

        $response = $psr_factory->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($psr_factory->createStream($body));

        $psr7_worker->respond($response);
    } catch (\Throwable $e) {
        // Сообщить RoadRunner об ошибке, иначе он будет ждать ответа до exec_ttl
        $worker->error((string) $e);
    }
}
