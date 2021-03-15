<?php

namespace App\Controller\BackOffice;

use App\Entity\Company;
use App\Entity\Feature;
use App\Entity\FeatureState;
use App\Entity\Feedback;
use App\Entity\Insight;
use App\Entity\InsightWeight;
use App\Entity\PortalFeature;
use App\Events\FeedbackUpdatedEvent;
use App\Form\FeatureFormType;
use App\Form\FeedbackFeatureDetailFormType;
use App\Form\InsightOnFeatureFormType;
use App\Form\PortalFeatureFormType;
use App\Handler\Feature\Add;
use App\Handler\Feature\Delete;
use App\Handler\Feature\Edit;
use App\Handler\Feature\MoveState;
use App\Handler\Insight\AddFromFeature;
use App\Services\SlugService;
use App\View\BackOffice\Feature\DetailView;
use App\View\BackOffice\Feature\FeedbackListView;
use App\View\BackOffice\Feature\ListView;
use App\View\BackOffice\Feature\RoadmapView;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class FeatureController extends AbstractController
{

    /**
     * @var EntityManager
     */
    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }


    /**
     * @Route("/admin/{slug}/feature/pridat", name="bo_feature_add")
     * @param Company $company
     * @param Request $request
     * @param Add $handler
     * @return Response
     */
    public function add(Company $company, Request $request, Add $handler): Response
    {
        $this->denyAccessUnlessGranted('edit', $company);

        $form = $this->createForm(FeatureFormType::class, $feature = new Feature(), [
            'tags' => $company->getFeatureTags()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $handler->handle($feature, $company);

            $this->addFlash('success', 'Feature přidána.');

            return $this->redirectToRoute('bo_feature_list', [
                'slug' => $company->getSlug()
            ]);
        }

        return $this->render('back_office/feature/add_edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/admin/{company_slug}/feature/{feature_id}/upravit", name="bo_feature_edit")
     * @ParamConverter("company", options={"mapping": {"company_slug": "slug"}})
     * @ParamConverter("feature", options={"mapping": {"feature_id": "id"}})
     * @param Company $company
     * @param Feature $feature
     * @param Request $request
     * @param Edit $handler
     * @return Response
     */
    public function edit(Company $company, Feature $feature, Request $request, Edit $handler)
    {

        $this->denyAccessUnlessGranted('edit', $feature);

        $form = $this->createForm(FeatureFormType::class, $feature, [
            'tags' => $company->getFeatureTags()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $handler->handle($feature);

            $this->addFlash('success', 'Feature upravena.');

            return $this->redirectToRoute('bo_feature_detail', [
                'company_slug' => $company->getSlug(),
                'feature_id' => $feature->getId()
            ]);
        }

        return $this->render('back_office/feature/add_edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/admin/{slug}/features/{state_slug?}", name="bo_feature_list")
     * @ParamConverter("company", options={"mapping": {"slug": "slug"}})
     * @ParamConverter("state", options={"mapping": {"state_slug": "slug"}})
     * @param Company $company
     * @param FeatureState $state
     * @param ListView $view
     * @return Response
     */
    public function list(Company $company, ?FeatureState $state, ListView $view)
    {
        $this->denyAccessUnlessGranted('edit', $company);
        
        return $this->render('back_office/feature/list.html.twig', $view->create($company, $state));
    }

    /**
     * @Route("/admin/{company_slug}/feature/{feature_id}/smazat", name="bo_feature_delete")
     * @ParamConverter("company", options={"mapping": {"company_slug": "slug"}})
     * @ParamConverter("feature", options={"mapping": {"feature_id": "id"}})
     * @param Company $company
     * @param Feature $feature
     * @param Delete $handler
     * @return RedirectResponse
     */
    public function delete(Company $company, Feature $feature, Delete $handler)
    {
        $this->denyAccessUnlessGranted('edit', $feature);

        $handler->handle($feature);

        $this->addFlash('success', 'Feature smazána.');

        return $this->redirectToRoute('bo_feature_list', [
            'slug' => $company->getSlug()
        ]);
    }

    /**
     * @Route("/admin/{company_slug}/feature/{feature_id}/detail", name="bo_feature_detail")
     * @ParamConverter("company", options={"mapping": {"company_slug": "slug"}})
     * @ParamConverter("feature", options={"mapping": {"feature_id": "id"}})
     * @param Company $company
     * @param Feature $feature
     * @param DetailView $view
     * @return Response
     */
    public function detail(
        Company $company,
        Feature $feature,
        DetailView $view)
    {
        $this->denyAccessUnlessGranted('edit', $feature);

        return $this->render('back_office/feature/detail.html.twig', $view->create($feature));
    }

    /**
     * @Route("/admin/{company_slug}/feature/{feature_id}/feedback", name="bo_feature_feedback")
     * @ParamConverter("company", options={"mapping": {"company_slug": "slug"}})
     * @ParamConverter("feature", options={"mapping": {"feature_id": "id"}})
     * @param Company $company
     * @param Feature $feature
     * @param Request $request
     * @param AddFromFeature $handler
     * @param FeedbackListView $view
     * @return RedirectResponse|Response
     */
    public function feedback(
        Company $company,
        Feature $feature,
        Request $request,
        AddFromFeature $handler,
        FeedbackListView $view)
    {
        $this->denyAccessUnlessGranted('edit', $feature);

        $form = $this->createForm(InsightOnFeatureFormType::class, $insight = new Insight());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $handler->handle($insight, $feature);

            $this->addFlash('success', 'Feedback přidán');

            return $this->redirectToRoute('bo_feature_feedback', [
                'company_slug' => $company->getSlug(),
                'feature_id' => $feature->getId(),
            ]);
        }

        return $this->render(
            'back_office/feature/feedback.html.twig',
            $view->create($feature, $form->createView()));
    }

    /**
     * @Route("/admin/{slug}/features/roadmap", name="bo_feature_list_roadmap")
     * @param Company $company
     * @param RoadmapView $view
     * @return Response
     */
    public function roadmapView(Company $company, RoadmapView $view)
    {
        $this->denyAccessUnlessGranted('edit', $company);

        return $this->render('back_office/feature/roadmap.html.twig', $view->create($company));
    }

    /**
     * @Route("/admin/{company_slug}/feature/{feature_id}/posunout-status/{direction}", name="bo_feature_status_move")
     * @ParamConverter("company", options={"mapping": {"company_slug": "slug"}})
     * @ParamConverter("feature", options={"mapping": {"feature_id": "id"}})
     * @param Company $company
     * @param Feature $feature
     * @param $direction
     * @param MoveState $handler
     * @return Response|NotFoundHttpException
     */
    public function moveStatus(Company $company, Feature $feature, $direction, MoveState $handler)
    {
        $this->denyAccessUnlessGranted('edit', $feature);

        if (! in_array($direction, FeatureState::getDirectionSlugs() )){
            throw new NotFoundHttpException();
        }

        $handler->handle($feature, $direction);

        return $this->redirectToRoute('bo_feature_list_roadmap', [
            'slug' => $company->getSlug()
        ]);
    }


    
}
