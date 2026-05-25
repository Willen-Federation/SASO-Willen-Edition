# 0013 — Background job queue via Symfony Messenger

* Status: accepted
* Date: 2026-04-26
* Deciders: Willen Federation contributors

## Context and Problem Statement

M6 introduces work that must not run on the request thread:

- **Image embedding** — calling an LLM vision endpoint on item registration takes 2-10 seconds. The operator should not stare at a spinner.
- **OpenSearch indexing** — every item write enqueues an upsert; bulk index rebuilds run as long-lived jobs.
- **Similar-item recomputation** — re-runs after writes against the k-NN index.
- **AI rate-limit smoothing** — when 30 operators register items in the same minute we must spread vendor calls across the rate-limit window.
- **Notifications** — Slack / email when the feature-flag circuit breaker trips (cf. ADR 0005).

We need a **named, durable, retryable** background-job system that fits PHP and the rest of our stack.

## Decision Drivers

- **PHP-native** — no Ruby Sidekiq, no Java daemons. Operators run one runtime.
- **Composes with ADR 0012's Redis** — when Redis is configured, use it as the transport; when not, fall back to MariaDB.
- **Reasonable retry semantics** — exponential backoff, dead-letter queue, max-retry cap.
- **Inspectable** — operators can list pending and failed messages from a CLI.
- **Compliance with ADR 0001** — message handlers live in `Application/`, message classes in `Domain/`; transport configuration is `Infrastructure/`.

## Considered Options

### Option A — Sidekiq (Ruby)

* (+) Industry standard.
* (−) Requires a Ruby runtime alongside PHP. Out of scope.

### Option B — Roll our own queue (DB-backed, polled by a worker script)

* (+) Zero dependency.
* (−) Reinvents retries, backoff, dead letters, and serialisation. Symfony Messenger already does these.

### Option C — Laravel Queue

* (+) Mature, ergonomic.
* (−) Requires pulling in the Laravel container + Eloquent foundations. Doesn't compose with the rest of our Symfony-flavoured choices (ADR 0012's translator, M3-D's rate-limiter glue).

### Option D — Symfony Messenger

* (+) Library; no framework lock-in.
* (+) First-class Redis Streams + Doctrine transports. We use both already in this project (Redis from ADR 0012, Doctrine through symfony/yaml's lineage).
* (+) Standard handlers, middleware (logging, retry, transaction wrapping), failure transport.
* (+) `messenger:consume` CLI works under any process supervisor — systemd, supervisord, the Docker container, or a cron-driven worker on shared hosting.
* (−) Configuration ergonomics designed for the full Symfony framework; we wire it manually. We accept this — M3 already taught the codebase how to compose Symfony components without the kernel.

## Decision Outcome

**Chosen option: D — Symfony Messenger.**

### Composition

```
Saso\Domain\Messaging\
    Message\                        # plain DTOs
        IndexItem.php
        EmbedItem.php
        RecomputeSimilar.php
        NotifyFlagAutoDisabled.php

Saso\Application\Messaging\Handler\
    IndexItemHandler.php
    EmbedItemHandler.php
    RecomputeSimilarHandler.php
    NotifyFlagAutoDisabledHandler.php

Saso\Infrastructure\Messaging\
    MessageBusFactory.php           # builds Symfony\Component\Messenger\MessageBus
    Transport\
        TransportFactory.php        # 'redis://...' OR 'doctrine://default'
```

### Transport selection

Configurable in `system_setting` (`messaging.transport = redis | doctrine`). Default chooses based on Redis availability:

| Configured driver | Transport |
|---|---|
| `cache.driver = redis` | Redis Streams (`messenger://redis/<group>`) |
| `cache.driver = null` | Doctrine on MariaDB (`messenger_messages` table) |

Operators on shared hosting get the Doctrine transport — which is slower but works without Redis. Operators with Redis (preferred) get Streams.

### Retry policy

Default: 3 retries, exponential backoff (1 s, 5 s, 30 s), then sent to a `failed` transport. The `messenger:failed:show` and `messenger:failed:retry` commands are exposed in `make` targets:

```
make messenger-consume       # consume the default transport
make messenger-stats         # pending counts per transport
make messenger-retry         # retry every failed message
```

### Failure handling

Failed messages emit `SASO-INFRA-9100` (new code, range reserved for messaging). The audit row carries the message class, the transport, the retry count, and a truncated stack trace. The admin UI (M6-G) shows the failure list.

### Deployment

- **Docker**: `docker-compose.yml` adds a `worker` service running `make messenger-consume`. Restart-on-failure.
- **Shared hosting**: cron entry runs `php bin/console messenger:consume async --time-limit=55` every minute. (`--time-limit=55` because shared cPanel cron typically caps a job at 60 s.)

## Consequences

- One `composer require symfony/messenger`.
- The `worker` profile in Docker is opt-in (`docker compose --profile worker up`). Default `make up` keeps the existing footprint.
- Message handlers can be tested in isolation (instantiate the handler with mocked dependencies; assert side effects on a fake repository / fake search index).
- `messenger:consume` is the unit of restart for handler-code deploys; the legacy front controller is not affected.
- This ADR does not pin a worker process supervisor. Operators choose between systemd, supervisord, Docker's restart policy, or cron, depending on their hosting shape.
