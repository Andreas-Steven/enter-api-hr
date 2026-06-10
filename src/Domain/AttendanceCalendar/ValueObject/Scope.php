<?php

declare(strict_types=1);

namespace App\Domain\AttendanceCalendar\ValueObject;

use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

final class Scope
{
    /**
     * @param array<int, array{id: int, code: string, name: string}> $branches
     */
    private function __construct(
        private readonly array $branches
    ) {
    }

    /**
     * @param array<int, array{id: int, code: string, name: string}> $branches
     */
    public static function create(array $branches): self
    {
        return new self(
            self::validateBranches($branches)
        );
    }

    /**
     * @param array<int, array<string, mixed>> $scopeData
     * @return self
     */
    public static function fromArray(array $scopeData): self
    {
        if (empty($scopeData)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'scope.payload_empty',
                    domain: 'validation'
                )
            );
        }

        $branches = [];

        foreach ($scopeData as $item) {
            if (isset($item['id']) && isset($item['code']) && isset($item['name'])) {
                $branches[] = [
                    'id' => (int) $item['id'],
                    'code' => (string) $item['code'],
                    'name' => (string) $item['name'],
                ];
            }
        }

        return new self(
            self::validateBranches($branches)
        );
    }

    /**
     * @param string $json
     * @return self
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'scope.invalid_json',
                    domain: 'validation'
                )
            );
        }

        return self::fromArray($data);
    }

    /**
     * @return array<int, array{id: int, code: string, name: string}>
     */
    public function getBranches(): array
    {
        return $this->branches;
    }

    /**
     * @return array<int, int>
     */
    public function getBranchIds(): array
    {
        return array_map(fn($branch) => $branch['id'], $this->branches);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(
            fn(array $branch) => [
                'id' => $branch['id'],
                'code' => $branch['code'],
                'name' => $branch['name'],
            ],
            $this->branches
        );
    }

    /**
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * @param array<int, array{id: int, code: string, name: string}> $branches
     * @return array<int, array{id: int, code: string, name: string}>
     */
    private static function validateBranches(array $branches): array
    {
        if (empty($branches)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'scope.branches_empty',
                    domain: 'validation'
                )
            );
        }

        $validated = [];
        $seenIds = [];

        foreach ($branches as $branch) {
            if (!isset($branch['id']) || !is_int($branch['id'])) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'scope.branch_id_invalid',
                        domain: 'validation'
                    )
                );
            }
            if (!isset($branch['code']) || !is_string($branch['code']) || trim($branch['code']) === '') {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'scope.branch_code_invalid',
                        domain: 'validation'
                    )
                );
            }
            if (!isset($branch['name']) || !is_string($branch['name']) || trim($branch['name']) === '') {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'scope.branch_name_invalid',
                        domain: 'validation'
                    )
                );
            }

            $branchId = $branch['id'];
            if (in_array($branchId, $seenIds, true)) {
                continue; // Skip duplicate branches
            }
            $seenIds[] = $branchId;

            $validated[] = [
                'id' => $branch['id'],
                'code' => trim($branch['code']),
                'name' => trim($branch['name']),
            ];
        }

        if (empty($validated)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'scope.branches_empty_after_validation',
                    domain: 'validation'
                )
            );
        }

        return array_values($validated);
    }

    /**
     * Validate that all branch IDs exist in the system
     *
     * @param array<int, array{id: int, code: string, name: string}> $branches
     * @param callable $existsById Function that takes a branch ID and returns true if it exists
     * @return void
     * @throws BadRequestException if the payload contains invalid branch references
     */
    public static function validateBranchesExist(array $branches, callable $existsById): void
    {
        foreach ($branches as $branch) {
            if (!isset($branch['id']) || !is_int($branch['id'])) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'scope.branch_id_invalid',
                        domain: 'validation'
                    )
                );
            }

            $branchId = $branch['id'];
            if (!$existsById($branchId)) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'scope.branch_not_found',
                        domain: 'validation',
                        params: ['id' => (string) $branchId]
                    )
                );
            }
        }
    }

    public function equals(self $other): bool
    {
        return $this->branches === $other->branches;
    }
}
