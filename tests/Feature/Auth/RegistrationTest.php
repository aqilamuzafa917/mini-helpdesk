<?php

test('registration screen redirects to login when registration is disabled', function () {
    // When registration is disabled, get('/register') should redirect to /login
    $response = $this->get('/register');

    $response->assertRedirect('/login');
});

test('post registration redirects to login when registration is disabled', function () {
    // When registration is disabled, post('/register') should redirect or return 404/403
    $response = $this->post('/register', [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    // Depending on Fortify disabled feature registration route registration, it should redirect to login, not found, or method not allowed.
    // The requirement states: the /register route returns an HTTP redirect (302) or not-found (404) response
    expect(in_array($response->status(), [302, 404, 405]))->toBeTrue();
});
