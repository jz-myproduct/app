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

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function handle(
        String $param,
        Insight $insight,
        Company $company)
    {

        // TODO ty stringy by měli být uloženy někde jako konstanty (ViewClass)

        if ($param === 'feature') {

            return $this->router->generate('bo_feature_feedback', [
                'feature_id' => $insight->getFeature()->getId(),
                'company_slug' => $company->getSlug()
            ]);

        }

        if ($param === 'feedback') {

            return $this->router->generate('bo_feedback_features', [
                'feedback_id' => $insight->getFeedback()->getId(),
                'company_slug' => $company->getSlug()
            ]);
        }

        return $this->router->generate('bo_home', [
            'slug' => $company->getSlug()
        ]);

    }

}