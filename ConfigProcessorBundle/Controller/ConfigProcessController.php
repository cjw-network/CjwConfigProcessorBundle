<?php


namespace App\CJW\ConfigProcessorBundle\Controller;


use App\CJW\ConfigProcessorBundle\src\ConfigProcessCoordinator;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfigProcessController extends AbstractController
{

    public function __construct (
        ContainerInterface $symContainer,
        ConfigResolverInterface $ezConfigResolver,
        RequestStack $symRequestStack
    )
    {
        ConfigProcessCoordinator::initializeCoordinator($symContainer,$ezConfigResolver,$symRequestStack);
    }


    /**
     * @Route("/admin/config-processing", name="config-processing")
     */
    public function retrieveProcessedParameters () {
        ConfigProcessCoordinator::startProcess();
        $processedParameters = ConfigProcessCoordinator::getProcessedParameters();

        return new Response(
          "<html lang='en'><body><p>".$processedParameters[0]."</p></body></html>"
        );
    }
}
