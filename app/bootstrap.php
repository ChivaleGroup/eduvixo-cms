<?php

declare(strict_types=1);

require __DIR__ . '/Site.php';

return new Eduvixo\Website\Site(require dirname(__DIR__) . '/config/site.php');
