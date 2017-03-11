# DisqueAdminProvider

A web-based management/monitoring GUI for [Disque](https://github.com/antirez/disque)

## Dependencies

* Silex 2
* [Twig](TwigServiceProvider) support, with the twig bridge
* [Service Controller](http://silex.sensiolabs.org/doc/2.0/providers/service_controller.html) support
* [HTTP Fragment](http://silex.sensiolabs.org/doc/2.0/providers/http_fragment.html) support

## Installation

### Manual

1. Register the provider. The routes will be mounted for you at a configurable prefix:
    ```php
    $app->register(new DisqueAdminProvider(), [
        // The path at which the admin routes will be mounted
        'disque_admin.mount_prefix' => '/_disque',

        // Connection details
        'disque_admin.host' => '10.10.10.10',
        'disque_admin.password' => 'nimrod',
    ]);
    ```
2. Link (or serve, using your webserver configuration) the `resources/public` directory
    at the same prefix (using `try_files` for example, so we still fall back to PHP for missing paths).
    e.g.
    ```bash
    cd web && ln -s ../vendor/varspool/disque-admin-provider/resources/public _disque
    ```

## Configuration

### Passing multiple connections

To pass multiple connections, extend the `disque_admin.credentials` service, and return
and array of `Disque\Connection\Credentials` instances.
