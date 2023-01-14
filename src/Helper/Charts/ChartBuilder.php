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
 * Construction des graphiques.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class ChartBuilder
{
    /**
     * Liste des libellés des graphiques.
     *
     * @var array<mixed>
     */
    protected $labels;

    /**
     * Liste des données.
     *
     * @var array<mixed>
     */
    protected $datasets;

    /**
     * Valeurs du DataSet.
     *
     * @var array<mixed>
     */
    protected $values;

    /**
     * Liste des options.
     *
     * @var array<mixed>
     */
    protected $options;

    /**
     * @param array<mixed> $labels
     *
     * @return self
     */
    public function setLabels(array $labels): self
    {
        $this->labels = $labels;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * @param array<mixed> $datasets
     *
     * @return self
     */
    public function setDatasets(array $datasets): self
    {
        $this->datasets = $datasets;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param array<mixed> $values
     *
     * @return self
     */
    public function setValues(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getDataSets(): array
    {
        return $this->datasets;
    }

    /**
     * @param array<mixed> $options
     *
     * @return self
     */
    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
