<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper\Report;

use App\Entity\Transaction;

/**
 * Rapport sur les catégories.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
final class CategoryReport
{
    /**
     * Calcul de la somme total par regroupement des catégories.
     *
     * @param Transaction[] $transactions
     *
     * @return array<mixed>
     */
    public function reGroupTotalAmountByCategory(array $transactions): array
    {
        $categories = [];

        foreach ($transactions as $transaction) {
            $idCat = $transaction->getCategory()->getId();
            if (!array_key_exists($idCat, $categories)) {
                $categories[$idCat] = [
                    'datas' => $transaction->getCategory(),
                    'total' => 0.0,
                ];
            }
            $categories[$idCat]['total'] += $transaction->getAmount();
        }
        // Tri descendant
        usort($categories, static fn ($aaa, $bbb): bool => $aaa['total'] > $bbb['total']);  /** @phpstan-ignore-line */

        return $categories;
    }
}
