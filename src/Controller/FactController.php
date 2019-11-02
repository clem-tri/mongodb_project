<?php


namespace App\Controller;


use App\Document\FactOfTheDay;
use App\Document\User;
use App\Form\Type\FactType;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;
use Symfony\Component\HttpFoundation\JsonResponse;

class FactController extends AbstractController
{
    /**
     * @Route("/", name="home", methods={"GET"})
     */
    public function list(DocumentManager $dm)
    {
        $repository = $dm->getRepository(FactOfTheDay::class);

        $facts = $repository->findBy([], ['created_at' => 'DESC']);

        return $this->render('home.html.twig',
            ["facts" => $facts]);

    }


    /**
     * @Route("/new", name="newFact", methods={"GET", "POST"})
     */
    public function add(Request $request,DocumentManager $documentManager)
    {
        $fact = new FactOfTheDay();

        $form = $this->createForm(FactType::class, $fact);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            /** @var FactOfTheDay $fact */
            $fact = $form->getData();
            $fact->setCreatedAt(new \DateTime('now'));
            $documentManager->persist($fact);
            $documentManager->flush();

            return $this->redirectToRoute('home');
        }

        return $this->render('new.html.twig', ['form' => $form->createView()] );
    }

}
