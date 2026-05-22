<?php

declare(strict_types=1);

namespace Saso\Application\MyPage\Passkey;

use DateTimeImmutable;

/**
 * View-model row describing a single passkey held by Auth0.
 *
 * `id` is Auth0's `authentication_method_id` — the value the Management
 * API expects on `DELETE /api/v2/users/{user_id}/authentication-methods/{id}`.
 * The template renders these into delete-form `<input name="passkey_id">`.
 *
 * `name` is the user-friendly label Auth0 stores (e.g. "iPhone — Face ID").
 * It can be empty when the device did not advertise a name; the view
 * substitutes a generic placeholder in that case.
 */
final readonly class Auth0Passkey
{
    public function __construct(
        public string $id,
        public string $name,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $lastUsedAt,
    ) {
    }

    /**
     * @return array{id: string, name: string, created_at: ?string, last_used_at: ?string}
     */
    public function toTemplateRow(): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'created_at'   => $this->createdAt?->format('Y-m-d H:i'),
            'last_used_at' => $this->lastUsedAt?->format('Y-m-d H:i'),
        ];
    }
}
