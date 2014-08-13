<?php

namespace NPS\CoreBundle\Constant;

/**
 * Class ImportConstants
 * @package NPS\CoreBundle\Constant
 */
class ImportConstants {
    //favorite
    const FAVORITE_ALL = 0; //return all
    const FAVORITE_YES = 1; //only return favorited items
    const FAVORITE_NOT = 2; //only return un-favorited items

    //content type
    const CONTENT_ALL = 0;     //return all
    const CONTENT_ARTICLE = 1; //only return articles
    const CONTENT_VIDEO = 2;   // only return videos or articles with embedded videos

    //tag
    const TAG_NOT = 'not';     //only untagged items

    //session
    const SESSION_CONTENT_TYPE = 'session_content_type';
    const SESSION_FAVORITE = 'session_favorite';
    const SESSION_LABEL_ID = 'session_label_id';
    const SESSION_TAG = 'session_tag';
    const SESSION_REQUEST_TOKEN = 'session_token_request';
}