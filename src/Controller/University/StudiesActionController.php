<?php 

namespace App\Controller\University;

use App\Entity\School;
use App\Entity\Recruit;
use App\Entity\Student;
use App\Entity\Studies;
use App\Form\StudiesType;
use App\Repository\RecruitRepository;
use App\Repository\StudiesRepository;
use App\Service\Recruitment\RecruitHelper;
use App\Service\UserChecker\StudentChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @Route("/studies")
 */
class StudiesActionController extends AbstractController
{
   /**
     * @Route("/study/{id}/student/{student_id}/", name="recruit", methods={"POST"})
     * @IsGranted("ROLE_STUDENT_HIRED")
     * @ParamConverter("student", options={"id" = "student_id"})
     */
    public function recruit(Studies $studies, Student $student, RecruitRepository $repository, Request $request, RecruitHelper $helper)
    {
        // check if study is available --> nombre max de place atteint

        // check if student is already hired
        if($helper->checkAgree($student) || $helper->checkConfirmed($student)) {
            $this->addFlash('error', 'Vous êtes déjà embauché ailleurs. Rendez-vous sur votre profil.');
            return  $this->redirectToRoute('studies_show_recruit', ['id' => $studies->getId(), 'from' => 'student', 'from_id' => $student->getId()]);
        }

        // check if student has already applied to current study
        if($helper->checkRecruit($studies, $student) || $helper->checkRefused($studies, $student)) {  
             $this->addFlash('error', 'Formation indisponible');
             return  $this->redirectToRoute('studies_show_recruit', ['id' => $studies->getId(), 'from' => 'student', 'from_id' => $student->getId()]);
        }

        // create entity
        $recruit = new Recruit; 
        $recruit->setHired(false);
        $recruit->setConfirmed(false);
        $recruit->setRefused(false);
        $recruit->setUnavailable(false);
        // $apply->setFinished(false);
        $recruit->setAgree(false);
        $recruit->setStudies($studies);
        $recruit->setStudent($student);

        // save 
        if($this->isCsrfTokenValid('recruit'.$student->getId(), $request->request->get('_token'))) {
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($recruit);
            $manager->flush();
        }
        else {
            throw new \Exception('Demande Invalide');
        }

        // redirect
        $this->addFlash('success', 'Candidature enregistrée !');

        return $this->redirectToRoute('studies_show_recruit', ['id' => $studies->getId(), 'from' => 'student', 'from_id' => $student->getId()]);
    }


     /**
     * @Route("/hire/{id}", name="recruit_hire", methods={"POST"})
     * @IsGranted("ROLE_SUPER_SCHOOL")
     */
    public function hire(RecruitRepository $repository, Recruit $recruit, Request $request, RecruitHelper $helper)
    {   
        // get users
        $student = $recruit->getStudent();
        $studies = $recruit->getstudies();

        // check if student is available
        if($helper->checkAgree($student) || $helper->checkConfirmed($student)) {
            $this->addFlash('error', 'Cet étudiant n\'est plus disponible.');
            return $this->redirectToRoute('school_studies_show', ['id' => $studies->getId(), 'school_id' => $studies->getSchool()->getId()]);
        }
   
        // close offer 
        // $offers->setState(true);                                                                         for features 

        // prevent student from applying 
        // $student->getUser()->setRoles(['ROLE_SUPER_STUDENT']);                                           ?

                                                                                                            // unavailables not now
        // send notification to student 
        // $email = $apply->getStudent()->getUser()->getEmail();
        // $name = $apply->getStudent()->getName();
        // $offerTitle = $apply->getOffers()->getTitle(); 
        
        // $mailer->sendHireMessage($email, $name, $offerTitle);
        
        $helper->hire($recruit);

        if($this->isCsrfTokenValid('hire'.$recruit->getId(), $request->request->get('_token'))) {           // not usefull to delete others 

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();
        }
        else {
            throw new \Exception('Candidature Invalide');
        }

        $this->addFlash('success', 'Elève recruté !');
 
        return $this->redirectToRoute('school_studies_show', ['id' => $studies->getId(), 'school_id' => $studies->getSchool()->getId()]);
    }

    /**
     * @Route("/agree/{id}", name="recruit_agree", methods={"POST"})
     * @IsGranted("ROLE_SUPER_STUDENT")
     */
    public function agree(RecruitRepository $repository, Recruit $recruit, Request $request, RecruitHelper $helper)
    {
        // get other applies
        $student = $recruit->getStudent();
        $studies = $recruit->getStudies();

        // agree
        $helper->agree($recruit);

        // set to unavailable
        $helper->unavailables($studies, $student);

        // send notification to student 
        // $email = $student->getUser()->getEmail();
        // $name = $student->getName();
        // $offerTitle = $offers->getTitle(); 
        
        // $mailer->sendAgreeMessage($email, $name, $offerTitle); 

        if($this->isCsrfTokenValid('agree'.$recruit->getId(), $request->request->get('_token'))) {
            $this->getDoctrine()->getManager()->flush();
        }
        else {
            throw new \Exception('Demande Invalide');
        }

        $this->addFlash('success', 'Cursus accepté !');

        return $this->redirectToRoute('school_student_index', ['id' => $student->getId()]);
    }

    /**
     * @Route("/confirm/{id}", name="recruit_confirm", methods={"POST"})
     * @IsGranted("ROLE_SUPER_SCHOOL")
     */
    public function confirm(RecruitRepository $repository, Recruit $recruit, Request $request, RecruitHelper $helper)
    {
        // get entites
        $student = $recruit->getStudent();
        $studies = $recruit->getStudies();

        // confirm
        $helper->confirm($recruit);

         // send notification to student 
        //  $email = $student->getUser()->getEmail();
        //  $name = $student->getName();
        //  $offerTitle = $offers->getTitle(); 
         
        //  $mailer->sendConfirmMessage($email, $name, $offerTitle); 

        // $student->getUser()->setRoles(['ROLE_STUDENT_HIRED']);

        if($this->isCsrfTokenValid('confirm'.$recruit->getId(), $request->request->get('_token'))) {

            $this->getDoctrine()->getManager()->flush();
        }
        else {
            throw new \Exception('Demande Invalide');
        }

        $this->addFlash('success', 'Mission Commencée. Bon travail !');

        return $this->redirectToRoute('school_studies_show', ['id' => $studies->getId(), 'school_id' => $studies->getSchool()->getId()]);
    }

    /**
     * @Route("/refuse/{id}", name="recruit_refuse", methods={"POST"})
     * @IsGranted("ROLE_SUPER_SCHOOL")
     */
    public function refuse(RecruitRepository $repository, Recruit $recruit, Request $request, RecruitHelper $helper)
    {
        // get entities
        $student = $recruit->getStudent();
        $studies = $recruit->getStudies();

        if($recruit->getRefused() == true) {
            $this->addFlash('error', 'Vous avez déjà refusé cette candidature');
            return $this->redirectToRoute('school_studies_show', ['id' => $studies->getId(), 'school_id' => $studies->getSchool()->getId()]);
        }

        // refuse
        $helper->refuse($recruit);

        // set roles not usefull

        // close offer 
        // $offers->setState(false);

        // send notification to student 
        // $email = $student->getUser()->getEmail();
        // $name = $student->getName();
        // $offerTitle = $offers->getTitle(); 
           
        // $mailer->sendRefuseMessage($email, $name, $offerTitle); 
  
        // set to availables not usefull because cannot refuse after agree

        if($this->isCsrfTokenValid('refuse'.$recruit->getId(), $request->request->get('_token'))) {
            $this->getDoctrine()->getManager()->flush();
        }
        else {
            throw new \Exception('Demande Invalide');
        }

        $this->addFlash('success', 'Candidature refusée');
    
        return $this->redirectToRoute('school_studies_show', ['id' => $studies->getId(), 'school_id' => $studies->getSchool()->getId()]);
    }

    /**
     * @Route("/delete/recruit/{id}", name="delete_recruit", methods={"DELETE"})
     * @IsGranted("ROLE_SUPER_STUDENT")
     * @ParamConverter("recruit", options={"id" = "id"})
     */
    public function recruitDelete(Recruit $recruit, Request $request, RecruitRepository $repository, StudentChecker $checker): Response
    {
        // set role not usefull 

        $student = $recruit->getStudent();
        $studies = $recruit->getStudies();

        // close offer 
        // $offers->setState(false);

        // set to available not usefull because cannot delete after agree

        // send mail 
        // $email = $user->getEmail();
        // $name = $apply->getStudent()->getName();
        // $offerTitle = $apply->getOffers()->getTitle();

        // $mailer->sendDeleteMessage($email, $name, $offerTitle); 
    
        // delete apply 
        if ($this->isCsrfTokenValid('delete'.$recruit->getId(), $request->request->get('_token'))) {

            $entityManager = $this->getDoctrine()->getManager();
            // delete relation
            $entityManager->remove($recruit);
            // delete offer
            $entityManager->flush();
        }
        else {
            throw new \Exception('Demande Invalide');
        }

        $this->addFlash('success', 'Postulation supprimée !');

        return $this->redirectToRoute('school_student_index', ['id' => $student->getId()]);
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