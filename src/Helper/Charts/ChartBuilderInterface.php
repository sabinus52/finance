<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper\Charts;

/**
 * Interface de la construction des graphiques.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
interface ChartBuilderInterface
{
    /**
     * Constructeur.
     */
    public function __construct();

    /**
     * Retourne les options du graphique.
     *
     * @return array<mixed>
     */
    public function getOptions(): array;

    /**
     * Retourne les données du graphique.
     *
     * @param mixed $datas
     *
     * @return array<mixed>
     */
    public function getData($datas): array;
}
