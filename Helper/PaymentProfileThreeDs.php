<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Helper;

use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;

class PaymentProfileThreeDs
{
    /**
     * @var array
     */
    private $activeThreeDsStatuses = [
        PaymentProfileInterface::THREE_DS_PENDING_AUTHENTICATION,
        PaymentProfileInterface::THREE_DS_AUTHENTICATED,
        PaymentProfileInterface::THREE_DS_AUTHENTICATION_FAILED
    ];

    /**
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $profile
     * @return bool
     */
    public function hasThreeDsStatus(PaymentProfileInterface $profile)
    {
        return in_array($profile->getThreeDsStatus(), $this->activeThreeDsStatuses, true);
    }

    /**
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $profile
     * @return bool
     */
    public function isThreeDsFailed(PaymentProfileInterface $profile)
    {
        return $profile->getThreeDsStatus() === PaymentProfileInterface::THREE_DS_AUTHENTICATION_FAILED;
    }

    /**
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $profile
     * @return bool
     */
    public function isThreeDsAuthenticated(PaymentProfileInterface $profile)
    {
        return $profile->getThreeDsStatus() === PaymentProfileInterface::THREE_DS_AUTHENTICATED;
    }
}
