<?php

namespace NPS\CoreBundle\Constant;

/**
 * Class EntityConstants
 * @package NPS\CoreBundle\Constant
 */
class EntityConstants {
    //sync status
    const STATUS_NORMAL = 1;
    const STATUS_NEW = 2;
    const STATUS_CHANGED = 3;
    const STATUS_DELETED = 4;

    //read / unread status
    const STATUS_UNREAD = 1;
    const STATUS_READ = 2;
    const STATUS_READ_CHANGE = null;
}