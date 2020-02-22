<?php

namespace App\Controller\Company;

use App\Entity\Company;
use App\Form\CompanyType;
use App\Repository\ApplyRepository;
use App\Service\Mailer\ApplyMailer;
use App\Repository\CompanyRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/company")
 */
class CompanyController extends AbstractController
{
    /**
     * @Route("/", name="company_index", methods={"GET"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(CompanyRepository $companyRepository, PaginatorInterface $paginator, Request $request): Response
    {   
        $queryBuilder = $companyRepository->findAllPaginated("DESC");

        $pagination = $paginator->paginate(
            $queryBuilder, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            10/*limit per page*/
        );

        return $this->render('company/index.html.twig', [
            'companies' => $pagination,
        ]);
    }

    /**
     * @Route("/new", name="company_new", methods={"GET","POST"})
     */
    public function new(UserPasswordEncoderInterface $passwordEncoder, Request $request): Response
    {
        $company = new Company();
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // set roles 
            $user = $company->getUser();
            $user->setRoles(['ROLE_COMPANY']);
            $user->setPassword($passwordEncoder->encodePassword(
                $user,
                $user->getPassword()
            ));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($company);
            $entityManager->flush();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('company/new.html.twig', [
            'company' => $company,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="company_show", methods={"GET"})
     * @IsGranted("ROLE_COMPANY")
     */
    public function show(Company $company): Response
    {
        return $this->render('company/show.html.twig', [
            'company' => $company,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="company_edit", methods={"GET","POST"})
     * @IsGranted("ROLE_COMPANY")
     */
    public function edit(Request $request, Company $company): Response
    {
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Mise à jour réussie');

            return $this->redirectToRoute('company_edit', ['id' => $company->getId() ]);
        }

        return $this->render('company/edit.html.twig', [
            'company' => $company,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/{from}", name="company_delete", methods={"DELETE"})
     * @IsGranted("ROLE_COMPANY")
     */
    public function delete(Request $request, Company $company, ApplyRepository $repository, ApplyMailer $mailer, $from): Response
    {
        if ($this->isCsrfTokenValid('delete'.$company->getId(), $request->request->get('_token'))) {

            $entityManager = $this->getDoctrine()->getManager();

            $offers = $company->getOffers();

            // remove applies 
            foreach($offers as $offers) {

                $applies = $offers->getApplies();

                foreach($applies as $applies) {

                    $student = $applies->getStudent();

                    // set roles 
                    $student->getUser()->setRoles([
                        "ROLE_SUPER_COMPANY"
                    ]);

                    // send mail 
                    $email = $student->getUser()->getEmail();
                    $name = $student->getName();
                    $offerTitle = $offers->getTitle();

                    $mailer->sendDeleteCompanyMessage($email, $name, $offerTitle); 

                    if($applies->getFinished() == false) {
                        $entityManager->remove($applies);
                    }
                    else {
                        $applies->setOffers(NULL);
                    } 
                }
                // remove offers 
                $entityManager->remove($offers);
            }

            $entityManager->remove($company);
            $entityManager->flush();
        }

        $this->addFlash('success', 'Compte Supprimé');

        return $this->redirectToRoute($from);
    }
}
