<?php

use function Pest\Laravel\getJson;

test('domain api routes require an access token', function () {
    getJson('/api/v1/projects')->assertUnauthorized();
});
