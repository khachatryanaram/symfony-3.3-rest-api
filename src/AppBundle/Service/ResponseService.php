<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 1/13/20
 * Time: 11:26 AM
 */

namespace AppBundle\Service;


use Symfony\Component\HttpFoundation\JsonResponse;

class ResponseService
{

    function sendResponse($status,$message, $data = null) {
        $response = array(
            'status' =>  $status,
            'message' =>  $message,
        );


        if($data) {
            switch ($status) {
                case 'error':
                    $response['errors'] = [];


                    foreach ($data as $error) {
                        array_push($response['errors'], [
                            'field' => $error->getPropertyPath(),
                            'message' => $error->getMessageTemplate()
                        ]);
                    }
                    break;
                case 'success':
                    $response['result'] = $data;
                    break;
            }
        }

        return new JsonResponse($response);
    }

}