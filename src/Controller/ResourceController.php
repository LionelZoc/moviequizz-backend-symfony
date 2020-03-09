<?php

namespace App\Controller;


use Doctrine\ORM\EntityManagerInterface;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use App\Utils\RedisHelper;



class ResourceController extends AbstractFOSRestController
{



    protected $redisHelper;

    /** @var LoggerInterface */
    protected $logger;


    public function __construct(RedisHelper $helper, $logger)
    {
        $this->redisHelper = $helper;
        $this->logger = $logger;
    }

    protected function view($data = null, $statusCode = null, $additionalScopes = [], $checkScopes = true)
    {
        $view = parent::view($data, $statusCode);

        if ($checkScopes) {
            $rawScopes = Request::createFromGlobals()->query->get('scopes');
            $scopes = $rawScopes ? explode(',', $rawScopes) : [];

            $scopes[] = "Default";

            $scopes = array_merge($scopes, $additionalScopes);

            $context = new Context();
            $context->setGroups($scopes);

            $view->setContext($context);
        }

        return $view;
    }

    protected function success($data, $statusCode = 200, $additionalScopes = [], $checkScopes = true)
    {
        return $this->handleView($this->view($data, $statusCode, $additionalScopes, $checkScopes));
    }



    protected function error($data, $statusCode = 400)
    {
        $response = [
            'code' => $statusCode,
            'errors' => $data,
        ];

        return $this->handleView($this->view($response, $statusCode, [], false));
    }

    protected function fatal($data, $statusCode = 500)
    {
        $response = [
            'code' => $statusCode,
            'errors' => $data,
        ];
        $this->logError($data);

        return $this->error($response, $statusCode);
    }

    protected function logError($msg, $extraData = [])
    {
        $this->logger->error($msg, $extraData);
    }






}
