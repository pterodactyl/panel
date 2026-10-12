<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Transfers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator as ValidatorFactory;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Pterodactyl\Contracts\Transfers\FailsTransfers;
use Pterodactyl\Contracts\Transfers\InitiatesTransfers;
use Pterodactyl\Enum\JwtScope;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerTransfer;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Pterodactyl\Validation\Concerns\ValidatesArrayExistence;
use Throwable;

final readonly class InitiateTransfer implements InitiatesTransfers
{
    use ValidatesArrayExistence;

    public function __construct(
        private NodeJWTService $nodeJWTService,
        private FailsTransfers $failTransfer,
    ) {}

    /**
     * Once these pass, validateAllocations() checks the allocations are free and on the node.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(Server $server): array
    {
        return [
            'node_id' => ['required', 'integer', 'exists:nodes,id', Rule::notIn([$server->node_id])],
            'allocation_id' => ['required', 'bail', 'unique:servers', 'exists:allocations,id'],
            'allocation_additional' => ['nullable', 'array', 'list'],
            'allocation_additional.*' => ['required', Rule::anyOf([['integer:strict'], ['string']]), 'integer'],
        ];
    }

    /**
     * Once the rules pass, adds an allocation_id error unless every allocation chosen is
     * unassigned and on the target node. They are counted in one query.
     */
    public static function validateAllocations(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $input = $validator->safe();
        $ids = array_unique([
            $input->integer('allocation_id'),
            ...array_map(fn (int|string $key): int => $input->integer('allocation_additional.'.$key), array_keys($input->array('allocation_additional'))),
        ]);

        $count = Allocation::query()
            ->whereIn('id', $ids)
            ->where('node_id', $input->integer('node_id'))
            ->whereNull('server_id')
            ->count();

        if ($count !== count($ids)) {
            $validator->errors()->add('allocation_id', 'The selected allocation must be an unassigned allocation on the target node.');
        }
    }

    public function initiate(Server $server, Node $node, int $allocationId, array $additionalAllocations): ServerTransfer
    {
        $additionalAllocations = $this->additionalAllocations($server, $node, $allocationId, $additionalAllocations);

        $transfer = DB::transaction(function () use ($server, $node, $allocationId, $additionalAllocations): ServerTransfer {
            $server = $server->newQuery()->whereKey($server->getKey())->lockForUpdate()->firstOrFail();
            throw_unless($node->isViable($server->memory, $server->disk), DisplayException::class, trans('admin/server.alerts.transfer_not_viable'));
            $server->validateTransferState();

            $allocationIds = array_values(array_unique([$allocationId, ...$additionalAllocations]));
            $allocations = Allocation::query()
                ->whereKey($allocationIds)
                ->where('node_id', $node->id)
                ->unassigned()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            throw_unless($allocations->count() === count($allocationIds), DisplayException::class, 'The selected allocations are no longer available on the target node.');

            $transfer = ServerTransfer::query()->create([
                'server_id' => $server->id,
                'old_node' => $server->node_id,
                'new_node' => $node->id,
                'old_allocation' => $server->allocation_id,
                'new_allocation' => $allocationId,
                'old_additional_allocations' => $server->allocations
                    ->reject(fn (Allocation $allocation): bool => $allocation->id === $server->allocation_id)
                    ->pluck('id')
                    ->values()
                    ->all(),
                'new_additional_allocations' => $additionalAllocations,
            ]);

            // Assign now so another server can't claim these allocations mid-transfer.
            Allocation::query()->whereKey($allocationIds)->update(['server_id' => $server->id]);

            return $transfer;
        });

        try {
            $token = $this->nodeJWTService
                ->setExpiresAt(CarbonImmutable::now()->addMinutes(15))
                ->setSubject($server->uuid)
                ->setScopes(JwtScope::ServerTransfer)
                ->handle($node, $server->uuid);

            Daemon::server($server)->transfer($node, $token);
        } catch (Throwable $throwable) {
            $this->failTransfer->fail($transfer);

            throw $throwable;
        }

        return $transfer->refresh();
    }

    /**
     * Missing additional allocations fail `allocation_additional.N` as an exists rule would, all
     * looked up in one query.
     *
     * @param  list<int>  $additionalAllocations
     * @return list<int>
     *
     * @throws ValidationException
     */
    private function additionalAllocations(Server $server, Node $node, int $allocationId, array $additionalAllocations): array
    {
        $validator = ValidatorFactory::make([
            'node_id' => $node->id,
            'allocation_id' => $allocationId,
            'allocation_additional' => $additionalAllocations,
        ], self::rules($server));
        $validator->after(function () use ($validator): void {
            $this->validateArrayExistence($validator, 'allocation_additional', Allocation::query());
            self::validateAllocations($validator);
        });

        $input = $validator->safe();

        return array_map(fn (int|string $key): int => $input->integer('allocation_additional.'.$key), array_keys($input->array('allocation_additional')));
    }
}
