<?php

namespace App\Controller\BackOffice;

use App\Entity\Company;
use App\Entity\Feature;
use App\Entity\FeatureState;
use App\Form\Feature\AddEditType;
use App\Form\Feature\ListFilterType;
use App\Form\Feature\RoadmapFilterType;
use App\FormRequest\Feature\ListFilterRequest;
use App\FormRequest\Feature\AddEditRequest;
use App\FormRequest\Feature\RoadmapFilterRequest;
use App\Handler\Feature\Add;
use App\Handler\Feature\Delete;
use App\Handler\Feature\Edit;
use App\Handler\Feature\MoveState;
use App\Handler\Feature\Search;
use App\View\BackOffice\Feature\DetailView;
use App\View\BackOffice\Feature\FilterFormView;
use App\View\BackOffice\Feature\ListView;
use App\View\BackOffice\Feature\RoadmapView;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;


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
     * @Route("/admin/{slug}/feature/add", name="bo_feature_add")
     * @param Company $company
     * @param Request $request
     * @param Add $handler
     * @return Response
     */
    public function add(Company $company, Request $request, Add $handler): Response
    {
        $this->denyAccessUnlessGranted('edit', $company);

        $form = $this->createForm(AddEditType::class, $formRequest = new AddEditRequest(), [
            'tags' => $company->getFeatureTags(),
            'states' => $this->manager->getRepository(FeatureState::class)->findAll()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $feature = $handler->handle($formRequest, $company);

            $this->addFlash('success', 'Feature added.');

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
     * @Route("/admin/{company_slug}/feature/{feature_id}/edit", name="bo_feature_edit")
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

        $form = $this->createForm(AddEditType::class, $formRequest = AddEditRequest::fromFeature($feature), [
            'tags' => $company->getFeatureTags(),
            'states' => $this->manager->getRepository(FeatureState::class)->findAll()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $handler->handle($formRequest, $feature);

            $this->addFlash('success', 'Feature edited.');

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
     * @Route("/admin/{slug}/features/list/{state_slug?}", name="bo_feature_list")
     * @ParamConverter("company", options={"mapping": {"slug": "slug"}})
     * @ParamConverter("state", options={"mapping": {"state_slug": "slug"}})
     * @param Company $company
     * @param FeatureState $state
     * @param ListView $view
     * @param Request $request
     * @param FilterFormView $formView
     * @param Search $handler
     * @return Response
     */
    public function list(
        Company $company,
        ?FeatureState $state,
        ListView $view,
        Request $request,
        FilterFormView $formView,
        Search $handler)
    {
        $this->denyAccessUnlessGranted('edit', $company);


        $form = $this->createForm(ListFilterType::class, $formRequest = ListFilterRequest::fromArray([
            'state' => $state ? $state->getId() : null,
            'fulltext' => $fulltext = $request->get('fulltext'),
            'tags' => $tagsParam = $request->get('tags')
        ]), [
            'stateChoices' => $formView->createStates(),
            'tagChoices' => $formView->createTags($company),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            return new RedirectResponse(
                $handler->handleList($company, $formRequest)
            );

        }

        return $this->render('back_office/feature/list.html.twig',
            $view->create(
                $company,
                $form->createView(),
                $state,
                $tagsParam,
                $fulltext
            ));
    }

    /**
     * @Route("/admin/{slug}/features/roadmap", name="bo_feature_list_roadmap")
     * @ParamConverter("company", options={"mapping": {"slug": "slug"}})
     * @param Company $company
     * @param RoadmapView $view
     * @param FilterFormView $formView
     * @param Request $request
     * @param Search $handler
     * @return Response
     */
    public function roadmap(
        Company $company,
        RoadmapView $view,
        FilterFormView $formView,
        Request $request,
        Search $handler)
    {
        $this->denyAccessUnlessGranted('edit', $company);

        $form = $this->createForm(RoadmapFilterType::class, $formRequest = RoadmapFilterRequest::fromArray([
            'fulltext' => $fulltext = $request->get('fulltext'),
            'tags' => $tagsParam = $request->get('tags')
        ]), [
            'tagChoices' => $formView->createTags($company)
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            return new RedirectResponse(
                $handler->handleRoadmap($company, $formRequest)
            );

        }

        return $this->render('back_office/feature/roadmap.html.twig',
            $view->create(
                $company,
                $form->createView(),
                $tagsParam,
                $fulltext,
                 $request->get('id') ? (int)$request->get('id') : null
            )
        );
    }

    /**
     * @Route("/admin/{company_slug}/feature/{feature_id}/delete", name="bo_feature_delete", methods={"POST"})
     * @ParamConverter("company", options={"mapping": {"company_slug": "slug"}})
     * @ParamConverter("feature", options={"mapping": {"feature_id": "id"}})
     * @param Company $company
     * @param Feature $feature
     * @param Delete $handler
     * @param Request $request
     * @return RedirectResponse
     */
    public function delete(Company $company, Feature $feature, Delete $handler, Request $request)
    {
        $this->denyAccessUnlessGranted('edit', $feature);

        if ($this->isCsrfTokenValid('delete-item', $request->request->get('token'))) {

            $handler->handle($feature);

            $this->addFlash('success', 'Feature deleted.');

        }

        return $this->redirectToRoute('bo_feature_list', [
            'slug' => $company->getSlug()
        ]);
    }

    /**
     * @Route("/admin/{company_slug}/feature/{feature_id}/description", name="bo_feature_detail")
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
     * @Route("/admin/{company_slug}/feature/{feature_id}/move-status/{direction}", name="bo_feature_status_move")
     * @ParamConverter("company", options={"mapping": {"company_slug": "slug"}})
     * @ParamConverter("feature", options={"mapping": {"feature_id": "id"}})
     * @param Company $company
     * @param Feature $feature
     * @param $direction
     * @param MoveState $handler
     * @param Request $request
     * @return Response|NotFoundHttpException
     */
    public function moveStatus(Company $company, Feature $feature, $direction, MoveState $handler, Request $request)
    {
        $this->denyAccessUnlessGranted('edit', $feature);

        if (! in_array($direction, RoadmapView::getDirectionsSlugs() )){
            throw new NotFoundHttpException();
        }

        $handler->handle($feature, $direction);

        $this->addFlash('success', 'Feature state updated.');

        return $this->redirectToRoute('bo_feature_list_roadmap', [
            'slug' => $company->getSlug(),
            'tags' => $request->get('tags'),
            'fulltext' => $request->get('fulltext'),
            'id' => $feature->getId(),
            '_fragment' => RoadmapView::$scrollTo
        ]);
    }

}
