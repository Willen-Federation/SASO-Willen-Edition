# 0011 — Flexible item attributes (EAV) + storage location codes

* Status: accepted
* Date: 2026-04-26
* Deciders: Willen Federation contributors

## Context and Problem Statement

The M6 scope adds two new data shapes:

1. **Flexible categories / specs** — operators define their own attributes (size, weight, ISBN, country of origin, …) per item, with types and units they choose. New attributes appear without a schema migration.
2. **Storage location codes** — every item has a stable, hierarchical location code (warehouse → row → column → bin) that operators print on shelves and scan with barcode readers. Codes must be operator-editable, deterministic, and unique within a location tree.

The legacy `category` table is a fixed hierarchical tree; the legacy `shelf` module models locations but with no code generator and no hierarchy beyond a flat name. Neither covers the M6 ask.

## Decision Drivers

- **Operators must add attributes without code changes.** This is the explicit M6 requirement.
- **Existing `Item` rows must continue to load** — schema-wise this is additive.
- **Location codes must be deterministic** so an operator who reprints a label gets the same code back.
- **Compliance with ADR 0001** — domain code talks to repository interfaces; the EAV plumbing stays in `Infrastructure/`.

## Considered Options for flexible attributes

### Option A — Wide table per item type

Add columns for every attribute. Rejected: a wide-then-wider table forces migrations for every new spec the operator wants.

### Option B — JSON column on `Item`

Attach an `attributes JSON` column to `Item`.

* (+) Trivial schema.
* (−) No type safety. No way for the admin UI to enumerate "what attributes are defined?". No reasonable indexing for `WHERE attributes->>'$.size' = 'L'` on MariaDB (functional indexes exist but operators on shared hosting may not have permission to create them).
* (−) Search becomes a OpenSearch responsibility only — no MariaDB-side filter.

### Option C — Classic EAV: `item_attribute_definition` + `item_attribute_value`

Two tables: a definition (name, type, unit, required, validation regex) and a value (`item_id` × `attribute_id` → typed value column). The admin UI reads the definition table to drive the form; values are stored typed.

* (+) Type-safe per attribute. Admin UI is data-driven.
* (+) Operators can add an attribute and the form updates immediately.
* (−) JOINs to render an item card. Mitigated by caching denormalised projections in OpenSearch (cf. ADR 0010).

## Considered Options for location codes

### Option A — Free-text label on `shelf`

* (−) Two operators typing "row3" vs "Row 3" produce different keys. No barcode-friendly format.

### Option B — Hierarchical `storage_location` table with a generated code

A `storage_location` table holds the tree (`parent_id`, `name`, `position`) and a `code` column populated by a deterministic generator: `<warehouse-prefix>-<row>-<col>-<bin>`, padded so the code sorts naturally. The generator runs in PHP and the DB enforces unique `code`.

* (+) Codes are stable and deterministic.
* (+) Operators get printable labels straight from the admin UI.
* (+) Tree structure handles "warehouse 2 has different aisle widths than warehouse 1".

## Decision Outcome

**Flexible attributes: Option C (EAV).**
**Storage locations: Option B (hierarchical + generated code).**

### Schema sketch

```sql
CREATE TABLE item_attribute_definition (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    code            VARCHAR(120) NOT NULL UNIQUE,           -- 'size', 'isbn', 'country_of_origin'
    label_en        VARCHAR(200) NOT NULL,
    label_ja        VARCHAR(200) NOT NULL,
    value_type      ENUM('string','int','float','bool','enum','barcode') NOT NULL,
    unit            VARCHAR(40) NULL,                       -- 'kg', 'cm', 'mL'
    required        TINYINT(1) NOT NULL DEFAULT 0,
    enum_values     JSON NULL,                              -- ['S','M','L'] when value_type='enum'
    validation_regex VARCHAR(500) NULL,
    sort_order      INT NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL,
    updated_at      DATETIME NOT NULL
);

CREATE TABLE item_attribute_value (
    item_id           BIGINT UNSIGNED NOT NULL,
    attribute_id      BIGINT UNSIGNED NOT NULL,
    value_string      VARCHAR(500) NULL,
    value_int         BIGINT NULL,
    value_float       DOUBLE NULL,
    value_bool        TINYINT(1) NULL,
    PRIMARY KEY (item_id, attribute_id),
    KEY idx_attr (attribute_id, value_string)
);

CREATE TABLE storage_location (
    id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    parent_id    BIGINT UNSIGNED NULL,
    code         VARCHAR(120) NOT NULL UNIQUE,
    name         VARCHAR(200) NOT NULL,
    position     INT NOT NULL DEFAULT 0,
    depth        TINYINT UNSIGNED NOT NULL,
    created_at   DATETIME NOT NULL,
    updated_at   DATETIME NOT NULL,
    KEY idx_parent (parent_id, position)
);

CREATE TABLE similar_item (
    item_id        BIGINT UNSIGNED NOT NULL,
    similar_to_id  BIGINT UNSIGNED NOT NULL,
    similarity     FLOAT NOT NULL,                          -- cosine in [0,1]
    method         ENUM('image','text','hybrid') NOT NULL,
    last_updated   DATETIME NOT NULL,
    PRIMARY KEY (item_id, similar_to_id, method),
    KEY idx_similarity (item_id, similarity DESC)
);
```

### Code shape

```
src/
├── Domain/
│   ├── Item/
│   │   ├── ItemAttributeDefinition.php
│   │   ├── ItemAttributeValue.php
│   │   └── Repository/
│   │       ├── AttributeDefinitionRepository.php
│   │       └── AttributeValueRepository.php
│   └── StorageLocation/
│       ├── LocationCode.php             # value object with generator
│       ├── StorageLocation.php
│       └── Repository/
│           └── StorageLocationRepository.php
```

### Auto-population (composes with ADR 0009)

When an operator scans a barcode the application calls `AiAssistant::extractStructured()` against the catalogue / web-fetched product page and writes the inferred attribute values. The operator reviews and confirms before persistence. AI-derived values carry a `source = 'ai'` audit field; manually-entered values carry `source = 'human'` so the admin UI can flag fields a human has not yet vetted.

### Similarity persistence

`similar_item` is computed offline (Symfony Messenger job, ADR 0013) from the OpenSearch k-NN index (ADR 0010). Cached so registration-time UI can show "did you mean…?" without a vector query on every keystroke.

## Consequences

* Adding an attribute is a UI action, not a code change.
* Storage labels printed today match the system tomorrow — codes are stable.
* The EAV pattern has a real query cost (JOINs) — we mitigate by indexing common attributes in OpenSearch (ADR 0010) and only hitting MariaDB for transactional reads.
* Search-by-attribute hits OpenSearch (`attributes.name = X AND attributes.value = Y`); it does not hit `item_attribute_value` directly except from admin "raw export" pages.
* `similar_item` keeps M6 fast on the registration form. Stale entries are tolerable; the offline job re-runs on item write.
