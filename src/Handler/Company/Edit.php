<?php


namespace App\Handler\Company;


use App\Entity\Company;
use App\FormRequest\Settings\InfoRequest;
use App\Services\SlugService;
use Doctrine\ORM\EntityManagerInterface;

class Edit
{
    /**
     * @var EntityManagerInterface
     */
    private $manager;
    /**
     * @var SlugService
     */
    private $slugService;


    public function __construct(EntityManagerInterface $manager, SlugService $slugService)
    {
        $this->slugService = $slugService;
        $this->manager = $manager;
    }

    public function handle(InfoRequest $request, Company $company)
    {
        $company->setName($request->name);
        $company->setSlug(
            $this->slugService->createCompanySlug($request->name)
        );
        $company->setEmail($request->username);

        $this->manager->flush();

        return $company;
    }

}