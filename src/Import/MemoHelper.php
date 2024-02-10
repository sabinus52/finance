<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Import;

/**
 * Fonction de recherche de valeur dans le mémo.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class MemoHelper implements \Stringable
{
    /**
     * Constructeur.
     */
    public function __construct(private readonly string $memo)
    {
    }

    /**
     * Retourne la valeur du mémo.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->memo;
    }

    /**
     * Retourne le compte de placement ou le nom du titre boursier ou autre.
     * Exemple :
     *  Versement:[Mon placement] -> Mon placement
     *  Stock:[Crédit Agricole SA] -> Crédit Agricole SA.
     *
     * @return string|null
     */
    public function getLabelMemo(): ?string
    {
        preg_match('/:\[(.*)\]/', $this->memo, $matches);
        if (!isset($matches[1])) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Retourne le montant du placement sur les comptes de capitalisation si renseigné
     * ( <> montant débité sur le compte courant)
     * Exemple : Versement:[Mon placement] €1000 -> 1000.
     *
     * @return float
     */
    public function getAmountVersement(float $amount): float
    {
        $amount *= -1;

        preg_match('/€([0-9]*[.]?[0-9]+)/', $this->memo, $matches);
        if (isset($matches[1])) {
            $amount = (float) $matches[1];
        }

        return $amount;
    }

    /**
     * Retourne le volume en achat ou vente de titres
     * Exemple : Stock:[Crédit Agricole SA] v=10 p=12.34 -> 10.
     *
     * @return float|null
     */
    public function getStockVolume(): ?float
    {
        preg_match('/v=([0-9]*[.]?[0-9]+)/', $this->memo, $matches);
        if (!isset($matches[1])) {
            return null;
        }

        return (float) $matches[1];
    }

    /**
     * Retourne le prix du titre lors de l'achat ou la vente
     * Exemple : Stock:[Crédit Agricole SA] v=10 p=12.34 -> 12.34.
     *
     * @return float|null
     */
    public function getStockPrice(): ?float
    {
        preg_match('/p=([0-9]*[.]?[0-9]+)/', $this->memo, $matches);
        if (!isset($matches[1])) {
            return null;
        }

        return (float) $matches[1];
    }

    /**
     * Retourne le kilométrage du véhicule (d=).
     *
     * @return int|null
     */
    public function getVehiculeKiloMeterAge(): ?int
    {
        preg_match('/d=([0-9]+)/', $this->memo, $matches);
        if (!isset($matches[1])) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * Retourne le volume de carburant (v=).
     *
     * @return float|null
     */
    public function getVehiculeFuelVolume(): ?float
    {
        preg_match('/v=([0-9]*[.]?[0-9]+)/', $this->memo, $matches);
        if (!isset($matches[1])) {
            return null;
        }

        return (float) $matches[1];
    }

    /**
     * Retourne le modèle du véhicule.
     *
     * @return string|null
     */
    public function getVehiculeModel(): ?string
    {
        preg_match('/m=\[(.*)\]/', $this->memo, $matches);
        if (!isset($matches[1])) {
            return null;
        }

        return $matches[1];
    }
}
