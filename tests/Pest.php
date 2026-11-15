<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
 * Tous les tests « Feature » tournent sur la base MariaDB de test
 * (yewtod_test), reconstruite une fois puis isolée par transaction.
 * Aucune requête HTTP sortante n'est autorisée pendant les tests.
 */
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => Http::preventStrayRequests())
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');
