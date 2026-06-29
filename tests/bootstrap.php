<?php

require __DIR__ . '/../vendor/autoload.php';

/*
 | The test suite leans heavily on Faker, which draws from the global mt_rand()
 | stream. PHP seeds that stream randomly for every process, so an unseeded run
 | produced different fixture data each time and was intermittently red. Seeding
 | the stream once, before any test runs, makes the whole suite deterministic and
 | reproducible (override with the FAKER_SEED environment variable when needed).
 */
mt_srand((int) ($_SERVER['FAKER_SEED'] ?? getenv('FAKER_SEED') ?: 90210));
