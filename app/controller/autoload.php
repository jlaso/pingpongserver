<?php

    $routingCacheManager = new \Router\RoutingCacheManager();

    $routingCacheManager->loadRoute(__DIR__ . '/frontend/FrontendController.php');

    $routingCacheManager->loadRoute(__DIR__ . '/api-rest/ApiV1Controller.php');