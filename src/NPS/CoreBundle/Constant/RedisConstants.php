<?php
namespace NPS\CoreBundle\Constant;

/**
 * Class RedisConstants
 *
 * @package NPS\CoreBundle\Constant
 */
class RedisConstants {
    /* Feed */
    const FEED_MENU_ALL = "feed_menu_all";

    /* Item */
    const ITEM_URL_HASH = "item_url_hash";
    const ITEM_TITLE_HASH = "item_title_hash";

    /* Label */
    const LABEL_DELETED = "user_labels_deleted";
    const IMPORT_LATER_ITEMS = 'import-later-items';
    const LABEL_TREE = "user_labels_tree";
    const LABEL_MENU_ALL = "labels_menu_all";

    /* User */
    const USER_ACTIVATION_CODE = "user_verify";
    const USER_PASSWORD_RECOVERY = "user_password_recovery";
}