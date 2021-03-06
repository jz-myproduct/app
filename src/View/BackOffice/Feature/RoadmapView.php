<?php


namespace App\View\BackOffice\Feature;


use App\Entity\Company;
use App\Entity\Feature;
use App\Entity\FeatureState;
use App\Entity\FeatureTag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormView;

class RoadmapView
{

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    public static $scrollTo = 'roadmapScroll';

    public static $previousDirection =
        [
            'int' => -1,
            'slug' => 'predchozi'
        ];

    public static $nextDirection =
        [
            'int' => 1,
            'slug' => 'nasledujici'
        ];

    public static function getDirectionsSlugs()
    {
        return [
          self::$previousDirection['slug'],
          self::$nextDirection['slug']
        ];
    }

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function create(
        Company $company,
        FormView $form,
        $tagsParam = [],
        String $fulltext = null,
        Int $movedFeature = null)
    {
        $states = $this->manager->getRepository(FeatureState::class)->findAll();

        return [
            'features' => $this->prepareFeaturesData($company, $states, $tagsParam, $fulltext),
            'columnWidth' => $this->prepareColumnWidth($states),
            'form' => $form,
            'tagsExist' => $company->getFeatureTags()->toArray() ? true : false,
            'tags' => $tagsParam,
            'fulltext' => $fulltext,
            'isFiltered' => is_null($tagsParam) && is_null($fulltext) ? false : true,
            'scrollTo' => self::$scrollTo,
            'previousDirection' => self::$previousDirection['slug'],
            'nextDirection' => self::$nextDirection['slug'],
            'movedFeatureId' => $movedFeature
        ];
    }

    private function prepareFeaturesData(Company $company, $states, $tagsParam = [], String $fulltext = null)
    {
        $tags = $this->manager->getRepository(FeatureTag::class)
            ->findBy( ['id' => $tagsParam ] );

        $features = array();

        foreach($states as $featureState)
        {
            $features[] = [

                'state' => $featureState->getName(),
                'stateColor' => $featureState->getColor(),
                'features' =>
                    $this->manager->getRepository(Feature::class)
                        ->findCompanyFeaturesByTagAndState($tags, $company, $featureState, $fulltext),
                'isFirst' =>
                    $featureState === $this->manager->getRepository(FeatureState::class)->findInitialState() ?
                        true : false,
                'isLast' =>
                    $featureState === $this->manager->getRepository(FeatureState::class)->findLastState() ?
                        true : false
            ];
        }

        return $features;
    }

    private function prepareColumnWidth($states)
    {
        if($states)
        {
            return round(100 / count($states));
        }

        return 100;
    }

}