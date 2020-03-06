<?php

namespace App\Controller\University;

use App\Entity\School;
use App\Entity\Studies;
use App\Form\StudiesType;
use App\Repository\StudiesRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @Route("/studies")
 * @IsGranted("ROLE_SUPER_ADMIN")
 */
class StudiesRenderController extends AbstractController
{
    // /**
    //  * @Route("/", name="studies_index", methods={"GET"})
    //  */
    // public function index(StudiesRepository $studiesRepository): Response
    // {
    //     return $this->render('studies/index-student.html.twig');
    // }

    /**
     * @Route("/candidate/{from}/{id}", name="studies_candidate_index", methods={"GET"})
     */
    public function indexCandidate(StudiesRepository $studiesRepository, PaginatorInterface $paginator, Request $request, $from, $id): Response
    {
        $queryBuilder = $studiesRepository->findAll();

        $pagination = $paginator->paginate(
            $queryBuilder, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            6/*limit per page*/
        );

        return $this->render('studies/index-student.html.twig', [
            'studies' => $pagination,
            'from' => $from,
            'id' => $id
        ]);
    } 

    /**
    * @Route("/show/recruit/{id}/{from}/{from_id}", name="studies_show_recruit", methods={"GET"})
    */
    public function showRecruit(Studies $study, $from, $from_id) 
    {
        return $this->render('studies/show-recruit.html.twig', [
            'study' => $study,
            'from' => $from,
            'from_id' => $from_id
        ]);
    }

    /**
     * @Route("/new/{school}", name="studies_new", methods={"GET","POST"})
     */
    public function new(Request $request, School $school): Response
    {
        $study = new Studies();
        $form = $this->createForm(StudiesType::class, $study);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $study = $form->getData();

            $study->setSchool($school);

            // dd($study);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($study);
            $entityManager->flush();

            return $this->redirectToRoute('school_studies_index', [ 'id' => $school->getId() ]);
        }

        return $this->render('studies/new.html.twig', [
            'study' => $study,
            'form' => $form->createView(),
            'school' => $school
        ]);
    }

    // /**
    //  * @Route("/{id}", name="studies_show", methods={"GET"})
    //  */
    // public function show(Studies $study): Response
    // {
    //     return $this->render('studies/show.html.twig', [
    //         'study' => $study,
    //     ]);
    // }

    /**
     * @Route("/{id}/edit/{school_id}", name="studies_edit", methods={"GET","POST"})
     * @ParamConverter("school", options={"id" = "school_id"})
     */
    public function edit(Request $request, Studies $study, School $school): Response
    {
        $form = $this->createForm(StudiesType::class, $study);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            // return $this->redirectToRoute('school_studies_index', [ 'id' => $school->getId() ]);
        }

        return $this->render('studies/edit.html.twig', [
            'study' => $study,
            'form' => $form->createView(),
            'school' => $school
        ]);
    }

    /**
     * @Route("/{id}/{school_id}", name="studies_delete", methods={"DELETE"})
     * @ParamConverter("school", options={"id" = "school_id"})
     */
    public function delete(Request $request, Studies $study, School $school): Response
    {
        if ($this->isCsrfTokenValid('delete'.$study->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($study);
            $entityManager->flush();
        }

        return $this->redirectToRoute('school_studies_index', [ 'id' => $school->getId() ]);
    }
}
