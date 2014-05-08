<?php
namespace NPS\CoreBundle;

/**
 * Core Events related with all core entities
 */
class NPSCoreEvents
{
/** Feed **/
    /**
     * Event to fire when was created new feed
     */
    const FEED_CREATED = 'nps.feed.created';

/** User **/
    /**
     * Event to fire when a user signs upt
     */
    const USER_SIGN_UP = 'nps.user.signup';
}
