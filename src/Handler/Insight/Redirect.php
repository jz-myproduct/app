<?php


namespace App\Handler\Insight;


use App\Entity\Company;
use App\Entity\Insight;
use Symfony\Component\Routing\RouterInterface;

class Redirect
{
    /**
     * @var RouterInterface
     */
    private $router;

    private static $redirectToFeature = 'feature';

    private static $redirectToFeedback = 'feedback';

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function handle(
        Insight $insight,
        Company $company,
        String $param = null)
    {

        if ($param === self::$redirectToFeature) {

            return $this->router->generate('bo_insight_feature_list', [
                'feature_id' => $insight->getFeature()->getId(),
                'company_slug' => $company->getSlug(),
            ]);

        }

        if ($param === self::$redirectToFeedback) {

            return $this->router->generate('bo_insight_feedback_list', [
                'feedback_id' => $insight->getFeedback()->getId(),
                'company_slug' => $company->getSlug()
            ]);
        }

        return $this->router->generate('bo_feedback_list', [
            'slug' => $company->getSlug(),
        ]);

    }

    public static function getRedirectToFeature()
    {
        return self::$redirectToFeature;
    }

    public static function getRedirectToFeedback()
    {
        return self::$redirectToFeedback;
    }

}