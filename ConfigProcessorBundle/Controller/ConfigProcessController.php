<?php


namespace App\CJW\ConfigProcessorBundle\Controller;


use App\CJW\ConfigProcessorBundle\src\ConfigProcessCoordinator;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfigProcessController extends AbstractController
{
    /** @var ContainerInterface */
    private $symContainer;

    public function __construct (
        ContainerInterface $symContainer,
        ConfigResolverInterface $ezConfigResolver,
        RequestStack $symRequestStack
    )
    {
        $this->container = $symContainer;
        ConfigProcessCoordinator::initializeCoordinator($symContainer,$ezConfigResolver,$symRequestStack);
    }

    public function retrieveProcessedParameters () {
        ConfigProcessCoordinator::startProcess();
        $processedParameters = ConfigProcessCoordinator::getProcessedParameters();
        $results = json_encode($processedParameters);

        return $this->render("@CJWConfigProcessor/test.html.twig", ["resulting_parameters" => $results]);
    }
}
