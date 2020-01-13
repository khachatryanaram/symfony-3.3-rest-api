<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 1/13/20
 * Time: 11:17 AM
 */

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


/**
 * @Route("/api")
 */
class UserController extends Controller
{
    /**
     * @Route("/user", name="create_user")
     * @Method("POST")
     */
    public function createAction(Request $request, LoggerInterface $logger)
    {

        $responseService = $this->get('app.send_response');

        try {

            $em = $this->getDoctrine()->getManager();

            $user = new User();

            $validator = $this->get('validator');
            $user->setFirstname($request->get('firstname'));
            $user->setLastname($request->get('lastname'));
            $errors = $validator->validate($user);


            if (count($errors) > 0) {
                return $responseService->sendResponse('error','Validation Error',$errors);
            }
            $em->persist($user);
            $em->flush();


            $params = [
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getFirstname()
            ];

            // log database modification
            $logger->info("User with ".json_encode($params)." params is created!");

            return $responseService->sendResponse('success','User Created!');

        } catch (\Exception $exception) {
            return $responseService->sendResponse('error',$exception->getMessage());
        }
    }


    //?sort=[{"key":"firstname","direction":"ASC"},{"key":"lastname","direction":"ASC"}]
    // add sort parameter for sorting
    /* add sort parameter for sorting
     * EXAMPLES
     * ?sort=[{"key":"firstname","direction":"ASC"},{"key":"lastname","direction":"DESC"}]
     * ?sort=[{"key":"firstname","direction":"ASC"}]
     * ?sort=[{"key":"firstname","direction":"DESC"}]
     */

    /**
     * @Route("/user", name="find_all_user")
     * @Method("GET")
     */
    public function findAllAction(Request $request)
    {


        $responseService = $this->get('app.send_response');


        try {
            $userRep = $this->getDoctrine()->getRepository('AppBundle:User');
            $usersQuery = $userRep->createQueryBuilder("u");


            $sorting = $request->get('sort');
            $sorting = json_decode($sorting, true);
            $validSorting = ['firstname','lastname'];
            $validSortingDir = ['ASC','DESC'];
            if($sorting) {
                foreach ($sorting as $sort) {
                    if(in_array($sort['key'],$validSorting) && in_array($sort['direction'],$validSortingDir)) {
                        $usersQuery->addOrderBy("u.".$sort['key'], $sort['direction']);

                    }
                }
            }

            $users = $usersQuery
                ->getQuery()
                ->getArrayResult();


            return $responseService->sendResponse('success','Users result', $users);

        } catch (\Exception $exception) {
            return $responseService->sendResponse('error',$exception->getMessage());
        }
    }

    /**
     * @Route("/user/{id}", name="find_user")
     * @Method("GET")
     */
    public function findOneAction($id)
    {


        $responseService = $this->get('app.send_response');

        try {
            $userRep = $this->getDoctrine()->getRepository('AppBundle:User');
            $user =  $userRep->createQueryBuilder("u")
                ->where('u.id = :id')
                ->setParameter('id',$id)
                ->getQuery()
                ->getArrayResult();

            if($user) {
                return $responseService->sendResponse('success','Users result', $user);
            }

            return $responseService->sendResponse('error','User not found');

        } catch (\Exception $exception) {
            return $responseService->sendResponse('error',$exception->getMessage());
        }
    }

    /**
     * @Route("/user/{id}", name="update_user")
     * @Method("POST")
     */
    public function updateOneAction(Request $request, LoggerInterface $logger, $id)
    {

        $responseService = $this->get('app.send_response');


        try {
            $userRep = $this->getDoctrine()->getRepository('AppBundle:User');
            $user = $userRep->find($id);

            if($user) {
                $logInfo = [];


                $firstname = $request->get('firstname');
                $lastname = $request->get('lastname');
                $validator = $this->get('validator');

                if($firstname) {
                    $logInfo['firstname'] = [
                        'from' => $user->getFirstname(),
                        'to' => $firstname
                    ];
                    $user->setFirstname($firstname);
                }

                if($lastname) {
                    $logInfo['lastname'] = [
                        'from' => $user->getLastname(),
                        'to' => $lastname
                    ];
                    $user->setLastname($lastname);
                }

                $errors = $validator->validate($user);

                if (count($errors) > 0) {
                    return $responseService->sendResponse('error','Validation Error',$errors);
                }

                $em = $this->getDoctrine()->getManager();
                $em->merge($user);
                $em->flush();


                $user =  $userRep->createQueryBuilder("u")
                    ->where('u.id = :id')
                    ->setParameter('id',$id)
                    ->getQuery()
                    ->getArrayResult();



                // log database modification
                $logger->info("User with ID ".$id." updated -> ".json_encode($logInfo));

                return $responseService->sendResponse('success','User Updated',$user);
            }

            return $responseService->sendResponse('error','User not found');

        } catch (\Exception $exception) {
            return $responseService->sendResponse('error',$exception->getMessage());
        }

    }

    /**
     * @Route("/user/{id}", name="delete_user")
     * @Method("DELETE")
     */
    public function deleteOneAction($id,LoggerInterface $logger)
    {


        $responseService = $this->get('app.send_response');

        try {
            $userRep = $this->getDoctrine()->getRepository('AppBundle:User');
            $user =  $userRep->find($id);

            if($user) {
                $em = $this->getDoctrine()->getManager();
                $em->remove($user);
                $em->flush();
                $logger->info("User with ID ".$id." deleted");
                return $responseService->sendResponse('success','Users deleted');
            }

            return $responseService->sendResponse('error','User not found');

        } catch (\Exception $exception) {
            return $responseService->sendResponse('error',$exception->getMessage());
        }
    }
}