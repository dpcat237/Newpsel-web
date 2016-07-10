<?php

namespace NPS\CoreBundle\Helper;

use NPS\CoreBundle\Entity\Preference;
use NPS\CoreBundle\Entity\User;

/**
 * Class PremiumUserHelper
 *
 * @package NPS\CoreBundle\Helper
 */
class PremiumUserHelper
{
    /**
     * Check premium permissions for automatically add feeds items to dictation
     *
     * @param User $user
     *
     * @return bool
     */
    public static function autoFeedToDictationPermissions(User $user)
    {
        if ($user->getPremiumType() == Preference::PREMIUM_SUBSCRIPTION_NORMAL) {
            return true;
        }

        return false;
    }
}
