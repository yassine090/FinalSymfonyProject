<?php

namespace App\Controller;

use App\Entity\Contact;


use App\Form\ContactType;



use Knp\Component\Pager\PaginatorInterface;
use phpDocumentor\Reflection\DocBlock\Serializer;
use Swift_Mailer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\Formulaire;
use App\Form\FormulaireType;
use App\Repository\FormulaireRepository;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/form")
 */
class FormController extends AbstractController
{
    /**
     * @Route("/", name="form_index")
     */
    public function index(FormulaireRepository $formulaireRepository)
    {
        $em = $this->getDoctrine()->getManager();
        $formulaire = $em->getRepository(Formulaire::class)->findAll();


        //////////////////////////
        ///
        ///

        $a=$formulaireRepository->filter("Homme");
        $i=0;
        foreach ($a as $row){
            $i++;
        }
        $b=$formulaireRepository->filter("Femme");
        $j=0;
        foreach ($b as $row){
            $j++;
        }


        $pieChart = new PieChart();
        $pieChart->getData()->setArrayToDataTable(
            [
                ['Sexe', 'Son type'],
                ['Homme', $i],
                ['Femme', $j]
            ]
        );
        $pieChart->getOptions()->setPieSliceText('label');
        $pieChart->getOptions()->setTitle('un aperçu du type de notre contenu');
        $pieChart->getOptions()->setPieStartAngle(100);
        $pieChart->getOptions()->setHeight(500);
        $pieChart->getOptions()->setWidth(900);
        $pieChart->getOptions()->getLegend()->setPosition('none');


        /// /////////////////////
        ///
        ///

        return $this->render('form/index.html.twig', [
            'formulaires' => $formulaire,
            'piechart' => $pieChart,
        ]);
    }












    /**
     * @Route("/new", name="form_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $formulaire = new Formulaire();
        $form = $this->createForm(FormulaireType::class, $formulaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($formulaire);
            $entityManager->flush();

            return $this->redirectToRoute('salutation', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('form/new.html.twig', [
            'formulaire' => $formulaire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="form_show", methods={"GET"})
     */
    public function show(Formulaire $formulaire): Response
    {
        return $this->render('form/show.html.twig', [
            'formulaire' => $formulaire,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="form_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Formulaire $formulaire): Response
    {
        $form = $this->createForm(FormulaireType::class, $formulaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('form_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('form/edit.html.twig', [
            'formulaire' => $formulaire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="form_delete", methods={"POST"})
     */
    public function delete(Request $request, Formulaire $formulaire): Response
    {
        if ($this->isCsrfTokenValid('delete'.$formulaire->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($formulaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('form_index', [], Response::HTTP_SEE_OTHER);
    }



////////////////////////  RESTFUL API Serialization des donnees //////////////
    /**
     * @Route ("/deleteFormulaireJSON/{id}", name="deleteStudentJSON")
     * @Method ("POST")
     */
    public function deleteFormulaireJSON(Request $request, NormalizerInterface $normalizer,$id)
    {
        $em=$this->getDoctrine()->getManager();
        $formulaire=$em->getRepository(Formulaire::class)->find($id);
        $em->remove($formulaire);
        $em->flush();
        $jsonContent=$normalizer->normalize($formulaire,'json',
            ['groups'=>'show_form']);
        return new Response("Successfully deleted!".json_encode($jsonContent));
    }
    /**
     * @Route ("/updateJSON/{id}", name="updateFormJSON")
     * @Method ("POST")
     */
    public function updateFormulaireJSON(Request $request, NormalizerInterface $normalizer,$id)
    {
        $dateCreation = new \DateTime('now');

        $em=$this->getDoctrine()->getManager();
        $formulaire=$em->getRepository(Formulaire::class)->find($id);
        $formulaire->setLibelle($request->get('libelle'));
        $formulaire->setDescription($request->get('discription'));
        $formulaire->setDateCreation($dateCreation);
        $formulaire->setType($request->get('type'));
        $formulaire->setImage($request->get('image'));
        $em->flush();
        $jsonContent=$normalizer->normalize($formulaire,'json',
            ['groups'=>'show_form']);
        return new Response("Successfully updated!".json_encode($jsonContent));
    }
    /**
     * @Route("/addJSON", name="addFormJSON")
     * @Method ("POST")
     */
    public function addFormJSON(Request $request, NormalizerInterface $normalizer)
    {
        $em=$this->getDoctrine()->getManager();
        $formulaire=new Formulaire();
        $dateCreation = new \DateTime('now');

        $formulaire->setLibelle($request->get('libelle'));
        $formulaire->setDescription($request->get('description'));
        $formulaire->setDateCreation($dateCreation);
        $formulaire->setType($request->get('type'));
        $formulaire->setImage($request->get('image'));
        $em->persist($formulaire);
        $em->flush();
        $jsonContent=$normalizer->normalize($formulaire, 'json',
            ['groups'=>'show_form']);
        return new Response(json_encode($jsonContent));

    }

    /**
     * @Route("/all", name="all_for")
     */
    public function AllFormulaire(NormalizerInterface $normalizer): Response
    {
        $repository=$this->getDoctrine()->getRepository(Formulaire::class);
        $formulaire=$repository->findAll();
        $jsonContent=$normalizer->normalize($formulaire,'json',['groups'=>'show_form']);

        return new Response(json_encode($jsonContent));
    }




}
