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
     */
    public function getAmountVersement(float $amount): float
    {
        $amount *= -1;

        preg_match('/€(\d*[.]?\d+)/', $this->memo, $matches);
        if (isset($matches[1])) {
            return (float) $matches[1];
        }

        return $amount;
    }

    /**
     * Retourne le volume en achat ou vente de titres
     * Exemple : Stock:[Crédit Agricole SA] v=10 p=12.34 -> 10.
     */
    public function getStockVolume(): ?float
    {
        preg_match('/v=(\d*[.]?\d+)/', $this->memo, $matches);
        if (!isset($matches[1])) {
            return null;
        }

        return (float) $matches[1];
    }

    /**
     * Retourne le prix du titre lors de l'achat ou la vente
     * Exemple : Stock:[Crédit Agricole SA] v=10 p=12.34 -> 12.34.
     */
    public function getStockPrice(): ?float
    {
        preg_match('/p=(\d*[.]?\d+)/', $this->memo, $matches);
        if (!isset($matches[1])) {
            return null;
        }

        return (float) $matches[1];
    }

    /**
     * Retourne le kilométrage du véhicule (d=).
     */
    public function getVehiculeKiloMeterAge(): ?int
    {
        preg_match('/d=(\d+)/', $this->memo, $matches);
        if (!isset($matches[1])) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * Retourne le volume de carburant (v=).
     */
    public function getVehiculeFuelVolume(): ?float
    {
        preg_match('/v=(\d*[.]?\d+)/', $this->memo, $matches);
        if (!isset($matches[1])) {
            return null;
        }

        return (float) $matches[1];
    }

    /**
     * Retourne le modèle du véhicule.
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
