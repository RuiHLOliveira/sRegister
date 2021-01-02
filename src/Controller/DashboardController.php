<?php
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index()
    {
        return $this->render('dashboard/index.html.twig');
    }

    /**
     * @Route("/phpinfo", name="home")
     */
    public function phpinfo()
    {
        return phpinfo();
    }
}
