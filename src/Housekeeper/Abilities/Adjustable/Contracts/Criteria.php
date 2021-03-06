<?php

namespace Housekeeper\Abilities\Adjustable\Contracts;

use Housekeeper\Contracts\Repository as RepositoryContract;

/**
 * Interface Criteria
 *
 * @author  AaronJan <https://github.com/AaronJan/Housekeeper>
 * @package Housekeeper\Ablities\Adjustable\Contracts
 */
interface Criteria
{
    /**
     * Apply criteria to repository
     *
     * @param RepositoryContract $repository
     */
    public function apply(RepositoryContract $repository);
}