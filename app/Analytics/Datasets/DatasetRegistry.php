<?php

namespace App\Analytics\Datasets;

use App\Analytics\Datasets\Definitions\TransactionDataset;
use LogicException;

final class DatasetRegistry
{
    /**
     * @var array<string, DatasetDefinition>
     */
    private array $definitions = [];

    public function __construct(?iterable $definitions = null)
    {
        foreach ($definitions ?? $this->defaultDefinitions() as $definition) {
            $key = $definition->key->value;

            if (isset($this->definitions[$key])) {
                throw new LogicException(
                    "Duplicate analytics dataset [$key].",
                );
            }

            $this->definitions[$key] = $definition;
        }
    }

    /**
     * @return list<DatasetDefinition>
     */
    public function all(): array
    {
        return array_values($this->definitions);
    }

    /**
     * @return list<DatasetDefinition>
     */
    public function active(): array
    {
        return array_values(
            array_filter(
                $this->definitions,
                fn (DatasetDefinition $definition): bool => $definition->status === DatasetStatus::ACTIVE,
            ),
        );
    }

    public function find(
        DatasetKey|string $identifier,
    ): ?DatasetDefinition {
        $key = $identifier instanceof DatasetKey
            ? $identifier
            : DatasetKey::tryFrom($identifier);

        if ($key === null) {
            return null;
        }

        return $this->definitions[$key->value] ?? null;
    }

    public function get(
        DatasetKey|string $identifier,
    ): DatasetDefinition {
        return $this->find($identifier)
            ?? throw UnknownDataset::forIdentifier($identifier);
    }

    /**
     * @return list<DatasetDefinition>
     */
    private function defaultDefinitions(): array
    {
        return [
            new DatasetDefinition(
                key: DatasetKey::CUSTOMER_OVERVIEW,
                label: 'Customer Overview',
                description: 'Customer demographics, lifecycle, segmentation, and risk information.',
                grain: 'One row per customer.',
                status: DatasetStatus::DRAFT,
            ),
            new DatasetDefinition(
                key: DatasetKey::ACCOUNT_BALANCES,
                label: 'Account Balances',
                description: 'Point-in-time ledger and available balances for banking accounts.',
                grain: 'One row per account per snapshot date.',
                status: DatasetStatus::DRAFT,
            ),
            TransactionDataset::definition(),
            new DatasetDefinition(
                key: DatasetKey::CARD_ACTIVITY,
                label: 'Card Activity',
                description: 'Card transaction activity, merchants, locations, and processing statuses.',
                grain: 'One row per card transaction.',
                status: DatasetStatus::DRAFT,
            ),
            new DatasetDefinition(
                key: DatasetKey::LOANS,
                label: 'Loans',
                description: 'Loan portfolio contracts, principal amounts, terms, and lifecycle statuses.',
                grain: 'One row per loan.',
                status: DatasetStatus::DRAFT,
            ),
            new DatasetDefinition(
                key: DatasetKey::LOAN_REPAYMENTS,
                label: 'Loan Repayments',
                description: 'Scheduled installments, payment progress, and delinquency information.',
                grain: 'One row per loan installment.',
                status: DatasetStatus::DRAFT,
            ),
            new DatasetDefinition(
                key: DatasetKey::BRANCH_PERFORMANCE,
                label: 'Branch Performance',
                description: 'Operational banking indicators summarized by branch.',
                grain: 'One row per branch per reporting period.',
                status: DatasetStatus::DRAFT,
            ),
        ];
    }
}
