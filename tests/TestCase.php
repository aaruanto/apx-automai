<?php

namespace Tests;

use App\Models\Setting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Setting caches its values in a static for the life of the process.
        // Model events clear it on save, but the whole suite runs in one
        // process, so without this a setting written by one test is still
        // cached after RefreshDatabase has rolled the row away.
        Setting::flushCache();
    }
}
