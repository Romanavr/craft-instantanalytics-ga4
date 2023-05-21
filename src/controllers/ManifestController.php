<?php
/**
 * Instant Analytics plugin for Craft CMS
 *
 * Instant Analytics brings full Google Analytics support to your Twig templates
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2017 nystudio107
 */

namespace nystudio107\instantanalyticsGa4\controllers;

use Craft;
use craft\web\Controller;
use yii\web\Response;

/**
 * @author    nystudio107
 * @package   InstantAnalytics
 * @since     1.0.0
 */
class ManifestController extends Controller
{
    // Constants
    // =========================================================================

    // Protected Properties
    // =========================================================================

    /**
     * @inerhitdoc
     */
    protected $allowAnonymous = [
        'resource'
    ];

    // Public Methods
    // =========================================================================

    /**
     * Make webpack async bundle loading work out of published AssetBundles
     *
     * @param string $resourceType
     * @param string $fileName
     *
     * @return Response
     */
    public function actionResource(string $resourceType = '', string $fileName = ''): Response
    {
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@nystudio107/instantanalyticsGa4/assetbundles/instantanalyticsGa4/dist',
            true
        );
        $url = "{$baseAssetsUrl}/{$resourceType}/{$fileName}";

        return $this->redirect($url);
    }
}
