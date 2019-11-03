<?php


namespace App\Controller;


use App\Document\FactOfTheDay;
use App\Document\User;
use App\Form\Type\FactType;
use phpDocumentor\Reflection\Types\This;
use App\Form\Type\FilterType;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;
use Symfony\Component\HttpFoundation\JsonResponse;

class FactController extends AbstractController
{
    /**
     * @Route("/", name="home", methods={"GET", "POST"})
     */
    public function list(Request $request, DocumentManager $dm)
    {
        $repository = $dm->getRepository(FactOfTheDay::class);

        $filterForm = $this->createForm(FilterType::class);

        $filterForm->handleRequest($request);

        if($filterForm->isSubmitted() && $filterForm->isValid())
        {
            /** @var \DateTime $date */
            $date = $filterForm->getData()['date'];
            if($date != null)
            {
                $startDate = new \DateTime($date->format('Y-m-d') );
                $endDate = new \DateTime($date->format('Y-m-d') );

                $startDate->setTime(00,00,00);
                $endDate->setTime(23,59,59);


                try {
                    $facts = $dm->createQueryBuilder(FactOfTheDay::class)
                        ->field('created_at')
                        ->gte($startDate)
                        ->lte($endDate)
                        ->sort('created_at', 'DESC')
                        ->getQuery()
                        ->execute();
                } catch (MongoDBException $e) {
                    return $e;
                }
            }
            else
            {
                $facts = $repository->findBy([], ['created_at' => 'DESC']);
            }



        }
        else
        {
            $facts = $repository->findBy([], ['created_at' => 'DESC']);
        }



        return $this->render('home.html.twig',
            ["facts" => $facts, "filterForm" => $filterForm->createView()]);

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


    /**
     * @Route("/delete/{fact}", name="deleteFact", methods={"POST"})
     */
    public function delete(DocumentManager $documentManager, $fact)
    {
        $repository = $documentManager->getRepository(FactOfTheDay::class);
        $factToDelete = $repository->findOneBy(["id" => $fact]);
        $documentManager->remove($factToDelete);
        $documentManager->flush();

        return $this->redirectToRoute('home');
    }

}
