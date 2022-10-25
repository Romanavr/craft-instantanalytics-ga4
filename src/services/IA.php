<?php
/**
 * Instant Analytics plugin for Craft CMS
 *
 * Instant Analytics brings full Google Analytics support to your Twig templates
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2017 nystudio107
 */

namespace nystudio107\instantanalytics\services;

use Craft;
use craft\base\Component;
use craft\elements\User as UserElement;
use craft\errors\MissingComponentException;
use craft\helpers\UrlHelper;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use nystudio107\instantanalytics\helpers\IAnalytics;
use nystudio107\instantanalytics\InstantAnalytics;
use nystudio107\seomatic\Seomatic;
use yii\base\Exception;
use function array_slice;
use function is_array;

/** @noinspection MissingPropertyAnnotationsInspection */

/**
 * @author    nystudio107
 * @package   InstantAnalytics
 * @since     1.0.0
 */
class IA extends Component
{
    // Constants
    // =========================================================================

    const DEFAULT_USER_AGENT = "User-Agent:Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13\r\n";

    // Public Methods
    // =========================================================================

    /**
     * @var null|IAnalytics
     */
    protected $cachedAnalytics;

    /**
     * Get the global variables for our Twig context
     *
     * @param $title
     *
     * @return null|IAnalytics
     */
    public function getGlobals($title)
    {
        if ($this->cachedAnalytics) {
            $analytics = $this->cachedAnalytics;
        } else {
            $analytics = $this->pageViewAnalytics('', $title);
            $this->cachedAnalytics = $analytics;
        }

        return $analytics;
    }

    /**
     * Get a PageView analytics object
     *
     * @param string $url
     * @param string $title
     *
     * @return null|IAnalytics
     */
    public function pageViewAnalytics($url = '', $title = '')
    {
        $result = null;
        $analytics = $this->analytics();
        if ($analytics) {
            $url = $this->documentPathFromUrl($url);
            // Prepare the Analytics object, and send the pageview
            $analytics->setDocumentPath($url)
                ->setDocumentTitle($title);
            $result = $analytics;
            Craft::info(
                Craft::t(
                    'instant-analytics',
                    'Created sendPageView for: {url} - {title}',
                    [
                        'url' => $url,
                        'title' => $title,
                    ]
                ),
                __METHOD__
            );
        }

        return $result;
    }

    /**
     * Get an Event analytics object
     *
     * @param string $eventCategory
     * @param string $eventAction
     * @param string $eventLabel
     * @param int $eventValue
     *
     * @return null|IAnalytics
     */
    public function eventAnalytics($eventCategory = '', $eventAction = '', $eventLabel = '', $eventValue = 0)
    {
        $result = null;
        $analytics = $this->analytics();
        if ($analytics) {
            $url = $this->documentPathFromUrl();
            $analytics->setDocumentPath($url)
                ->setEventCategory($eventCategory)
                ->setEventAction($eventAction)
                ->setEventLabel($eventLabel)
                ->setEventValue((int)$eventValue);
            $result = $analytics;
            Craft::info(
                Craft::t(
                    'instant-analytics',
                    'Created sendPageView for: {eventCategory} - {eventAction} - {eventLabel} - {eventValue}',
                    [
                        'eventCategory' => $eventCategory,
                        'eventAction' => $eventAction,
                        'eventLabel' => $eventLabel,
                        'eventValue' => $eventValue,
                    ]
                ),
                __METHOD__
            );
        }

        return $result;
    }

    /**
     * getAnalyticsObject() return an analytics object
     *
     * @return null|IAnalytics object
     */
    public function analytics()
    {
        $analytics = $this->getAnalyticsObj();
        Craft::info(
            Craft::t(
                'instant-analytics',
                'Created generic analytics object'
            ),
            __METHOD__
        );

        return $analytics;
    }

    /**
     * Get a PageView tracking URL
     *
     * @param $url
     * @param $title
     *
     * @return string
     * @throws Exception
     */
    public function pageViewTrackingUrl($url, $title): string
    {
        $urlParams = [
            'url' => $url,
            'title' => $title,
        ];
        $path = parse_url($url, PHP_URL_PATH);
        $pathFragments = explode('/', rtrim($path, '/'));
        $fileName = end($pathFragments);
        $trackingUrl = UrlHelper::siteUrl('instantanalytics/pageViewTrack/' . $fileName, $urlParams);
        Craft::info(
            Craft::t(
                'instant-analytics',
                'Created pageViewTrackingUrl for: {trackingUrl}',
                [
                    'trackingUrl' => $trackingUrl,
                ]
            ),
            __METHOD__
        );

        return $trackingUrl;
    }

    /**
     * Get an Event tracking URL
     *
     * @param        $url
     * @param string $eventCategory
     * @param string $eventAction
     * @param string $eventLabel
     * @param int $eventValue
     *
     * @return string
     * @throws Exception
     */
    public function eventTrackingUrl(
        $url,
        $eventCategory = '',
        $eventAction = '',
        $eventLabel = '',
        $eventValue = 0
    ): string
    {
        $urlParams = [
            'url' => $url,
            'eventCategory' => $eventCategory,
            'eventAction' => $eventAction,
            'eventLabel' => $eventLabel,
            'eventValue' => $eventValue,
        ];
        $fileName = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_BASENAME);
        $trackingUrl = UrlHelper::siteUrl('instantanalytics/eventTrack/' . $fileName, $urlParams);
        Craft::info(
            Craft::t(
                'instant-analytics',
                'Created eventTrackingUrl for: {trackingUrl}',
                [
                    'trackingUrl' => $trackingUrl,
                ]
            ),
            __METHOD__
        );

        return $trackingUrl;
    }

    /**
     * _shouldSendAnalytics determines whether we should be sending Google
     * Analytics data
     *
     * @return bool
     */
    public function shouldSendAnalytics(): bool
    {
        $result = true;

        $request = Craft::$app->getRequest();

        if (!InstantAnalytics::$settings->sendAnalyticsData) {
            $this->logExclusion('sendAnalyticsData');

            return false;
        }

        if (!InstantAnalytics::$settings->sendAnalyticsInDevMode && Craft::$app->getConfig()->getGeneral()->devMode) {
            $this->logExclusion('sendAnalyticsInDevMode');

            return false;
        }

        if ($request->getIsConsoleRequest()) {
            $this->logExclusion('Craft::$app->getRequest()->getIsConsoleRequest()');

            return false;
        }

        if ($request->getIsCpRequest()) {
            $this->logExclusion('Craft::$app->getRequest()->getIsCpRequest()');

            return false;
        }

        if ($request->getIsLivePreview()) {
            $this->logExclusion('Craft::$app->getRequest()->getIsLivePreview()');

            return false;
        }

        // Check the $_SERVER[] super-global exclusions
        if (InstantAnalytics::$settings->serverExcludes !== null
            && is_array(InstantAnalytics::$settings->serverExcludes)) {
            foreach (InstantAnalytics::$settings->serverExcludes as $match => $matchArray) {
                if (isset($_SERVER[$match])) {
                    foreach ($matchArray as $matchItem) {
                        if (preg_match($matchItem, $_SERVER[$match])) {
                            $this->logExclusion('serverExcludes');

                            return false;
                        }
                    }
                }
            }
        }

        // Filter out bot/spam requests via UserAgent
        if (InstantAnalytics::$settings->filterBotUserAgents) {
            $crawlerDetect = new CrawlerDetect;
            // Check the user agent of the current 'visitor'
            if ($crawlerDetect->isCrawler()) {
                $this->logExclusion('filterBotUserAgents');

                return false;
            }
        }

        // Filter by user group
        $userService = Craft::$app->getUser();
        /** @var UserElement $user */
        $user = $userService->getIdentity();
        if ($user) {
            if (InstantAnalytics::$settings->adminExclude && $user->admin) {
                $this->logExclusion('adminExclude');

                return false;
            }

            if (InstantAnalytics::$settings->groupExcludes !== null
                && is_array(InstantAnalytics::$settings->groupExcludes)) {
                foreach (InstantAnalytics::$settings->groupExcludes as $matchItem) {
                    if ($user->isInGroup($matchItem)) {
                        $this->logExclusion('groupExcludes');

                        return false;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Log the reason for excluding the sending of analytics
     *
     * @param string $setting
     */
    protected function logExclusion(string $setting)
    {
        if (InstantAnalytics::$settings->logExcludedAnalytics) {
            $request = Craft::$app->getRequest();
            $requestIp = $request->getUserIP();
            Craft::info(
                Craft::t(
                    'instant-analytics',
                    'Analytics excluded for:: {requestIp} due to: `{setting}`',
                    [
                        'requestIp' => $requestIp,
                        'setting' => $setting,
                    ]
                ),
                __METHOD__
            );
        }
    }

    /**
     * Return a sanitized documentPath from a URL
     *
     * @param $url
     *
     * @return string
     */
    protected function documentPathFromUrl($url = ''): string
    {
        if ($url === '') {
            $url = Craft::$app->getRequest()->getFullPath();
        }

        // We want to send just a path to GA for page views
        if (UrlHelper::isAbsoluteUrl($url)) {
            $urlParts = parse_url($url);
            $url = $urlParts['path'] ?? '/';
            if (isset($urlParts['query'])) {
                $url = $url . '?' . $urlParts['query'];
            }
        }

        // We don't want to send protocol-relative URLs either
        if (UrlHelper::isProtocolRelativeUrl($url)) {
            $url = substr($url, 1);
        }

        // Strip the query string if that's the global config setting
        if (InstantAnalytics::$settings) {
            if (InstantAnalytics::$settings->stripQueryString !== null
                && InstantAnalytics::$settings->stripQueryString) {
                $url = UrlHelper::stripQueryString($url);
            }
        }

        // We always want the path to be / rather than empty
        if ($url === '') {
            $url = '/';
        }

        return $url;
    }

    /**
     * Get the Google Analytics object, primed with the default values
     *
     * @return null|IAnalytics object
     */
    private function getAnalyticsObj()
    {
        $analytics = null;
        $request = Craft::$app->getRequest();
        $trackingId = InstantAnalytics::$settings->googleAnalyticsTracking;
        if (!empty($trackingId)) {
            $trackingId = Craft::parseEnv($trackingId);
        }
        if (InstantAnalytics::$settings !== null
            && !empty($trackingId)) {
            $analytics = new IAnalytics();
            if ($analytics) {
                $hostName = $request->getServerName();
                if (empty($hostName)) {
                    try {
                        $hostName = parse_url(UrlHelper::siteUrl(), PHP_URL_HOST);
                    } catch (Exception $e) {
                        Craft::error(
                            $e->getMessage(),
                            __METHOD__
                        );
                    }
                }
                $userAgent = $request->getUserAgent();
                if ($userAgent === null) {
                    $userAgent = self::DEFAULT_USER_AGENT;
                }
                $referrer = $request->getReferrer();
                if ($referrer === null) {
                    $referrer = '';
                }
                $analytics->setProtocolVersion('1')
                    ->setTrackingId($trackingId)
                    ->setIpOverride($request->getUserIP())
                    ->setUserAgentOverride($userAgent)
                    ->setDocumentHostName($hostName)
                    ->setDocumentReferrer($referrer)
                    ->setAsyncRequest(false);

                // Try to parse a clientId from an existing _ga cookie
                $clientId = $this->getClientId();
                if (!empty($clientId)) {
                    $analytics->setClientId($clientId);
                }
                // Set the gclid
                $gclid = $this->getGclid();
                if ($gclid) {
                    $analytics->setGoogleAdwordsId($gclid);
                }

                // Handle UTM parameters
                try {
                    $session = Craft::$app->getSession();
                } catch (MissingComponentException $e) {
                    // That's ok
                    $session = null;
                }
                // utm_source
                $utm_source = $request->getParam('utm_source') ?? $session->get('utm_source') ?? null;
                if (!empty($utm_source)) {
                    $analytics->setCampaignSource($utm_source);
                    if ($session) {
                        $session->set('utm_source', $utm_source);
                    }
                }
                // utm_medium
                $utm_medium = $request->getParam('utm_medium') ?? $session->get('utm_medium') ?? null;
                if (!empty($utm_medium)) {
                    $analytics->setCampaignMedium($utm_medium);
                    if ($session) {
                        $session->set('utm_medium', $utm_medium);
                    }
                }
                // utm_campaign
                $utm_campaign = $request->getParam('utm_campaign') ?? $session->get('utm_campaign') ?? null;
                if (!empty($utm_campaign)) {
                    $analytics->setCampaignName($utm_campaign);
                    if ($session) {
                        $session->set('utm_campaign', $utm_campaign);
                    }
                }
                // utm_content
                $utm_content = $request->getParam('utm_content') ?? $session->get('utm_content') ?? null;
                if (!empty($utm_content)) {
                    $analytics->setCampaignContent($utm_content);
                    if ($session) {
                        $session->set('utm_content', $utm_content);
                    }
                }

                // If SEOmatic is installed, set the affiliation as well
                if (InstantAnalytics::$seomaticPlugin && Seomatic::$settings->renderEnabled
                    && Seomatic::$plugin->metaContainers->metaSiteVars !== null) {
                    $siteName = Seomatic::$plugin->metaContainers->metaSiteVars->siteName;
                    $analytics->setAffiliation($siteName);
                }
            }
        }

        return $analytics;
    } /* -- _getAnalyticsObj */

    /**
     * _getGclid get the `gclid` and sets the 'gclid' cookie
     */
    /**
     * _getGclid get the `gclid` and sets the 'gclid' cookie
     *
     * @return string
     */
    private function getGclid(): string
    {
        $gclid = '';
        if (isset($_GET['gclid'])) {
            $gclid = $_GET['gclid'];
            if (InstantAnalytics::$settings->createGclidCookie && !empty($gclid)) {
                setcookie('gclid', $gclid, strtotime('+10 years'), '/');
            }
        }

        return $gclid;
    }

    /**
     * getClientId handles the parsing of the _ga cookie or setting it to a
     * unique identifier
     *
     * @return string the cid
     */
    public function getClientId(): string
    {
        $cid = '';
        if (isset($_COOKIE['_ga'])) {
            $parts = preg_split('[\.]', $_COOKIE['_ga'], 4);
            if ($parts !== false) {
                $cid = implode('.', array_slice($parts, 2));
            }
        } elseif (isset($_COOKIE['_ia']) && $_COOKIE['_ia'] !== '') {
            $cid = $_COOKIE['_ia'];
        } else {
            // Only generate our own unique clientId if `requireGaCookieClientId` isn't true
            if (!InstantAnalytics::$settings->requireGaCookieClientId) {
                $cid = $this->gaGenUUID();
            }
        }
        if (InstantAnalytics::$settings->createGclidCookie && !empty($cid)) {
            setcookie('_ia', $cid, strtotime('+2 years'), '/'); // Two years
        }

        return $cid;
    }

    /**
     * gaGenUUID Generate UUID v4 function - needed to generate a CID when one
     * isn't available
     *
     * @return string The generated UUID
     */
    private function gaGenUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
