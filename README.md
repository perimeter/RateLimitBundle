Perimeter RateLimitBundle
=========================

[![Build Status](https://travis-ci.org/perimeter/RateLimitBundle.svg?branch=develop)](https://travis-ci.org/perimeter/RateLimitBundle)

Rate Limit those APIs!

Installation
------------

```
$ composer.phar require perimeter/rate-limit-bundle:dev-develop
```

Get Started
-----------

This library helps you rate limit your APIs in two ways:

 * **Rate Limit Warning**  - an `X-RATELIMIT-WARNING` header is issued in the response, but otherwise the call is unaffected.
 * **Rate Limit Exceeded** - An HTTP status code of `429 Too Many Requests` is returned, and the call is throttled.

### Configure your Meter Storage

The simplest option is to use `MemoryStorage` for meter configuration. Be default the warning header is issued at **80 calls/hour**, and the response is throttled at **100 calls/hour**. You can change these basic defaults in your service container:

```xml
<parameters>
    <parameter key="perimeter.rate_limit.warn_threshold.default">45000</parameter>
    <parameter key="perimeter.rate_limit.limit_threshold.default">50000</parameter>
</parameters>
```

This will now warn and limit your users at `45,000` and `50,000` calls per hour respectively. You can also customize different meters depending on the authenticated user:

```xml
<parameters>
    <parameter key="perimeter.rate_limit.storage.memory.meters" type="collection">
        <parameter key="*" type="collection">
            <parameter key="warn_threshold">%perimeter.rate_limit.warn_threshold.default%</parameter>
            <parameter key="limit_threshold">%perimeter.rate_limit.limit_threshold.default%</parameter>
        </parameter>
        <parameter key="bshaffer" type="collection">
            <parameter key="warn_threshold">150</parameter>
            <parameter key="limit_threshold">200</parameter>
        </parameter>
    </parameter>
</parameters>
```

This means any calls authenticated with the username `bshaffer` will get warned at `150/hr` and limited at `200/hr` instead of the default. If you plan on rate limiting by username, the `Doctrine Meter Storage` is highly recommended.

#### Doctrine Meter Storage (advanced)

Doctrine meter storage is the best way to configure meters dynamically. First, run the command to create the tables in your database:

    php vendor/doctrine/orm/bin/doctrine orm:schema-tool:update --force

Second, you will need to configure your container to use the doctrine storage engine:

```xml
<services>
    <!-- ... -->
    <service id="perimeter.rate_limit.storage" alias="perimeter.rate_limit.storage.doctrine" />
<services>
```

Next, you'll want to create the meters in your doctrine database. Do this using the `perimeter:rate-limit-meter` command, for example:

    $ ./bin/console perimeter:rate-limit-meter * 80 100

You *must* have a `default meter` configured when using Doctrine Meter Storage. The command above will create default meters which warn at `80 calls/hour`, and rate limit at `100 calls/hr`.

You can use the `perimeter:rate-limit-meter` command to view, create, update, and delete meters. There is also a `MeterApiController` that exposes a JSON/XML API to do this very thing.

### Configuring your Throttler

By default, this library uses the `RedisThrottler` for keeping track of hits and buckets. If `Redis` is not an option for throttling, you can use `DoctrineThrottler` for this as well, although this is not recommended as `Redis` is *much better suited* for this kind of thing.

#### Redis Throttler (default)

This library uses Redis by default. To get started with the redis throttler, as long as redis is running on `localhost:6379`, you don't have to do anything! If your redis server is running somewhere else, just configure `perimeter.rate_limit.redis_client.url` in your container to point to the proper host and port.

#### Doctrine Throttler

Be sure to run the following command to create the `rate_limit_bucket` table in your database:

    php vendor/doctrine/orm/bin/doctrine orm:schema-tool:update --force

Now, configure your container to use `Perimeter\RateLimitBundle\Throttler\DoctrineThrottler` by making the throttler service ID an alias to the doctrine throttler:

```xml
<services>
    <!-- ... -->
    <service id="perimeter.rate_limit.throttler" alias="perimeter.rate_limit.throttler.doctrine" />
<services>
```
