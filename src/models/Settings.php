<?php
/**
 * Instant Analytics plugin for Craft CMS
 *
 * Instant Analytics brings full Google Analytics support to your Twig templates
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2017 nystudio107
 */

namespace nystudio107\instantanalyticsGa4\models;

use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\validators\ArrayValidator;
use yii\behaviors\AttributeTypecastBehavior;

/**
 * @author    nystudio107
 * @package   InstantAnalytics
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * The default Google Analytics measurement ID used by GA4.
     * @var string
     */
    public $googleAnalyticsMeasurementId = '';

    /**
     * The default Google Analytics measurement API secret used by GA4.
     * @var string
     */
    public $googleAnalyticsMeasurementApiSecret = '';

    /**
     * The default Google Analytics tracking ID
     *
     * @var string
     */
    public $googleAnalyticsTracking = '';

    /**
     * Should the query string be stripped from the page tracking URL?
     *
     * @var bool
     */
    public $stripQueryString = true;

    /**
     * Should page views be sent automatically when a page view happens?
     *
     * @var bool
     */
    public $autoSendPageView = true;

    /**
     * If you plan to use Instant Analytics in conjunction with frontend JavaScript, this setting should be on, so that Instant Analytics requires a `clientId` from the frontend-set GA cookie before it will send analytics data.
     * @var bool
     */
    public $requireGaCookieClientId = true;

    /**
     * @var bool Should the GCLID cookie be created if it doesn't exist?
     */
    public $createGclidCookie = true;

    /**
     * @var int The session duration as set in GA4 admin settings.
     */
    public $sessionDuration = 30;

    /**
     * The field in a Commerce Product Variant that should be used for the
     * category
     *
     * @var string
     */
    public $productCategoryField = '';

    /**
     * The field in a Commerce Product Variant that should be used for the brand
     *
     * @var string
     */
    public $productBrandField = '';

    /**
     * Whether add to cart events should be automatically sent
     *
     * @var bool
     */
    public $autoSendAddToCart = true;

    /**
     * Whether remove from cart events should be automatically sent
     *
     * @var bool
     */
    public $autoSendRemoveFromCart = true;

    /**
     * Whether purchase complete events should be automatically sent
     *
     * @var bool
     */
    public $autoSendPurchaseComplete = true;

    /**
     * Controls whether Instant Analytics will send analytics data.
     *
     * @var bool
     */
    public $sendAnalyticsData = true;

    /**
     * Controls whether Instant Analytics will send analytics data when
     * `devMode` is on.
     *
     * @var bool
     */
    public $sendAnalyticsInDevMode = true;

    /**
     * Controls whether we should filter out bot UserGents.
     *
     * @var bool
     */
    public $filterBotUserAgents = true;

    /**
     * Controls whether we should exclude users logged into an admin account
     * from Analytics tracking.
     *
     * @var bool
     */
    public $adminExclude = false;

    /**
     * Controls whether analytics that blocked from being sent should be logged
     * to storage/logs/web.log These are always logged if `devMode` is on
     *
     * @var bool
     */
    public $logExcludedAnalytics = true;

    /**
     * Contains an array of Craft user group handles to exclude from Analytics
     * tracking.  If there's a match for any of them, analytics data is not
     * sent.
     *
     * @var array
     */
    public $groupExcludes = [
    ];

    /**
     * Contains an array of keys that correspond to $_SERVER[] super-global
     * array keys to test against. Each item in the sub-array is tested against
     * the $_SERVER[] super-global key via RegEx; if there's a match for any of
     * them, analytics data is not sent.  This allows you to filter based on
     * whatever information you want. Reference:
     * http://php.net/manual/en/reserved.variables.server.php RegEx tester:
     * http://regexr.com
     *
     * @var array
     */
    public $serverExcludes = [
        'REMOTE_ADDR' => [
            '/^localhost$|^127(?:\.[0-9]+){0,2}\.[0-9]+$|^(?:0*\:)*?:?0*1$/',
        ],
    ];

    // Public Methods
    // =========================================================================

    /**
     * @return array
     */
    public function defineRules(): array
    {
        return [
            [
                [
                    'stripQueryString',
                    'autoSendPageView',
                    'requireGaCookieClientId',
                    'createGclidCookie',
                    'autoSendAddToCart',
                    'autoSendRemoveFromCart',
                    'autoSendPurchaseComplete',
                    'sendAnalyticsData',
                    'sendAnalyticsInDevMode',
                    'filterBotUserAgents',
                    'adminExclude',
                    'logExcludedAnalytics',
                ],
                'boolean',
            ],
            [
                [
                    'googleAnalyticsTracking',
                    'productCategoryField',
                    'productBrandField',
                    'googleAnalyticsTracking',
                ],
                'string',
            ],
            [
                [
                    'groupExcludes',
                    'serverExcludes',
                ],
                ArrayValidator::class,
            ],
        ];
    }

    /**
     * @return array
     */
    public function behaviors(): array
    {
        return [
            'typecast' => [
                'class' => AttributeTypecastBehavior::class,
                // 'attributeTypes' will be composed automatically according to `rules()`
            ],
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => [
                    'googleAnalyticsTracking',
                ],
            ],
        ];
    }
}
